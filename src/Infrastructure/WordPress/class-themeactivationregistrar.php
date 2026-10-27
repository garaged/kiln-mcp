<?php
/**
 * WordPress Abilities API adapter for one approved installed-theme activation.
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
 * Executes only a state-bound administrator-approved theme activation.
 */
final class ThemeActivationRegistrar {
	private const CATEGORY = 'kiln-theme-management';

	public const ROLLBACK_OPTION = 'kiln_mcp_theme_rollback';

	/**
	 * Creates the activation adapter.
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
	 * Registers the one exact activation ability.
	 *
	 * @return void
	 */
	public function register_ability(): void {
		wp_register_ability(
			AuditReceipt::ACTIVATE_THEME_ABILITY_NAME,
			array(
				'label'               => __( 'Activate one approved theme', 'kiln-mcp' ),
				'description'         => __( 'Activates one exact installed theme after local administrator approval.', 'kiln-mcp' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::input_schema(),
				'output_schema'       => self::output_schema(),
				'execute_callback'    => array( $this, 'activate_theme' ),
				'permission_callback' => array( $this, 'can_activate_theme' ),
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
	 * Validates authority and the exact current target before token consumption.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return bool|WP_Error Whether the request is admissible.
	 */
	public function can_activate_theme( array $input ): bool|WP_Error {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return $this->deny( 'not_edit_theme_options', 'kiln_theme_denied' );
		}

		try {
			$request = ThemeRequest::from_input( ThemeAction::ACTIVATE, $input );
		} catch ( InvalidArgumentException ) {
			return $this->deny( 'invalid_input', 'kiln_theme_invalid_input' );
		}

		$theme = wp_get_theme( $request->target );
		if ( ! $theme->exists() ) {
			return $this->deny( 'theme_not_installed', 'kiln_theme_not_admitted' );
		}

		$identity = hash( 'sha256', (string) $theme->get( 'Version' ) );
		if ( ! hash_equals( $identity, $request->identity ) ) {
			return $this->deny( 'theme_changed', 'kiln_theme_conflict' );
		}

		if ( ! hash_equals( wp_get_theme()->get_stylesheet(), $request->expected_active_stylesheet ) ) {
			return $this->deny( 'active_theme_changed', 'kiln_theme_conflict' );
		}

		return true;
	}

	/**
	 * Rechecks, consumes one exact approval, and activates only the admitted theme.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return array<string, string>|WP_Error Fixed result or refusal.
	 */
	public function activate_theme( array $input ): array|WP_Error {
		$permission = $this->can_activate_theme( $input );
		if ( $permission instanceof WP_Error ) {
			return $permission;
		}

		$request = ThemeRequest::from_input( ThemeAction::ACTIVATE, $input );
		$before  = wp_get_theme()->get_stylesheet();
		$theme   = wp_get_theme( $request->target );

		if ( ! $theme->exists()
			|| ! hash_equals( hash( 'sha256', (string) $theme->get( 'Version' ) ), $request->identity )
			|| ! hash_equals( $before, $request->expected_active_stylesheet ) ) {
			return $this->deny( 'pre_effect_recheck_failed', 'kiln_theme_conflict' );
		}

		if ( ! $this->approval_store->consume(
			ThemeAction::ACTIVATE,
			$request->target,
			$request->identity,
			$before,
			$request->approval_token
		) ) {
			return $this->deny( 'approval_unavailable', 'kiln_theme_approval_unavailable' );
		}

		switch_theme( $request->target );

		if ( ! hash_equals( wp_get_theme()->get_stylesheet(), $request->target ) ) {
			switch_theme( $before );
			return $this->deny( 'activation_failed', 'kiln_theme_effect_failed' );
		}

		if ( ! update_option(
			self::ROLLBACK_OPTION,
			array(
				'previous' => $before,
				'active'   => $request->target,
			),
			false
		) ) {
			switch_theme( $before );
			return $this->deny( 'rollback_record_failed', 'kiln_theme_effect_failed' );
		}

		$this->audit_store->append( AuditReceipt::create( AuditReceipt::ACTIVATE_THEME_ABILITY_NAME, 'allowed', 'edit_theme_options', 1, gmdate( 'c' ) ) );

		return array(
			'stylesheet'          => $request->target,
			'previous_stylesheet' => $before,
		);
	}

	/**
	 * Appends a fixed redacted receipt and returns a stable refusal.
	 *
	 * @param string $capability_class Redacted denial classification.
	 * @param string $error_code       Public stable error code.
	 * @return WP_Error Stable refusal.
	 */
	private function deny( string $capability_class, string $error_code ): WP_Error {
		$this->audit_store->append( AuditReceipt::create( AuditReceipt::ACTIVATE_THEME_ABILITY_NAME, 'denied', $capability_class, 0, gmdate( 'c' ) ) );

		return new WP_Error( $error_code, __( 'The requested theme activation is unavailable.', 'kiln-mcp' ) );
	}

	/**
	 * Returns the closed activation input schema.
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
	 * Returns fixed activation result metadata.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function output_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'stylesheet'          => array( 'type' => 'string' ),
				'previous_stylesheet' => array( 'type' => 'string' ),
			),
			'required'             => array(
				'stylesheet',
				'previous_stylesheet',
			),
		);
	}
}
