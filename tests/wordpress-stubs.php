<?php

declare(strict_types=1);

/**
 * Static-analysis-only declarations for the WordPress hooks used by the
 * composition root. WordPress provides these functions at runtime.
 *
 * @param callable $callback Callback registered for the hook.
 * @return true
 */
function add_action( string $hook_name, callable $callback ): bool {
	return true;
}
