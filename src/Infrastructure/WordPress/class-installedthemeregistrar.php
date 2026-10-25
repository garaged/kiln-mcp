<?php
/**
 * WordPress Abilities API adapter for bounded installed-theme discovery.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use KilnMcp\Capability\ThemeManagement\ThemeSnapshot;
use WP_Error;
use WP_Theme;

/**
 * Registers M13's installed-theme inventory without theme source or mutation.
 */
final class InstalledThemeRegistrar {
	private const CATEGORY = 'kiln-theme-management';

	/**
	 * @param AuditReceiptStore $audit_store Local redacted receipt store.
	 */
	public function __construct(
		private readonly AuditReceiptStore $audit_store,
	) {
	}

	/**
	 * Registers the bounded theme-management discovery category.
	 *
	 * @return void
	 */
	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Kiln theme management', 'kiln-mcp' ),
				'description' => __( 'Bounded installed-theme inventory provided by Kiln MCP.', 'kiln-mcp' ),
			)
		);
	}

	/**
	 * Registers the sole M13 discovery ability.
	 *
	 * @return void
	 */
	public function register_ability(): void {
		wp_register_ability(
			AuditReceipt::LIST_INSTALLED_THEMES_ABILITY_NAME,
			array(
				'label'               => __( 'List installed themes', 'kiln-mcp' ),
				'description'         => __( 'Lists bounded installed-theme metadata without files or source.', 'kiln-mcp' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::input_schema(),
				'output_schema'       => self::output_schema(),
				'execute_callback'    => array( $this, 'list_installed_themes' ),
				'permission_callback' => array( $this, 'can_list_installed_themes' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'public'       => true,
					'show_in_rest' => false,
				),
			)
		);
	}

	/**
	 * Checks the explicit theme-options capability.
	 *
	 * @return bool Whether discovery is allowed.
	 */
	public function can_list_installed_themes(): bool {
		$allowed = current_user_can( 'edit_theme_options' );
		if ( ! $allowed ) {
			$this->audit_store->append( AuditReceipt::create( AuditReceipt::LIST_INSTALLED_THEMES_ABILITY_NAME, 'denied', 'not_edit_theme_options', 0, gmdate( 'c' ) ) );
		}

		return $allowed;
	}

	/**
	 * Returns no more than twenty fixed installed-theme metadata records.
	 *
	 * @param array<string, mixed> $input Closed empty input.
	 * @return array<string, array<int, array<string, bool|string>>|WP_Error Fixed inventory or refusal.
	 */
	public function list_installed_themes( array $input ): array|WP_Error {
		if ( array() !== $input ) {
			$this->audit_store->append( AuditReceipt::create( AuditReceipt::LIST_INSTALLED_THEMES_ABILITY_NAME, 'denied', 'invalid_input', 0, gmdate( 'c' ) ) );
			return new WP_Error( 'kiln_theme_invalid_input', __( 'Theme inventory input is invalid.', 'kiln-mcp' ) );
		}

		$active = wp_get_theme()->get_stylesheet();
		$items  = array();

		foreach ( array_slice( wp_get_themes(), 0, 20, true ) as $stylesheet => $theme ) {
			if ( ! is_string( $stylesheet ) || ! $theme instanceof WP_Theme ) {
				continue;
			}

			$items[] = ThemeSnapshot::installed(
				(string) $theme->get( 'Name' ),
				$stylesheet,
				(string) $theme->get( 'Version' ),
				hash_equals( $active, $stylesheet ),
				false !== $theme->parent(),
				false
			);
		}

		$this->audit_store->append( AuditReceipt::create( AuditReceipt::LIST_INSTALLED_THEMES_ABILITY_NAME, 'allowed', 'edit_theme_options', count( $items ), gmdate( 'c' ) ) );

		return array( 'items' => $items );
	}

	/**
	 * Returns the closed empty input schema.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function input_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(),
			'required'             => array(),
		);
	}

	/**
	 * Returns a closed, bounded inventory output schema.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function output_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'items' => array(
					'type'     => 'array',
					'maxItems' => 20,
					'items'    => array(
						'type'                 => 'object',
						'additionalProperties' => false,
						'properties'           => array(
							'name'       => array( 'type' => 'string' ),
							'stylesheet' => array( 'type' => 'string' ),
							'version'    => array( 'type' => 'string' ),
							'active'     => array( 'type' => 'boolean' ),
							'has_parent' => array( 'type' => 'boolean' ),
							'update'     => array( 'type' => 'boolean' ),
							'identity'   => array( 'type' => 'string' ),
						),
						'required'             => array(
							'name',
							'stylesheet',
							'version',
							'active',
							'has_parent',
							'update',
							'identity',
						),
					),
				),
			),
			'required'             => array( 'items' ),
		);
	}
}
