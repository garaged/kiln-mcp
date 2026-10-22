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
use KilnMcp\Infrastructure\WordPress\SiteHealthSummaryRegistrar;

/**
 * Composes bounded capabilities after all required WordPress plugins load.
 */
final class Plugin {
	public function __construct(
		private readonly string $version,
	) {
	}

	/**
	 * @return void
	 */
	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'bootstrap' ) );
	}

	/**
	 * Failing the adapter check does not register any Kiln capability.
	 *
	 * @return void
	 */
	public function bootstrap(): void {
		ToolPolicy::deny_by_default();

		if ( ! AdapterDependency::is_available(
			class_exists( 'WP\\MCP\\Core\\McpAdapter' ),
			function_exists( 'wp_register_ability' ),
			defined( 'WP_MCP_VERSION' ) ? WP_MCP_VERSION : null,
		) ) {
			return;
		}

		$registrar = new SiteHealthSummaryRegistrar( new SiteHealthSummary( $this->version ) );

		add_action( 'wp_abilities_api_categories_init', array( $registrar, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $registrar, 'register_ability' ) );
	}
}
