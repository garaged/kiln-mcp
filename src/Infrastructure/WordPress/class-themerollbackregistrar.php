<?php
/**
 * WordPress Abilities API adapter for one approved immediate theme rollback.
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
 * Restores only the prior installed theme recorded by a successful Kiln activation.
 */
final class ThemeRollbackRegistrar {
	private const CATEGORY = 'kiln-theme-management';

	/**
	 * Creates the rollback adapter.
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
	 * Registers the one exact rollback ability.
	 *
	 * @return void
	 */
	public function register_ability(): void {
		wp_register_ability(
			AuditReceipt::ROLLBACK_THEME_ABILITY_NAME,
			array(
				'label'               => __( 'Rollback one approved theme activation', 'kiln-mcp' ),
				'description'         => __( 'Restores only the immediately previous installed theme after local administrator approval.', 'kiln-mcp' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::input_schema(),
				'output_schema'       => self::output_schema(),
				'execute_callback'    => array( $this, 'rollback_theme' ),
				'permission_callback' => array( $this, 'can_rollback_theme' ),
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
	 * Validates authority, the prior-theme record, and current state before consumption.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return bool|WP_Error Whether the request is admissible.
	 */
	public function can_rollback_theme( array $input ): bool|WP_Error {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return $this->deny( 'not_edit_theme_options', 'kiln_theme_denied' );
		}

		try {
			$request = ThemeRequest::from_input( ThemeAction::ROLLBACK, $input );
		} catch ( InvalidArgumentException ) {
			return $this->deny( 'invalid_input', 'kiln_theme_invalid_input' );
		}

		$record = $this->rollback_record();
		if ( null === $record
			|| ! hash_equals( $record['previous'], $request->target )
			|| ! hash_equals( $record['active'], $request->expected_active_stylesheet )
			|| ! hash_equals( wp_get_theme()->get_stylesheet(), $record['active'] ) ) {
			return $this->deny( 'rollback_unavailable', 'kiln_theme_not_admitted' );
		}

		$theme = wp_get_theme( $record['previous'] );
		if ( ! $theme->exists()
			|| ! hash_equals( hash( 'sha256', (string) $theme->get( 'Version' ) ), $request->identity ) ) {
			return $this->deny( 'previous_theme_changed', 'kiln_theme_conflict' );
		}

		return true;
	}

	/**
	 * Rechecks, consumes one exact approval, and restores only the recorded prior theme.
	 *
	 * @param array<string, mixed> $input Closed ability input.
	 * @return array<string, string>|WP_Error Fixed result or refusal.
	 */
	public function rollback_theme( array $input ): array|WP_Error {
		$permission = $this->can_rollback_theme( $input );
		if ( $permission instanceof WP_Error ) {
			return $permission;
		}

		$request = ThemeRequest::from_input( ThemeAction::ROLLBACK, $input );
		$record  = $this->rollback_record();
		if ( null === $record
			|| ! hash_equals( $record['previous'], $request->target )
			|| ! hash_equals( $record['active'], $request->expected_active_stylesheet )
			|| ! hash_equals( wp_get_theme()->get_stylesheet(), $record['active'] ) ) {
			return $this->deny( 'pre_effect_recheck_failed', 'kiln_theme_conflict' );
		}

		$theme = wp_get_theme( $record['previous'] );
		if ( ! $theme->exists()
			|| ! hash_equals( hash( 'sha256', (string) $theme->get( 'Version' ) ), $request->identity )
			|| ! $this->approval_store->consume(
				ThemeAction::ROLLBACK,
				$request->target,
				$request->identity,
				$record['active'],
				$request->approval_token
			) ) {
			return $this->deny( 'approval_or_state_unavailable', 'kiln_theme_approval_unavailable' );
		}

		switch_theme( $record['previous'] );
		if ( ! hash_equals( wp_get_theme()->get_stylesheet(), $record['previous'] ) ) {
			switch_theme( $record['active'] );
			return $this->deny( 'rollback_failed', 'kiln_theme_effect_failed' );
		}

		if ( ! delete_option( ThemeActivationRegistrar::ROLLBACK_OPTION ) ) {
			switch_theme( $record['active'] );
			return $this->deny( 'rollback_record_clear_failed', 'kiln_theme_effect_failed' );
		}

		$this->audit_store->append( AuditReceipt::create( AuditReceipt::ROLLBACK_THEME_ABILITY_NAME, 'allowed', 'edit_theme_options', 1, gmdate( 'c' ) ) );

		return array(
			'stylesheet'          => $record['previous'],
			'previous_stylesheet' => $record['active'],
		);
	}

	/**
	 * Reads only a complete bounded immediate-rollback record.
	 *
	 * @return array{previous: string, active: string}|null Record or no eligible rollback.
	 */
	private function rollback_record(): ?array {
		$record = get_option( ThemeActivationRegistrar::ROLLBACK_OPTION, array() );
		if ( ! is_array( $record )
			|| array_keys( $record ) !== array( 'previous', 'active' )
			|| ! is_string( $record['previous'] )
			|| ! is_string( $record['active'] )
			|| $record['previous'] === $record['active'] ) {
			return null;
		}

		return array(
			'previous' => $record['previous'],
			'active'   => $record['active'],
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
		$this->audit_store->append( AuditReceipt::create( AuditReceipt::ROLLBACK_THEME_ABILITY_NAME, 'denied', $capability_class, 0, gmdate( 'c' ) ) );

		return new WP_Error( $error_code, __( 'The requested theme rollback is unavailable.', 'kiln-mcp' ) );
	}

	/**
	 * Returns the closed rollback input schema.
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
	 * Returns fixed rollback result metadata.
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
