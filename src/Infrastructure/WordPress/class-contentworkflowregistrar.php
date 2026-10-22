<?php
/**
 * WordPress Abilities API adapter for approval-gated content workflow.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use DateTimeImmutable;
use InvalidArgumentException;
use KilnMcp\Capability\ContentWorkflow\WorkflowAction;
use KilnMcp\Capability\ContentWorkflow\WorkflowRequest;
use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use WP_Error;
use WP_Post;

/**
 * Registers the four exact M5 lifecycle effects.
 */
final class ContentWorkflowRegistrar {
	private const CATEGORY = 'kiln-content-workflow';

	/**
	 * Creates the controlled workflow adapter.
	 *
	 * @param AuditReceiptStore     $audit_store     Local redacted receipt store.
	 * @param WorkflowApprovalStore $approval_store  Local hash-only approval store.
	 */
	public function __construct(
		private readonly AuditReceiptStore $audit_store,
		private readonly WorkflowApprovalStore $approval_store,
	) {
	}

	/**
	 * Registers the content-workflow ability category.
	 *
	 * @return void
	 */
	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Kiln content workflow', 'kiln-mcp' ),
				'description' => __( 'Administrator-approved lifecycle effects provided by Kiln MCP.', 'kiln-mcp' ),
			)
		);
	}

	/**
	 * Registers each exact lifecycle ability; there is no generic status changer.
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		$this->register_ability(
			AuditReceipt::PUBLISH_POST_ABILITY_NAME,
			WorkflowAction::PUBLISH,
			__( 'Publish one WordPress draft', 'kiln-mcp' ),
			__( 'Publishes one approved, current standard post draft.', 'kiln-mcp' )
		);
		$this->register_ability(
			AuditReceipt::SCHEDULE_POST_PUBLICATION_ABILITY_NAME,
			WorkflowAction::SCHEDULE,
			__( 'Schedule one WordPress draft', 'kiln-mcp' ),
			__( 'Schedules one approved, current standard post draft.', 'kiln-mcp' )
		);
		$this->register_ability(
			AuditReceipt::TRASH_POST_ABILITY_NAME,
			WorkflowAction::TRASH,
			__( 'Trash one WordPress post', 'kiln-mcp' ),
			__( 'Moves one approved, current standard post to the Trash.', 'kiln-mcp' )
		);
		$this->register_ability(
			AuditReceipt::RESTORE_POST_ABILITY_NAME,
			WorkflowAction::RESTORE,
			__( 'Restore one WordPress post', 'kiln-mcp' ),
			__( 'Restores one approved, current standard trashed post as a draft.', 'kiln-mcp' )
		);
	}

	/**
	 * Checks an exact workflow action without consuming its approval.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return bool|WP_Error Whether the current identity may attempt publication.
	 */
	public function can_publish( array $input ): bool|WP_Error {
		return $this->can_execute( WorkflowAction::PUBLISH, $input );
	}

	/**
	 * Checks an exact workflow action without consuming its approval.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return bool|WP_Error Whether the current identity may attempt scheduling.
	 */
	public function can_schedule( array $input ): bool|WP_Error {
		return $this->can_execute( WorkflowAction::SCHEDULE, $input );
	}

	/**
	 * Checks an exact workflow action without consuming its approval.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return bool|WP_Error Whether the current identity may attempt trashing.
	 */
	public function can_trash( array $input ): bool|WP_Error {
		return $this->can_execute( WorkflowAction::TRASH, $input );
	}

	/**
	 * Checks an exact workflow action without consuming its approval.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return bool|WP_Error Whether the current identity may attempt restoration.
	 */
	public function can_restore( array $input ): bool|WP_Error {
		return $this->can_execute( WorkflowAction::RESTORE, $input );
	}

	/**
	 * Publishes one approved current draft.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return array<string, int|string>|WP_Error Fixed metadata or refusal.
	 */
	public function publish( array $input ): array|WP_Error {
		return $this->execute( WorkflowAction::PUBLISH, $input );
	}

	/**
	 * Schedules one approved current draft.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return array<string, int|string>|WP_Error Fixed metadata or refusal.
	 */
	public function schedule( array $input ): array|WP_Error {
		return $this->execute( WorkflowAction::SCHEDULE, $input );
	}

	/**
	 * Moves one approved current post to WordPress Trash.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return array<string, int|string>|WP_Error Fixed metadata or refusal.
	 */
	public function trash( array $input ): array|WP_Error {
		return $this->execute( WorkflowAction::TRASH, $input );
	}

	/**
	 * Restores one approved current trashed post as a draft.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return array<string, int|string>|WP_Error Fixed metadata or refusal.
	 */
	public function restore( array $input ): array|WP_Error {
		return $this->execute( WorkflowAction::RESTORE, $input );
	}

	/**
	 * Registers one preselected action under its own fixed ability name.
	 *
	 * @param string $ability_name Ability identifier.
	 * @param string $action       Exact lifecycle action.
	 * @param string $label        Administrator-facing label.
	 * @param string $description  Administrator-facing description.
	 * @return void
	 */
	private function register_ability( string $ability_name, string $action, string $label, string $description ): void {
		wp_register_ability(
			$ability_name,
			array(
				'label'               => $label,
				'description'         => $description,
				'category'            => self::CATEGORY,
				'input_schema'        => self::input_schema( $action ),
				'output_schema'       => self::output_schema( $action ),
				'execute_callback'    => array( $this, $action ),
				'permission_callback' => array( $this, 'can_' . $action ),
				'meta'                => self::write_meta(),
			)
		);
	}

	/**
	 * Applies all non-consuming request, target, authority, and version checks.
	 *
	 * @param string               $action Exact lifecycle action.
	 * @param array<string, mixed> $input  Closed ability input.
	 * @return bool|WP_Error Whether the request is admissible before approval claim.
	 */
	private function can_execute( string $action, array $input ): bool|WP_Error {
		try {
			$request = WorkflowRequest::from_input( $action, $input );
		} catch ( InvalidArgumentException ) {
			return $this->deny( $action, 'invalid_input', 'kiln_workflow_invalid_input', __( 'Workflow input is invalid.', 'kiln-mcp' ) );
		}

		$post = get_post( $request->post_id );
		if ( ! $post instanceof WP_Post || ! $this->is_admitted( $post, $action ) ) {
			return $this->deny( $action, 'post_not_admitted', 'kiln_workflow_not_admitted', __( 'The requested post is not available for that workflow action.', 'kiln-mcp' ) );
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $this->deny( $action, 'not_edit_post', 'kiln_workflow_denied', __( 'You cannot change the requested post.', 'kiln-mcp' ) );
		}

		if ( ! hash_equals( $this->token_for( $post ), $request->expected_modified_token ) ) {
			return $this->deny( $action, 'stale_modified_token', 'kiln_workflow_conflict', __( 'The requested post has changed. Read it again before continuing.', 'kiln-mcp' ) );
		}

		if ( WorkflowAction::SCHEDULE === $action && ! $this->is_future_schedule( $request ) ) {
			return $this->deny( $action, 'schedule_not_future', 'kiln_workflow_schedule_time', __( 'The publication time must be in the future.', 'kiln-mcp' ) );
		}

		if ( WorkflowAction::TRASH === $action && ! $this->is_trash_enabled() ) {
			return $this->deny( $action, 'trash_disabled', 'kiln_workflow_trash_disabled', __( 'WordPress Trash is disabled, so this action is unavailable.', 'kiln-mcp' ) );
		}

		return true;
	}

	/**
	 * Rechecks the request immediately before claiming approval and making a change.
	 *
	 * @param string               $action Exact lifecycle action.
	 * @param array<string, mixed> $input  Closed ability input.
	 * @return array<string, int|string>|WP_Error Fixed metadata or refusal.
	 */
	private function execute( string $action, array $input ): array|WP_Error {
		$permission = $this->can_execute( $action, $input );
		if ( $permission instanceof WP_Error ) {
			return $permission;
		}

		$request = WorkflowRequest::from_input( $action, $input );
		$post    = get_post( $request->post_id );

		if ( ! $post instanceof WP_Post
			|| ! $this->is_admitted( $post, $action )
			|| ! current_user_can( 'edit_post', $post->ID )
			|| ! hash_equals( $this->token_for( $post ), $request->expected_modified_token )
			|| ( WorkflowAction::SCHEDULE === $action && ! $this->is_future_schedule( $request ) )
			|| ( WorkflowAction::TRASH === $action && ! $this->is_trash_enabled() ) ) {
			return $this->deny( $action, 'pre_effect_recheck_failed', 'kiln_workflow_recheck_failed', __( 'The requested workflow action is no longer available.', 'kiln-mcp' ) );
		}

		if ( ! $this->approval_store->consume( $action, $post->ID, $request->expected_modified_token, $request->approval_token ) ) {
			return $this->deny( $action, 'approval_unavailable', 'kiln_workflow_approval_unavailable', __( 'The approval is invalid, expired, or already used.', 'kiln-mcp' ) );
		}

		$effect_result = $this->apply_effect( $action, $post, $request );
		if ( ! $effect_result ) {
			return $this->deny( $action, 'effect_failed', 'kiln_workflow_effect_failed', __( 'The requested workflow action could not be completed.', 'kiln-mcp' ) );
		}

		$updated_post = get_post( $post->ID );
		if ( ! $updated_post instanceof WP_Post || ! $this->has_expected_result( $updated_post, $action ) ) {
			return $this->deny( $action, 'effect_recheck_failed', 'kiln_workflow_effect_failed', __( 'The requested workflow action could not be completed.', 'kiln-mcp' ) );
		}

		$this->refresh_version( $updated_post->ID );
		$updated_post = get_post( $updated_post->ID );
		$this->audit_store->append( AuditReceipt::create( $this->ability_for( $action ), 'allowed', 'edit_post', 1, gmdate( 'c' ) ) );

		return $this->metadata( $updated_post );
	}

	/**
	 * Applies exactly one admitted WordPress lifecycle effect.
	 *
	 * @param string          $action  Exact lifecycle action.
	 * @param WP_Post         $post    Rechecked target post.
	 * @param WorkflowRequest $request Rechecked closed request.
	 * @return bool Whether WordPress reported an effect result.
	 */
	private function apply_effect( string $action, WP_Post $post, WorkflowRequest $request ): bool {
		if ( WorkflowAction::PUBLISH === $action ) {
			$result = wp_update_post(
				wp_slash(
					array(
						'ID'          => $post->ID,
						'post_status' => 'publish',
					)
				),
				true
			);

			return ! is_wp_error( $result ) && 0 < $result;
		}

		if ( WorkflowAction::SCHEDULE === $action ) {
			$scheduled_at = new DateTimeImmutable( (string) $request->publish_at );
			$result       = wp_update_post(
				wp_slash(
					array(
						'ID'            => $post->ID,
						'post_status'   => 'future',
						'post_date'     => get_date_from_gmt( $scheduled_at->format( 'Y-m-d H:i:s' ) ),
						'post_date_gmt' => $scheduled_at->format( 'Y-m-d H:i:s' ),
						'edit_date'     => true,
					)
				),
				true
			);

			return ! is_wp_error( $result ) && 0 < $result;
		}

		if ( WorkflowAction::TRASH === $action ) {
			return wp_trash_post( $post->ID ) instanceof WP_Post;
		}

		if ( WorkflowAction::RESTORE === $action ) {
			return wp_untrash_post( $post->ID ) instanceof WP_Post;
		}

		return false;
	}

	/**
	 * Recognizes the closed M5 source-state and resource shape.
	 *
	 * @param WP_Post|null $post   Candidate target.
	 * @param string       $action Exact lifecycle action.
	 * @return bool Whether the action admits the candidate.
	 */
	private function is_admitted( ?WP_Post $post, string $action ): bool {
		if ( ! $post instanceof WP_Post || 'post' !== $post->post_type || '' !== $post->post_password ) {
			return false;
		}

		if ( WorkflowAction::PUBLISH === $action || WorkflowAction::SCHEDULE === $action ) {
			return 'draft' === $post->post_status;
		}

		if ( WorkflowAction::TRASH === $action ) {
			return in_array( $post->post_status, array( 'draft', 'publish' ), true );
		}

		return WorkflowAction::RESTORE === $action && 'trash' === $post->post_status;
	}

	/**
	 * Checks the closed result state expected for one lifecycle action.
	 *
	 * @param WP_Post|null $post   Post after the effect.
	 * @param string       $action Exact lifecycle action.
	 * @return bool Whether the post stayed within M5's result boundary.
	 */
	private function has_expected_result( ?WP_Post $post, string $action ): bool {
		if ( ! $post instanceof WP_Post || 'post' !== $post->post_type || '' !== $post->post_password ) {
			return false;
		}

		if ( WorkflowAction::PUBLISH === $action ) {
			return 'publish' === $post->post_status;
		}

		if ( WorkflowAction::SCHEDULE === $action ) {
			return 'future' === $post->post_status;
		}

		if ( WorkflowAction::TRASH === $action ) {
			return 'trash' === $post->post_status;
		}

		return WorkflowAction::RESTORE === $action && 'draft' === $post->post_status;
	}

	/**
	 * Refuses trashing when WordPress would permanently delete instead.
	 *
	 * @return bool Whether WordPress Trash is positively enabled.
	 */
	private function is_trash_enabled(): bool {
		$trash_days = defined( 'EMPTY_TRASH_DAYS' ) ? constant( 'EMPTY_TRASH_DAYS' ) : null;

		return is_int( $trash_days ) && 0 < $trash_days;
	}

	/**
	 * Checks a validated schedule timestamp against the current time.
	 *
	 * @param WorkflowRequest $request Validated scheduling request.
	 * @return bool Whether publication is strictly in the future.
	 */
	private function is_future_schedule( WorkflowRequest $request ): bool {
		return null !== $request->publish_at && ( new DateTimeImmutable( $request->publish_at ) )->getTimestamp() > time();
	}

	/**
	 * Stores a fresh opaque version component after the lifecycle effect.
	 *
	 * @param int $post_id Post to version.
	 * @return void
	 */
	private function refresh_version( int $post_id ): void {
		update_post_meta( $post_id, PostVersion::META_KEY, wp_generate_uuid4() );
	}

	/**
	 * Builds the current modification token for a post.
	 *
	 * @param WP_Post $post Target post.
	 * @return string Current opaque version token.
	 */
	private function token_for( WP_Post $post ): string {
		return PostVersion::token(
			$post->ID,
			$post->post_modified_gmt,
			(string) get_post_meta( $post->ID, PostVersion::META_KEY, true )
		);
	}

	/**
	 * Returns only fixed post metadata for a successful M5 effect.
	 *
	 * @param WP_Post|null $post Updated target.
	 * @return array<string, int|string>|WP_Error Fixed metadata or internal failure.
	 */
	private function metadata( ?WP_Post $post ): array|WP_Error {
		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'kiln_workflow_metadata_unavailable', __( 'Workflow post metadata is unavailable.', 'kiln-mcp' ) );
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
	 * Appends a redacted denial receipt and returns a stable error.
	 *
	 * @param string $action           Exact lifecycle action.
	 * @param string $capability_class Redacted denial classification.
	 * @param string $error_code       Public stable error code.
	 * @param string $message          Public fixed error message.
	 * @return WP_Error Denial error.
	 */
	private function deny( string $action, string $capability_class, string $error_code, string $message ): WP_Error {
		$this->audit_store->append( AuditReceipt::create( $this->ability_for( $action ), 'denied', $capability_class, 0, gmdate( 'c' ) ) );

		return new WP_Error( $error_code, $message );
	}

	/**
	 * Maps an internal closed action to the corresponding public ability.
	 *
	 * @param string $action Exact lifecycle action.
	 * @return string Ability identifier.
	 */
	private function ability_for( string $action ): string {
		if ( WorkflowAction::PUBLISH === $action ) {
			return AuditReceipt::PUBLISH_POST_ABILITY_NAME;
		}

		if ( WorkflowAction::SCHEDULE === $action ) {
			return AuditReceipt::SCHEDULE_POST_PUBLICATION_ABILITY_NAME;
		}

		if ( WorkflowAction::TRASH === $action ) {
			return AuditReceipt::TRASH_POST_ABILITY_NAME;
		}

		return AuditReceipt::RESTORE_POST_ABILITY_NAME;
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
	 * Returns an action-specific closed input schema.
	 *
	 * @param string $action Exact lifecycle action.
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function input_schema( string $action ): array {
		$properties = array(
			'post_id'                 => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
			'expected_modified_token' => self::token_schema(),
			'approval_token'          => self::token_schema(),
		);
		$required   = array( 'post_id', 'expected_modified_token', 'approval_token' );

		if ( WorkflowAction::SCHEDULE === $action ) {
			$properties['publish_at'] = array(
				'type'        => 'string',
				'format'      => 'date-time',
				'description' => __( 'Canonical future UTC ISO 8601 timestamp with +00:00 offset.', 'kiln-mcp' ),
			);
			$required[]               = 'publish_at';
		}

		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => $properties,
			'required'             => $required,
		);
	}

	/**
	 * Returns fixed success metadata schema for one result state.
	 *
	 * @param string $action Exact lifecycle action.
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function output_schema( string $action ): array {
		$status = WorkflowAction::PUBLISH === $action ? 'publish' : ( WorkflowAction::SCHEDULE === $action ? 'future' : ( WorkflowAction::TRASH === $action ? 'trash' : 'draft' ) );

		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'id'             => array( 'type' => 'integer' ),
				'title'          => array( 'type' => 'string' ),
				'status'         => array(
					'type' => 'string',
					'enum' => array( $status ),
				),
				'modified_at'    => array( 'type' => 'string' ),
				'modified_token' => self::token_schema(),
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

	/**
	 * Returns the shared fixed-length opaque token schema.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function token_schema(): array {
		return array(
			'type'      => 'string',
			'minLength' => 64,
			'maxLength' => 64,
			'pattern'   => '^[a-f0-9]{64}$',
		);
	}
}
