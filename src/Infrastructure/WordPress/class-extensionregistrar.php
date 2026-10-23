<?php
/**
 * Governed WordPress adapter for M7 extension manifests.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use KilnMcp\Extension\ExtensionAbility;
use KilnMcp\Extension\ExtensionManifest;
use KilnMcp\Extension\ExtensionRegistry;
use Throwable;
use WP_Error;
use WP_Hook;

/**
 * Admits and exposes only bounded, core-governed extension abilities.
 */
final class ExtensionRegistrar {
	private const CATEGORY = 'kiln-extensions';
	private const HOOK     = 'kiln_mcp_register_extensions';

	/**
	 * Creates the governed extension adapter.
	 *
	 * @param AuditReceiptStore $audit_store Locally retained receipt store.
	 */
	public function __construct(
		private readonly AuditReceiptStore $audit_store,
	) {
	}

	/**
	 * Registers the extension category owned by Kiln core.
	 *
	 * @return void
	 */
	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Kiln extensions', 'kiln-mcp' ),
				'description' => __( 'Administrator-gated, read-only local extensions governed by Kiln MCP.', 'kiln-mcp' ),
			)
		);
	}

	/**
	 * Collects isolated extension declarations and registers admitted abilities.
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		$registry = new ExtensionRegistry();
		$this->collect_manifests( $registry );

		foreach ( $registry->manifests() as $manifest ) {
			foreach ( $manifest->abilities as $ability ) {
				if ( $ability instanceof ExtensionAbility ) {
					$this->register_ability( $manifest, $ability );
				}
			}
		}
	}

	/**
	 * Invokes each registered extension provider in its own transaction.
	 *
	 * Providers register against a private staging registry. A thrown exception
	 * or a conflicting batch leaves the shared registry unchanged and dispatch
	 * proceeds to the next provider.
	 *
	 * @param ExtensionRegistry $registry Shared admitted-manifest registry.
	 * @return void
	 */
	private function collect_manifests( ExtensionRegistry $registry ): void {
		global $wp_filter;

		$hook = $wp_filter[ self::HOOK ] ?? null;
		if ( ! $hook instanceof WP_Hook ) {
			return;
		}

		foreach ( $hook->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$provider = $callback['function'] ?? null;
				if ( ! is_callable( $provider ) ) {
					continue;
				}

				$staged = new ExtensionRegistry();
				try {
					$provider( $staged );
					$registry->register_all( array_values( $staged->manifests() ) );
				} catch ( Throwable ) {
					continue;
				}
			}
		}
	}

	/**
	 * Registers one core-governed extension ability.
	 *
	 * @param ExtensionManifest $manifest Owning extension manifest.
	 * @param ExtensionAbility  $ability  Declared extension ability.
	 * @return void
	 */
	private function register_ability( ExtensionManifest $manifest, ExtensionAbility $ability ): void {
		$name = $manifest->ability_name( $ability );

		wp_register_ability(
			$name,
			array(
				'label'               => $ability->label,
				'description'         => $ability->description,
				'category'            => self::CATEGORY,
				'input_schema'        => $ability->input_schema,
				'output_schema'       => $ability->output_schema,
				'execute_callback'    => function ( array $input ) use ( $name, $ability ): array|WP_Error {
					return $this->execute( $ability, $input );
				},
				'permission_callback' => function (): bool {
					return $this->can_execute();
				},
				'meta'                => array(
					'annotations'  => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'show_in_rest' => false,
				),
			)
		);
	}

	/**
	 * Checks the fixed, core-owned administrator policy.
	 *
	 * @param string $ability Registered canonical ability name.
	 * @return bool Whether the active user is an administrator.
	 */
	private function can_execute(): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$this->append_receipt( 'denied', 'not_manage_options', 0 );

		return false;
	}

	/**
	 * Rechecks authorization, executes one local callback, and redacts failures.
	 *
	 * @param string               $name    Registered canonical ability name.
	 * @param ExtensionAbility     $ability Declared extension ability.
	 * @param array<string, mixed> $input   Schema-validated ability input.
	 * @return array<string, mixed>|WP_Error Extension result or fixed failure.
	 */
	private function execute( ExtensionAbility $ability, array $input ): array|WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->append_receipt( 'denied', 'not_manage_options', 0 );

			return new WP_Error( 'kiln_extension_denied', __( 'You cannot execute this extension ability.', 'kiln-mcp' ) );
		}

		try {
			$result = ( $ability->executor )( $input );
		} catch ( Throwable ) {
			$this->append_receipt( 'denied', 'extension_execution_failed', 0 );

			return new WP_Error( 'kiln_extension_failed', __( 'The extension ability could not be completed.', 'kiln-mcp' ) );
		}

		if ( ! is_array( $result ) ) {
			$this->append_receipt( 'denied', 'extension_execution_failed', 0 );

			return new WP_Error( 'kiln_extension_failed', __( 'The extension ability could not be completed.', 'kiln-mcp' ) );
		}

		$this->append_receipt( 'allowed', 'manage_options', 1 );

		return $result;
	}

	/**
	 * Appends a bounded receipt that excludes extension data and fault details.
	 *
		 * @param string $decision         Allowed or denied.
	 * @param string $capability_class Redacted policy outcome.
	 * @param int    $result_count     Fixed result count.
	 * @return void
	 */
	private function append_receipt( string $ability, string $decision, string $capability_class, int $result_count ): void {
		$this->audit_store->append( AuditReceipt::create( $ability, $decision, $capability_class, $result_count, gmdate( 'c' ) ) );
	}
}
