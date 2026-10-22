<?php
/**
 * WordPress composition root.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Application;

use KilnMcp\Capability\SiteHealthSummary\SiteHealthSummary;
use KilnMcp\Domain\Policy\ToolPolicy;
use KilnMcp\Infrastructure\WordPress\AdapterDependency;
use KilnMcp\Infrastructure\WordPress\AuditReceiptStore;
use KilnMcp\Infrastructure\WordPress\PostDiscoveryRegistrar;
use KilnMcp\Infrastructure\WordPress\SiteHealthSummaryRegistrar;

/**
 * Composes bounded capabilities after all required WordPress plugins load.
 */
final class Plugin {
	/**
	 * Creates the composition root for a specific Kiln version.
	 *
	 * @param string $version Kiln plugin version.
	 */
	public function __construct(
		private readonly string $version,
	) {
	}

	/**
	 * Registers the composition root with WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'bootstrap' ) );
	}

	/**
	 * Creates policy and conditionally wires the supported adapter integration.
	 *
	 * Failing the adapter check does not register any Kiln capability.
	 *
	 * @return void
	 */
	public function bootstrap(): void {
		ToolPolicy::deny_by_default();

		$adapter_version = defined( 'WP_MCP_VERSION' ) && is_string( WP_MCP_VERSION ) ? WP_MCP_VERSION : null;

		if ( ! AdapterDependency::is_available(
			class_exists( 'WP\\MCP\\Core\\McpAdapter' ),
			function_exists( 'wp_register_ability' ),
			$adapter_version,
		) ) {
			return;
		}

		$health_registrar = new SiteHealthSummaryRegistrar( new SiteHealthSummary( $this->version ) );
		$post_registrar   = new PostDiscoveryRegistrar( new AuditReceiptStore() );

		add_action( 'wp_abilities_api_categories_init', array( $health_registrar, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $health_registrar, 'register_ability' ) );
		add_action( 'wp_abilities_api_categories_init', array( $post_registrar, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $post_registrar, 'register_ability' ) );
	}
}
