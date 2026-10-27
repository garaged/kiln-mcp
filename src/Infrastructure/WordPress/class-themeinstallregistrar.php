<?php
/**
 * WordPress Abilities API adapter for one approved scanned theme ZIP install.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use InvalidArgumentException;
use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use KilnMcp\Capability\ThemeManagement\ThemeAction;
use KilnMcp\Capability\ThemeManagement\ThemeArchiveScanner;
use KilnMcp\Capability\ThemeManagement\ThemeRequest;
use WP_Error;

/**
 * Installs only a locally scanned artifact with an exact one-time approval.
 */
final class ThemeInstallRegistrar {
	private const CATEGORY = 'kiln-theme-management';

	/**
	 * Creates the scanned ZIP installation adapter.
	 *
	 * @param AuditReceiptStore  $audit_store    Local redacted receipt store.
	 * @param ThemeApprovalStore $approval_store Local hash-only approval store.
	 * @param ThemeArtifactStore $artifact_store Local scanned artifact store.
	 */
	public function __construct(
		private readonly AuditReceiptStore $audit_store,
		private readonly ThemeApprovalStore $approval_store,
		private readonly ThemeArtifactStore $artifact_store,
	) {
	}

	/**
	 * Registers the one exact local ZIP installation ability.
	 *
	 * @return void
	 */
	public function register_ability(): void {
		wp_register_ability(
			AuditReceipt::INSTALL_UPLOADED_THEME_ABILITY_NAME,
			array(
				'label'               => __( 'Install one approved scanned theme ZIP', 'kiln-mcp' ),
				'description'         => __( 'Installs one exact locally scanned theme ZIP after local administrator approval.', 'kiln-mcp' ),
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
	 * Validates authority and a current locally scanned artifact without effect.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return bool|WP_Error Whether the request is admissible.
	 */
	public function can_install_theme( array $input ): bool|WP_Error {
		if ( ! current_user_can( 'install_themes' ) || ! current_user_can( 'edit_theme_options' ) ) {
			return $this->deny( 'not_install_themes', 'kiln_theme_denied' );
		}

		try {
			$request = ThemeRequest::from_input( ThemeAction::INSTALL_UPLOADED_ZIP, $input );
		} catch ( InvalidArgumentException ) {
			return $this->deny( 'invalid_input', 'kiln_theme_invalid_input' );
		}

		if ( null === $request->artifact_id
			|| null === $this->artifact_path( $request->artifact_id, $request->identity )
			|| ! hash_equals( wp_get_theme()->get_stylesheet(), $request->expected_active_stylesheet ) ) {
			return $this->deny( 'artifact_or_state_unavailable', 'kiln_theme_not_admitted' );
		}

		return true;
	}

	/**
	 * Rechecks, consumes one exact approval, and invokes WordPress' theme upgrader.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return array<string, string>|WP_Error Fixed result or refusal.
	 */
	public function install_theme( array $input ): array|WP_Error {
		$permission = $this->can_install_theme( $input );
		if ( $permission instanceof WP_Error ) {
			return $permission;
		}

		$request = ThemeRequest::from_input( ThemeAction::INSTALL_UPLOADED_ZIP, $input );
		if ( null === $request->artifact_id ) {
			return $this->deny( 'artifact_unavailable', 'kiln_theme_not_admitted' );
		}

		$before        = wp_get_theme()->get_stylesheet();
		$artifact_path = $this->artifact_path( $request->artifact_id, $request->identity );
		if ( null === $artifact_path
			|| ! hash_equals( $before, $request->expected_active_stylesheet )
			|| ! $this->approval_store->consume(
				ThemeAction::INSTALL_UPLOADED_ZIP,
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
		$result   = $upgrader->install( $artifact_path, array( 'clear_update_cache' => true ) );
		$theme    = wp_get_theme( $request->target );
		if ( true !== $result || ! $theme->exists() ) {
			return $this->deny( 'install_failed', 'kiln_theme_effect_failed' );
		}

		$this->audit_store->append( AuditReceipt::create( AuditReceipt::INSTALL_UPLOADED_THEME_ABILITY_NAME, 'allowed', 'install_themes', 1, gmdate( 'c' ) ) );

		return array(
			'stylesheet' => $request->target,
			'identity'   => $request->identity,
		);
	}

	/**
	 * Rechecks the exact persisted archive digest and static scan verdict.
	 *
	 * @param string $artifact_id Opaque local artifact ID.
	 * @param string $digest      Requested SHA-256 package digest.
	 * @return string|null Internal path only when the artifact remains admitted.
	 */
	private function artifact_path( string $artifact_id, string $digest ): ?string {
		$path = $this->artifact_store->path_for( $artifact_id, $digest );
		if ( null === $path || null !== ThemeArchiveScanner::rejection_reason( $path ) ) {
			return null;
		}

		return $path;
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
		$this->audit_store->append( AuditReceipt::create( AuditReceipt::INSTALL_UPLOADED_THEME_ABILITY_NAME, 'denied', $capability_class, 0, gmdate( 'c' ) ) );

		return new WP_Error( $error_code, __( 'The requested theme installation is unavailable.', 'kiln-mcp' ) );
	}

	/**
	 * Returns the closed upload-install input schema.
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
				'artifact_id'                => array( 'type' => 'string' ),
			),
			'required'             => array(
				'target',
				'identity',
				'expected_active_stylesheet',
				'approval_token',
				'artifact_id',
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
