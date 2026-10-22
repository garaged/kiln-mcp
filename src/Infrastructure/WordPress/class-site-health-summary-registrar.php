<?php
/**
 * WordPress Abilities API adapter for the site health summary.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use KilnMcp\Capability\SiteHealthSummary\SiteHealthSummary;

/**
 * Registers one capability and contains all WordPress API interaction.
 */
final class SiteHealthSummaryRegistrar {
	private const CATEGORY = 'kiln-diagnostics';

	public function __construct(
		private readonly SiteHealthSummary $summary,
	) {
	}

	/**
	 * @return void
	 */
	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Kiln diagnostics', 'kiln-mcp' ),
				'description' => __( 'Read-only compatibility diagnostics provided by Kiln MCP.', 'kiln-mcp' ),
			)
		);
	}

	/**
	 * @return void
	 */
	public function register_ability(): void {
		wp_register_ability(
			SiteHealthSummary::ABILITY_NAME,
			array(
				'label'               => __( 'Get Kiln site health summary', 'kiln-mcp' ),
				'description'         => __( 'Returns a bounded, read-only Kiln and runtime compatibility summary.', 'kiln-mcp' ),
				'category'            => self::CATEGORY,
				'input_schema'        => SiteHealthSummary::input_schema(),
				'output_schema'       => SiteHealthSummary::output_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'can_execute' ),
				'meta'                => array(
					'annotations' => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'public'      => true,
					'show_in_rest' => false,
				),
			)
		);
	}

	public function can_execute(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * @return array<string, bool|string>
	 */
	public function execute(): array {
		return $this->summary->execute(
			get_bloginfo( 'version' ),
			PHP_VERSION,
			is_multisite(),
			get_stylesheet(),
		);
	}
}
