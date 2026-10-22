<?php
/**
 * WordPress Abilities API adapter for bounded post discovery.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use KilnMcp\Capability\PostDiscovery\PostDiscoveryRequest;

/**
 * Registers and executes the audited post-discovery capability.
 */
final class PostDiscoveryRegistrar {
	private const CATEGORY = 'kiln-content-discovery';

	/**
	 * @param AuditReceiptStore $audit_store Locally retained receipt store.
	 */
	public function __construct(
		private readonly AuditReceiptStore $audit_store,
	) {
	}

	/**
	 * Registers the content-discovery ability category.
	 *
	 * @return void
	 */
	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Kiln content discovery', 'kiln-mcp' ),
				'description' => __( 'Bounded WordPress post discovery provided by Kiln MCP.', 'kiln-mcp' ),
			)
		);
	}

	/**
	 * Registers the audited post-discovery ability.
	 *
	 * @return void
	 */
	public function register_ability(): void {
		wp_register_ability(
			AuditReceipt::ABILITY_NAME,
			array(
				'label'               => __( 'List WordPress posts', 'kiln-mcp' ),
				'description'         => __( 'Lists a bounded page of public or draft post metadata and records a local redacted receipt.', 'kiln-mcp' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::input_schema(),
				'output_schema'       => self::output_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'can_execute' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'   => false,
						'idempotent' => false,
					),
					'public'       => true,
					'show_in_rest' => false,
				),
			)
		);
	}

	/**
	 * Checks access and records a redacted denied receipt where required.
	 *
	 * @return bool Whether the active identity may list posts.
	 */
	public function can_execute(): bool {
		$allowed = current_user_can( 'edit_posts' );
		if ( ! $allowed ) {
			$this->audit_store->append( AuditReceipt::create( 'denied', 'not_edit_posts', 0, gmdate( 'c' ) ) );
		}

		return $allowed;
	}

	/**
	 * @param array<string, mixed> $input Closed ability input.
	 * @return array<string, array<int, array<string, int|string>>>
	 */
	public function execute( array $input ): array {
		$request = PostDiscoveryRequest::from_input( $input );
		$items   = array();

		foreach ( get_posts( $request->to_query_arguments() ) as $post ) {
			$items[] = array(
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'status'      => $post->post_status,
				'modified_at' => get_post_modified_time( 'c', true, $post ),
				'permalink'   => get_permalink( $post ),
			);
		}

		$this->audit_store->append( AuditReceipt::create( 'allowed', 'edit_posts', count( $items ), gmdate( 'c' ) ) );

		return array( 'items' => $items );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function input_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'status' => array(
					'type' => 'string',
					'enum' => array( 'publish', 'draft' ),
				),
				'limit'  => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 20,
				),
				'page'   => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
			'required'             => array( 'status', 'limit', 'page' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function output_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'items' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'additionalProperties' => false,
						'properties'           => array(
							'id'          => array( 'type' => 'integer' ),
							'title'       => array( 'type' => 'string' ),
							'status'      => array( 'type' => 'string' ),
							'modified_at' => array( 'type' => 'string' ),
							'permalink'   => array( 'type' => 'string' ),
						),
						'required'             => array( 'id', 'title', 'status', 'modified_at', 'permalink' ),
					),
				),
			),
			'required'             => array( 'items' ),
		);
	}
}
