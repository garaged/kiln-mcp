<?php
/**
 * WordPress Abilities API adapter for one approved native theme update.
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
 * Updates only an installed theme with a current official update package.
 */
final class ThemeUpdateRegistrar {
	private const CATEGORY = 'kiln-theme-management';

	/**
	 * Creates the native theme-update adapter.
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
	 * Registers the exact native update ability.
	 *
	 * @return void
	 */
	public function register_ability(): void {
		wp_register_ability(
			AuditReceipt::UPDATE_THEME_ABILITY_NAME,
			array(
				'label'               => __( 'Update one approved theme', 'kiln-mcp' ),
				'description'         => __( 'Updates one exact installed theme after local administrator approval.', 'kiln-mcp' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::input_schema(),
				'output_schema'       => self::output_schema(),
				'execute_callback'    => array( $this, 'update_theme' ),
				'permission_callback' => array( $this, 'can_update_theme' ),
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
	 * Validates authority, installed state, and the exact current update.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return bool|WP_Error Whether the request is admissible.
	 */
	public function can_update_theme( array $input ): bool|WP_Error {
		if ( ! current_user_can( 'update_themes' ) || ! current_user_can( 'edit_theme_options' ) ) {
			return $this->deny( 'not_update_themes', 'kiln_theme_denied' );
		}

		try {
			$request = ThemeRequest::from_input( ThemeAction::UPDATE, $input );
		} catch ( InvalidArgumentException ) {
			return $this->deny( 'invalid_input', 'kiln_theme_invalid_input' );
		}

		$theme  = wp_get_theme( $request->target );
		$update = $theme->exists() ? self::available_update( $request->target ) : null;
		if ( null === $update
			|| ! hash_equals( $update['identity'], $request->identity )
			|| ! hash_equals( wp_get_theme()->get_stylesheet(), $request->expected_active_stylesheet ) ) {
			return $this->deny( 'update_or_state_unavailable', 'kiln_theme_not_admitted' );
		}

		return true;
	}

	/**
	 * Rechecks and invokes WordPress' native single-theme update API.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return array<string, string>|WP_Error Fixed result or refusal.
	 */
	public function update_theme( array $input ): array|WP_Error {
		$permission = $this->can_update_theme( $input );
		if ( $permission instanceof WP_Error ) {
			return $permission;
		}

		$request = ThemeRequest::from_input( ThemeAction::UPDATE, $input );
		$before  = wp_get_theme()->get_stylesheet();
		$update  = self::available_update( $request->target );
		if ( null === $update
			|| ! hash_equals( $update['identity'], $request->identity )
			|| ! hash_equals( $before, $request->expected_active_stylesheet )
			|| ! $this->approval_store->consume(
				ThemeAction::UPDATE,
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

		$upgrader = new \Theme_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->upgrade( $request->target, array( 'clear_update_cache' => true ) );
		$theme    = wp_get_theme( $request->target );
		if ( true !== $result || ! $theme->exists() ) {
			return $this->deny( 'update_failed', 'kiln_theme_effect_failed' );
		}

		$this->audit_store->append( AuditReceipt::create( AuditReceipt::UPDATE_THEME_ABILITY_NAME, 'allowed', 'update_themes', 1, gmdate( 'c' ) ) );

		return array(
			'stylesheet' => $request->target,
			'identity'   => $request->identity,
		);
	}

	/**
	 * Resolves one installed theme's current official update package.
	 *
	 * @param string $stylesheet Installed theme stylesheet.
	 * @return array{identity: string}|null Exact trusted update metadata.
	 */
	public static function available_update( string $stylesheet ): ?array {
		wp_update_themes();
		$updates = get_site_transient( 'update_themes' );
		if ( ! is_object( $updates ) || ! isset( $updates->response ) || ! is_array( $updates->response )
			|| ! isset( $updates->response[ $stylesheet ] ) || ! is_array( $updates->response[ $stylesheet ] ) ) {
			return null;
		}

		$update = $updates->response[ $stylesheet ];
		if ( ! isset( $update['new_version'], $update['package'] )
			|| ! is_string( $update['new_version'] ) || '' === $update['new_version']
			|| ! is_string( $update['package'] )
			|| ! ThemeWordPressOrgRegistrar::is_official_download_link( $update['package'] ) ) {
			return null;
		}

		return array( 'identity' => hash( 'sha256', $update['new_version'] ) );
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
		$this->audit_store->append( AuditReceipt::create( AuditReceipt::UPDATE_THEME_ABILITY_NAME, 'denied', $capability_class, 0, gmdate( 'c' ) ) );

		return new WP_Error( $error_code, __( 'The requested theme update is unavailable.', 'kiln-mcp' ) );
	}

	/**
	 * Returns the closed update input schema.
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
	 * Returns fixed update result metadata.
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
