<?php
/**
 * WordPress composition root.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Application;

use KilnMcp\Domain\Policy\ToolPolicy;

/**
 * Composition root. It deliberately publishes no MCP capabilities in Milestone 0.
 */
final class Plugin {
	/**
	 * Registers only foundation-level hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'bootstrap' ) );
	}

	/**
	 * Creates the policy nucleus. Future capability modules receive policy through
	 * this composition root; they must not construct competing policy stores.
	 *
	 * @return void
	 */
	public function bootstrap(): void {
		ToolPolicy::deny_by_default();
	}
}
