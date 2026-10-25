<?php
/**
 * WordPress Abilities API adapter for bounded theme inventory and switching.
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
use WP_Theme;

/**
 * Registers M13's bounded inventory plus approval-gated activation and recovery.
 */
final class ThemeManagementRegistrar {
	private const CATEGORY        = 'kiln-theme-management';
	private const ROLLBACK_OPTION = 'kiln_mcp_theme_rollback';

	/**
	 * @param AuditReceiptStore  $audit_store    Local redacted receipt store.
	 * @param ThemeApprovalStore $approval_store Hash-only local approval store.
	 */
	public function __construct(
		private readonly AuditReceiptStore $audit_store,
		private readonly ThemeApprovalStore $approval_store,
	) {
	}

	/**
	 * Registers M13's isolated ability category.
	 *
	 * @return void
	 */
	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Kiln theme management', 'kiln-mcp' ),
				'description' => __( 'Administrator-approved, bounded theme operations provided by Kiln MCP.', 'kiln-mcp' ),
			)
		);
	}

	/**
	 * Registers the first exact M13 abilities; there is no generic theme mutator.
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		$this->register( AuditReceipt::LIST_INSTALLED_THEMES_ABILITY_NAME, __( 'List installed themes', 'kiln-mcp' ), array( $this, 'list_installed_themes' ), array( $this, 'can_list_installed_themes' ), self::empty_input_schema(), self::theme_list_schema(), true );
		$this->register( AuditReceipt::ACTIVATE_THEME_ABILITY_NAME, __( 'Activate one approved theme', 'kiln-mcp' ), array( $this, 'activate_theme' ), array( $this, 'can_activate_theme' ), self::effect_input_schema(), self::effect_output_schema(), false );
		$this->register( AuditReceipt::ROLLBACK_THEME_ABILITY_NAME, __( 'Roll back one approved theme activation', 'kiln-mcp' ), array( $this, 'rollback_theme' ), array( $this, 'can_rollback_theme' ), self::effect_input_schema(), self::effect_output_schema(), false );
	}

	/** @return bool */
	public function can_list_installed_themes(): bool {
		return $this->can( AuditReceipt::LIST_INSTALLED_THEMES_ABILITY_NAME );
	}

	/** @param array<string, mixed> $input @return bool|WP_Error */
	public function can_activate_theme( array $input ): bool|WP_Error {
		return $this->can_effect( ThemeAction::ACTIVATE, $input );
	}

	/** @param array<string, mixed> $input @return bool|WP_Error */
	public function can_rollback_theme( array $input ): bool|WP_Error {
		return $this->can_effect( ThemeAction::ROLLBACK, $input );
	}

	/**
	 * Returns at most twenty fixed metadata records without paths, source, or URLs.
	 *
	 * @param array<string, mixed> $input Closed empty input.
	 * @return array<string, array<int, array<string, bool|string>>>|WP_Error Fixed bounded inventory.
	 */
	public function list_installed_themes( array $input ): array|WP_Error {
		if ( array() !== $input ) {
			return $this->deny( AuditReceipt::LIST_INSTALLED_THEMES_ABILITY_NAME, 'invalid_input', 'kiln_theme_invalid_input' );
		}

		$active = wp_get_theme()->get_stylesheet();
		$items  = array();
		foreach ( array_slice( wp_get_themes(), 0, 20, true ) as $stylesheet => $theme ) {
			if ( ! is_string( $stylesheet ) || ! $theme instanceof WP_Theme ) {
				continue;
			}
			$items[] = array(
				'name'       => (string) $theme->get( 'Name' ),
				'stylesheet' => $stylesheet,
				'version'    => (string) $theme->get( 'Version' ),
				'active'     => hash_equals( $active, $stylesheet ),
				'has_parent' => false !== $theme->parent(),
				'update'     => false,
				'identity'   => hash( 'sha256', (string) $theme->get( 'Version' ) ),
			);
		}

		$this->audit_store->append( AuditReceipt::create( AuditReceipt::LIST_INSTALLED_THEMES_ABILITY_NAME, 'allowed', 'edit_theme_options', count( $items ), gmdate( 'c' ) ) );
		return array( 'items' => $items );
	}

	/** @param array<string, mixed> $input @return array<string, string>|WP_Error */
	public function activate_theme( array $input ): array|WP_Error {
		return $this->switch( ThemeAction::ACTIVATE, $input );
	}

	/** @param array<string, mixed> $input @return array<string, string>|WP_Error */
	public function rollback_theme( array $input ): array|WP_Error {
		return $this->switch( ThemeAction::ROLLBACK, $input );
	}

	/** @param string $ability @return bool */
	private function can( string $ability ): bool {
		$allowed = current_user_can( 'edit_theme_options' );
		if ( ! $allowed ) {
			$this->audit_store->append( AuditReceipt::create( $ability, 'denied', 'not_edit_theme_options', 0, gmdate( 'c' ) ) );
		}
		return $allowed;
	}

	/** @param string $action @param array<string,mixed> $input @return bool|WP_Error */
	private function can_effect( string $action, array $input ): bool|WP_Error {
		if ( ! $this->can( $this->ability_for( $action ) ) ) {
			return new WP_Error( 'kiln_theme_denied', __( 'You cannot manage themes.', 'kiln-mcp' ) );
		}
		try {
			$request = ThemeRequest::from_input( $action, $input );
		} catch ( InvalidArgumentException ) {
			return $this->deny( $this->ability_for( $action ), 'invalid_input', 'kiln_theme_invalid_input' );
		}
		$theme = wp_get_theme( $request->target );
		if ( ! $theme->exists() || ! hash_equals( hash( 'sha256', (string) $theme->get( 'Version' ) ), $request->identity ) ) {
			return $this->deny( $this->ability_for( $action ), 'theme_not_admitted', 'kiln_theme_not_admitted' );
		}
		return true;
	}

	/** @param string $action @param array<string,mixed> $input @return array<string,string>|WP_Error */
	private function switch( string $action, array $input ): array|WP_Error {
		if ( true !== $this->can_effect( $action, $input ) ) {
			return $this->can_effect( $action, $input );
		}
		$request = ThemeRequest::from_input( $action, $input );
		$before  = wp_get_theme()->get_stylesheet();
		if ( ! hash_equals( $before, $request->expected_active_stylesheet ) ) {
			return $this->deny( $this->ability_for( $action ), 'stale_active_theme', 'kiln_theme_conflict' );
		}
		if ( ThemeAction::ROLLBACK === $action && ! $this->is_immediate_rollback( $request->target, $before ) ) {
			return $this->deny( $this->ability_for( $action ), 'rollback_not_admitted', 'kiln_theme_not_admitted' );
		}
		if ( ! $this->approval_store->consume( $action, $request->target, $request->identity, $before, $request->approval_token ) ) {
			return $this->deny( $this->ability_for( $action ), 'approval_unavailable', 'kiln_theme_approval_unavailable' );
		}
		switch_theme( $request->target );
		if ( ! hash_equals( wp_get_theme()->get_stylesheet(), $request->target ) ) {
			switch_theme( $before );
			return $this->deny( $this->ability_for( $action ), 'effect_failed', 'kiln_theme_effect_failed' );
		}
		if ( ThemeAction::ACTIVATE === $action ) {
			update_option( self::ROLLBACK_OPTION, array( 'previous' => $before, 'active' => $request->target ), false );
		}
		$this->audit_store->append( AuditReceipt::create( $this->ability_for( $action ), 'allowed', 'edit_theme_options', 1, gmdate( 'c' ) ) );
		return array( 'stylesheet' => $request->target, 'previous_stylesheet' => $before );
	}

	/** @return bool */
	private function is_immediate_rollback( string $target, string $active ): bool {
		$record = get_option( self::ROLLBACK_OPTION, array() );
		return is_array( $record ) && isset( $record['previous'], $record['active'] ) && is_string( $record['previous'] ) && is_string( $record['active'] ) && hash_equals( $record['previous'], $target ) && hash_equals( $record['active'], $active );
	}

	/** @param string $ability @param string $class @param string $code @return WP_Error */
	private function deny( string $ability, string $class, string $code ): WP_Error {
		$this->audit_store->append( AuditReceipt::create( $ability, 'denied', $class, 0, gmdate( 'c' ) ) );
		return new WP_Error( $code, __( 'The requested theme operation is unavailable.', 'kiln-mcp' ) );
	}

	/** @param string $action @return string */
	private function ability_for( string $action ): string {
		return ThemeAction::ACTIVATE === $action ? AuditReceipt::ACTIVATE_THEME_ABILITY_NAME : AuditReceipt::ROLLBACK_THEME_ABILITY_NAME;
	}

	/** @return array<string,mixed> */
	private static function empty_input_schema(): array { return array( 'type' => 'object', 'additionalProperties' => false, 'properties' => array(), 'required' => array() ); }
	/** @return array<string,mixed> */
	private static function effect_input_schema(): array { return array( 'type' => 'object', 'additionalProperties' => false, 'properties' => array( 'target' => array( 'type' => 'string' ), 'identity' => array( 'type' => 'string' ), 'expected_active_stylesheet' => array( 'type' => 'string' ), 'approval_token' => array( 'type' => 'string' ) ), 'required' => array( 'target', 'identity', 'expected_active_stylesheet', 'approval_token' ) ); }
	/** @return array<string,mixed> */
	private static function theme_list_schema(): array { return array( 'type' => 'object', 'additionalProperties' => false, 'properties' => array( 'items' => array( 'type' => 'array', 'maxItems' => 20, 'items' => array( 'type' => 'object' ) ) ), 'required' => array( 'items' ) ); }
	/** @return array<string,mixed> */
	private static function effect_output_schema(): array { return array( 'type' => 'object', 'additionalProperties' => false, 'properties' => array( 'stylesheet' => array( 'type' => 'string' ), 'previous_stylesheet' => array( 'type' => 'string' ) ), 'required' => array( 'stylesheet', 'previous_stylesheet' ) ); }
	/** @param string $name @param string $label @param callable $execute @param callable $permission @param array<string,mixed> $input @param array<string,mixed> $output @param bool $readonly @return void */
	private function register( string $name, string $label, callable $execute, callable $permission, array $input, array $output, bool $readonly ): void {
		wp_register_ability( $name, array( 'label' => $label, 'description' => $label, 'category' => self::CATEGORY, 'input_schema' => $input, 'output_schema' => $output, 'execute_callback' => $execute, 'permission_callback' => $permission, 'meta' => array( 'annotations' => array( 'readonly' => $readonly, 'idempotent' => $readonly ), 'public' => true, 'show_in_rest' => false ) ) );
	}
}
