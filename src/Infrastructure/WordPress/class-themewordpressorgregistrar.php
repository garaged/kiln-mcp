<?php
/**
 * WordPress Abilities API adapter for one approved WordPress.org theme install.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use InvalidArgumentException;
use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use KilnMcp\Capability\ThemeManagement\ThemeAction;
use KilnMcp\Capability\ThemeManagement\ThemeRequest;
use WP_Error;

/**
 * Installs one exact WordPress.org theme version after local approval.
 */
final class ThemeWordPressOrgRegistrar {
	private const CATEGORY = 'kiln-theme-management';

	/**
	 * Creates the WordPress.org installation adapter.
	 *
	 * @param AuditReceiptStore  $audit_store    Local redacted receipt store.
	 * @param ThemeApprovalStore $approval_store Local hash-only approval store.
	 */
	public function __construct(
		private readonly AuditReceiptStore $audit_store,
		private readonly ThemeApprovalStore $approval_store,
	) {
	}

	/**
	 * Registers the exact WordPress.org installation ability.
	 *
	 * @return void
	 */
	public function register_ability(): void {
		wp_register_ability(
			AuditReceipt::INSTALL_WORDPRESS_ORG_THEME_ABILITY_NAME,
			array(
				'label'               => __( 'Install one approved WordPress.org theme', 'kiln-mcp' ),
				'description'         => __( 'Installs one exact WordPress.org theme version after local administrator approval.', 'kiln-mcp' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::input_schema(),
				'output_schema'       => self::output_schema(),
				'execute_callback'    => array( $this, 'install_theme' ),
				'permission_callback' => array( $this, 'can_install_theme' ),
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
	 * Validates authority, exact current catalog metadata, and current state.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return bool|WP_Error Whether the request is admissible.
	 */
	public function can_install_theme( array $input ): bool|WP_Error {
		if ( ! current_user_can( 'install_themes' ) || ! current_user_can( 'edit_theme_options' ) ) {
			return $this->deny( 'not_install_themes', 'kiln_theme_denied' );
		}

		try {
			$request = ThemeRequest::from_input( ThemeAction::INSTALL_WORDPRESS_ORG, $input );
		} catch ( InvalidArgumentException ) {
			return $this->deny( 'invalid_input', 'kiln_theme_invalid_input' );
		}

		$theme = $this->wordpress_org_theme( $request->target );
		if ( null === $theme
			|| ! hash_equals( $theme['identity'], $request->identity )
			|| ! hash_equals( wp_get_theme()->get_stylesheet(), $request->expected_active_stylesheet ) ) {
			return $this->deny( 'catalog_or_state_unavailable', 'kiln_theme_not_admitted' );
		}

		return true;
	}

	/**
	 * Rechecks and installs only the exact approved catalog release.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return array<string, string>|WP_Error Fixed result or refusal.
	 */
	public function install_theme( array $input ): array|WP_Error {
		$permission = $this->can_install_theme( $input );
		if ( $permission instanceof WP_Error ) {
			return $permission;
		}

		$request = ThemeRequest::from_input( ThemeAction::INSTALL_WORDPRESS_ORG, $input );
		$before  = wp_get_theme()->get_stylesheet();
		$theme   = $this->wordpress_org_theme( $request->target );
		if ( null === $theme
			|| ! hash_equals( $theme['identity'], $request->identity )
			|| ! hash_equals( $before, $request->expected_active_stylesheet )
			|| ! $this->approval_store->consume(
				ThemeAction::INSTALL_WORDPRESS_ORG,
				$request->target,
				$request->identity,
				$before,
				$request->approval_token
			) ) {
			return $this->deny( 'approval_or_state_unavailable', 'kiln_theme_approval_unavailable' );
		}

		if ( ! $this->load_upgrader() ) {
			return $this->deny( 'upgrader_unavailable', 'kiln_theme_effect_failed' );
		}

		$upgrader  = new \Theme_Upgrader( new \Automatic_Upgrader_Skin() );
		$result    = $upgrader->install( $theme['download_link'], array( 'clear_update_cache' => true ) );
		$installed = wp_get_theme( $request->target );
		if ( true !== $result || ! $installed->exists() ) {
			return $this->deny( 'install_failed', 'kiln_theme_effect_failed' );
		}

		$this->audit_store->append( AuditReceipt::create( AuditReceipt::INSTALL_WORDPRESS_ORG_THEME_ABILITY_NAME, 'allowed', 'install_themes', 1, gmdate( 'c' ) ) );

		return array(
			'stylesheet' => $request->target,
			'identity'   => $request->identity,
		);
	}

	/**
	 * Resolves and narrows catalog metadata without exposing it to MCP.
	 *
	 * @param string $slug Closed WordPress.org theme slug.
	 * @return array{identity: string, download_link: string}|null Exact trusted catalog package data.
	 */
	public static function wordpress_org_theme( string $slug ): ?array {
		$information = themes_api( 'theme_information', array( 'slug' => $slug ) );
		if ( is_wp_error( $information )
			|| ! isset( $information->version, $information->download_link )
			|| ! is_string( $information->version ) || '' === $information->version
			|| ! is_string( $information->download_link )
			|| ! self::is_official_download_link( $information->download_link ) ) {
			return null;
		}

		return array(
			'identity'      => hash( 'sha256', $information->version ),
			'download_link' => $information->download_link,
		);
	}

	/**
	 * Accepts only the canonical WordPress.org theme package host and path.
	 *
	 * @param string $download_link Candidate catalog package URL.
	 * @return bool Whether it is an official HTTPS theme ZIP URL.
	 */
	private static function is_official_download_link( string $download_link ): bool {
		$parts = wp_parse_url( $download_link );
		if ( ! is_array( $parts )
			|| ! isset( $parts['scheme'], $parts['host'], $parts['path'] )
			|| 'https' !== $parts['scheme']
			|| 'downloads.wordpress.org' !== $parts['host']
			|| ! is_string( $parts['path'] ) ) {
			return false;
		}

		return 1 === preg_match( '#\\A/theme/[a-z0-9][a-z0-9_-]{0,99}\\.[0-9A-Za-z._-]+\\.zip\\z#D', $parts['path'] );
	}

	/**
	 * Loads only WordPress' native upgrader implementation.
	 *
	 * @return bool Whether the native upgrader is available.
	 */
	private function load_upgrader(): bool {
		if ( class_exists( '\\Theme_Upgrader' ) && class_exists( '\\Automatic_Upgrader_Skin' ) ) {
			return true;
		}

		$wordpress_root = defined( 'ABSPATH' ) ? constant( 'ABSPATH' ) : null;
		if ( ! is_string( $wordpress_root ) || '' === $wordpress_root ) {
			return false;
		}

		require_once $wordpress_root . 'wp-admin/includes/class-wp-upgrader.php';

		return class_exists( '\\Theme_Upgrader' ) && class_exists( '\\Automatic_Upgrader_Skin' );
	}

	/**
	 * Appends a fixed redacted receipt and returns a stable refusal.
	 *
	 * @param string $capability_class Redacted denial classification.
	 * @param string $error_code       Public stable error code.
	 * @return WP_Error Stable refusal.
	 */
	private function deny( string $capability_class, string $error_code ): WP_Error {
		$this->audit_store->append( AuditReceipt::create( AuditReceipt::INSTALL_WORDPRESS_ORG_THEME_ABILITY_NAME, 'denied', $capability_class, 0, gmdate( 'c' ) ) );

		return new WP_Error( $error_code, __( 'The requested theme installation is unavailable.', 'kiln-mcp' ) );
	}

	/**
	 * Returns the closed catalog-install input schema.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function input_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'target'                     => array( 'type' => 'string' ),
				'identity'                   => array( 'type' => 'string' ),
				'expected_active_stylesheet' => array( 'type' => 'string' ),
				'approval_token'             => array( 'type' => 'string' ),
			),
			'required'             => array(
				'target',
				'identity',
				'expected_active_stylesheet',
				'approval_token',
			),
		);
	}

	/**
	 * Returns fixed installation result metadata.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function output_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'stylesheet' => array( 'type' => 'string' ),
				'identity'   => array( 'type' => 'string' ),
			),
			'required'             => array(
				'stylesheet',
				'identity',
			),
		);
	}
}
