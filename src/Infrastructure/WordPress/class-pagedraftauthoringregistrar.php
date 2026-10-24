<?php
/**
 * WordPress Abilities API adapter for safe draft authoring.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use InvalidArgumentException;
use KilnMcp\Capability\PageDraftAuthoring\PageDraftCreateRequest;
use KilnMcp\Capability\PageDraftAuthoring\PageDraftUpdateRequest;
use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use WP_Error;
use WP_Post;

/**
 * Registers bounded draft creation and optimistic-concurrency draft updates.
 */
final class PageDraftAuthoringRegistrar {
	private const CATEGORY = 'kiln-content-authoring';

	/**
	 * Creates the safe draft-authoring WordPress adapter.
	 *
	 * @param AuditReceiptStore $audit_store Locally retained receipt store.
	 */
	public function __construct(
		private readonly AuditReceiptStore $audit_store,
	) {
	}

	/**
	 * Registers the draft-authoring ability category.
	 *
	 * @return void
	 */
	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Kiln content authoring', 'kiln-mcp' ),
				'description' => __( 'Bounded draft-only WordPress authoring provided by Kiln MCP.', 'kiln-mcp' ),
			)
		);
	}

	/**
	 * Registers both M4 authoring abilities.
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		wp_register_ability(
			AuditReceipt::CREATE_PAGE_DRAFT_ABILITY_NAME,
			array(
				'label'               => __( 'Create a WordPress page draft', 'kiln-mcp' ),
				'description'         => __( 'Creates one active-user-owned standard page draft from bounded plain text.', 'kiln-mcp' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::create_input_schema(),
				'output_schema'       => self::output_schema(),
				'execute_callback'    => array( $this, 'create_draft' ),
				'permission_callback' => array( $this, 'can_create_draft' ),
				'meta'                => self::write_meta(),
			)
		);

		wp_register_ability(
			AuditReceipt::UPDATE_PAGE_DRAFT_ABILITY_NAME,
			array(
				'label'               => __( 'Update a WordPress page draft', 'kiln-mcp' ),
				'description'         => __( 'Updates one permitted standard page draft with optimistic concurrency and bounded plain text.', 'kiln-mcp' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::update_input_schema(),
				'output_schema'       => self::output_schema(),
				'execute_callback'    => array( $this, 'update_draft' ),
				'permission_callback' => array( $this, 'can_update_draft' ),
				'meta'                => self::write_meta(),
			)
		);
	}

	/**
	 * Refreshes the local revision component whenever a standard page changes.
	 *
	 * @param int     $page_id      Updated post ID.
	 * @param WP_Post $post_after   Post after the database update.
	 * @param WP_Post $post_before  Post before the database update.
	 * @return void
	 */
	public function record_page_update( int $page_id, WP_Post $post_after, WP_Post $post_before ): void {
		unset( $post_before );

		if ( 'page' !== $post_after->post_type ) {
			return;
		}

		update_post_meta( $page_id, PostVersion::META_KEY, wp_generate_uuid4() );
	}

	/**
	 * Checks create authorization and records an appropriately redacted denial.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return bool|WP_Error Whether the active identity may create a draft.
	 */
	public function can_create_draft( array $input ): bool|WP_Error {
		try {
			PageDraftCreateRequest::from_input( $input );
		} catch ( InvalidArgumentException ) {
			return $this->deny( AuditReceipt::CREATE_PAGE_DRAFT_ABILITY_NAME, 'invalid_input', 'kiln_invalid_draft_create_input', __( 'Draft create input is invalid.', 'kiln-mcp' ) );
		}

		if ( ! current_user_can( 'edit_pages' ) ) {
			return $this->deny( AuditReceipt::CREATE_PAGE_DRAFT_ABILITY_NAME, 'not_edit_pages', 'kiln_draft_create_denied', __( 'You cannot create a draft.', 'kiln-mcp' ) );
		}

		return true;
	}

	/**
	 * Creates one active-user-owned standard draft and returns fixed metadata.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return array<string, int|string>|WP_Error Created draft metadata or a denial.
	 */
	public function create_draft( array $input ): array|WP_Error {
		$permission = $this->can_create_draft( $input );
		if ( $permission instanceof WP_Error ) {
			return $permission;
		}

		$request = PageDraftCreateRequest::from_input( $input );
		$page_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'      => 'page',
					'post_status'    => 'draft',
					'post_author'    => get_current_user_id(),
					'post_title'     => $request->title,
					'post_content'   => $request->content,
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return $this->deny( AuditReceipt::CREATE_PAGE_DRAFT_ABILITY_NAME, 'write_failed', 'kiln_draft_create_failed', __( 'The draft could not be created.', 'kiln-mcp' ) );
		}

		$post = get_post( $page_id );
		if ( ! $post instanceof WP_Post || ! $this->is_admitted_draft( $post ) || get_current_user_id() !== (int) $post->post_author ) {
			return $this->deny( AuditReceipt::CREATE_PAGE_DRAFT_ABILITY_NAME, 'write_recheck_failed', 'kiln_draft_create_failed', __( 'The draft could not be created.', 'kiln-mcp' ) );
		}

		$this->refresh_version( $post->ID );
		$post = get_post( $post->ID );
		$this->audit_store->append( AuditReceipt::create( AuditReceipt::CREATE_PAGE_DRAFT_ABILITY_NAME, 'allowed', 'edit_pages', 1, gmdate( 'c' ) ) );

		return $this->metadata( $post instanceof WP_Post ? $post : null );
	}

	/**
	 * Checks target admission, exact per-post authority, and concurrency state.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return bool|WP_Error Whether the active identity may update the current draft version.
	 */
	public function can_update_draft( array $input ): bool|WP_Error {
		try {
			$request = PageDraftUpdateRequest::from_input( $input );
		} catch ( InvalidArgumentException ) {
			return $this->deny( AuditReceipt::UPDATE_PAGE_DRAFT_ABILITY_NAME, 'invalid_input', 'kiln_invalid_draft_update_input', __( 'Draft update input is invalid.', 'kiln-mcp' ) );
		}

		$post = get_post( $request->page_id );
		if ( ! $post instanceof WP_Post || ! $this->is_admitted_draft( $post ) ) {
			return $this->deny( AuditReceipt::UPDATE_PAGE_DRAFT_ABILITY_NAME, 'post_not_admitted', 'kiln_draft_not_admitted', __( 'The requested draft is not available.', 'kiln-mcp' ) );
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $this->deny( AuditReceipt::UPDATE_PAGE_DRAFT_ABILITY_NAME, 'not_edit_post', 'kiln_draft_update_denied', __( 'You cannot update the requested draft.', 'kiln-mcp' ) );
		}

		if ( ! hash_equals( $this->token_for( $post ), $request->expected_modified_token ) ) {
			return $this->deny( AuditReceipt::UPDATE_PAGE_DRAFT_ABILITY_NAME, 'stale_modified_token', 'kiln_draft_conflict', __( 'The requested draft has changed. Read it again before updating.', 'kiln-mcp' ) );
		}

		return true;
	}

	/**
	 * Updates one admitted draft without changing its workflow state.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return array<string, int|string>|WP_Error Updated draft metadata or a denial.
	 */
	public function update_draft( array $input ): array|WP_Error {
		$permission = $this->can_update_draft( $input );
		if ( $permission instanceof WP_Error ) {
			return $permission;
		}

		$request = PageDraftUpdateRequest::from_input( $input );
		$page_id = wp_update_post(
			wp_slash(
				array(
					'ID'           => $request->page_id,
					'post_title'   => $request->title,
					'post_content' => $request->content,
				)
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return $this->deny( AuditReceipt::UPDATE_PAGE_DRAFT_ABILITY_NAME, 'write_failed', 'kiln_draft_update_failed', __( 'The draft could not be updated.', 'kiln-mcp' ) );
		}

		$post = get_post( $page_id );
		if ( ! $post instanceof WP_Post || ! $this->is_admitted_draft( $post ) || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $this->deny( AuditReceipt::UPDATE_PAGE_DRAFT_ABILITY_NAME, 'write_recheck_failed', 'kiln_draft_update_failed', __( 'The draft could not be updated.', 'kiln-mcp' ) );
		}

		$this->refresh_version( $post->ID );
		$post = get_post( $post->ID );
		$this->audit_store->append( AuditReceipt::create( AuditReceipt::UPDATE_PAGE_DRAFT_ABILITY_NAME, 'allowed', 'edit_post', 1, gmdate( 'c' ) ) );

		return $this->metadata( $post instanceof WP_Post ? $post : null );
	}

	/**
	 * Recognizes the one mutable resource shape admitted by M4.
	 *
	 * @param WP_Post|null $post Candidate target.
	 * @return bool Whether the post is a standard, unprotected draft.
	 */
	private function is_admitted_draft( ?WP_Post $post ): bool {
		return $post instanceof WP_Post
			&& 'page' === $post->post_type
			&& 'draft' === $post->post_status
			&& '' === $post->post_password;
	}

	/**
	 * Stores a fresh opaque version component without retaining request content.
	 *
	 * @param int $page_id Page to version.
	 * @return void
	 */
	private function refresh_version( int $page_id ): void {
		update_post_meta( $page_id, PostVersion::META_KEY, wp_generate_uuid4() );
	}

	/**
	 * Builds the current modification token for an admitted page.
	 *
	 * @param WP_Post $post Post whose version is exposed.
	 * @return string Current opaque modification token.
	 */
	private function token_for( WP_Post $post ): string {
		return PostVersion::token(
			$post->ID,
			$post->post_modified_gmt,
			(string) get_post_meta( $post->ID, PostVersion::META_KEY, true )
		);
	}

	/**
	 * Returns only the fixed M4 draft metadata shape.
	 *
	 * @param WP_Post|null $post Created or updated post.
	 * @return array<string, int|string>|WP_Error Fixed metadata or an internal failure.
	 */
	private function metadata( ?WP_Post $post ): array|WP_Error {
		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'kiln_draft_metadata_unavailable', __( 'Draft metadata is unavailable.', 'kiln-mcp' ) );
		}

		return array(
			'id'             => $post->ID,
			'title'          => $post->post_title,
			'status'         => $post->post_status,
			'modified_at'    => (string) get_post_modified_time( 'c', true, $post ),
			'modified_token' => $this->token_for( $post ),
			'permalink'      => (string) get_permalink( $post ),
		);
	}

	/**
	 * Appends a redacted denial receipt and returns a fixed WordPress error.
	 *
	 * @param string $ability          Kiln ability identifier.
	 * @param string $capability_class Redacted denial classification.
	 * @param string $error_code       Public stable error code.
	 * @param string $message          Public fixed error message.
	 * @return WP_Error Denial error.
	 */
	private function deny( string $ability, string $capability_class, string $error_code, string $message ): WP_Error {
		$this->audit_store->append( AuditReceipt::create( $ability, 'denied', $capability_class, 0, gmdate( 'c' ) ) );

		return new WP_Error( $error_code, $message );
	}

	/**
	 * Returns common non-read-only ability metadata.
	 *
	 * @return array<string, mixed>
	 */
	private static function write_meta(): array {
		return array(
			'annotations'  => array(
				'readonly'   => false,
				'idempotent' => false,
			),
			'public'       => true,
			'show_in_rest' => false,
		);
	}

	/**
	 * Returns the closed creation input JSON Schema.
	 *
	 * @return array<string, mixed>
	 */
	private static function create_input_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'title'   => array(
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => PageDraftCreateRequest::MAX_TITLE_CHARACTERS,
				),
				'content' => array(
					'type'      => 'string',
					'maxLength' => PageDraftCreateRequest::MAX_CONTENT_CHARACTERS,
				),
			),
			'required'             => array( 'title', 'content' ),
		);
	}

	/**
	 * Returns the closed update input JSON Schema.
	 *
	 * @return array<string, mixed>
	 */
	private static function update_input_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'page_id'                 => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'expected_modified_token' => array(
					'type'      => 'string',
					'minLength' => 64,
					'maxLength' => 64,
					'pattern'   => '^[a-f0-9]{64}$',
				),
				'title'                   => array(
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => PageDraftCreateRequest::MAX_TITLE_CHARACTERS,
				),
				'content'                 => array(
					'type'      => 'string',
					'maxLength' => PageDraftCreateRequest::MAX_CONTENT_CHARACTERS,
				),
			),
			'required'             => array( 'page_id', 'expected_modified_token', 'title', 'content' ),
		);
	}

	/**
	 * Returns the fixed draft metadata output JSON Schema.
	 *
	 * @return array<string, mixed>
	 */
	private static function output_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'id'             => array( 'type' => 'integer' ),
				'title'          => array( 'type' => 'string' ),
				'status'         => array(
					'type' => 'string',
					'enum' => array( 'draft' ),
				),
				'modified_at'    => array( 'type' => 'string' ),
				'modified_token' => array(
					'type'      => 'string',
					'minLength' => 64,
					'maxLength' => 64,
					'pattern'   => '^[a-f0-9]{64}$',
				),
				'permalink'      => array( 'type' => 'string' ),
			),
			'required'             => array(
				'id',
				'title',
				'status',
				'modified_at',
				'modified_token',
				'permalink',
			),
		);
	}
}
