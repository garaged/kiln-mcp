<?php

declare(strict_types=1);

/**
 * Static-analysis-only declaration for the WordPress hook used by the
 * composition root. WordPress provides this function at runtime.
 *
 * @param callable $callback Callback registered for the hook.
 * @return void
 */
function add_action( string $hook_name, callable $callback ): void {
}
