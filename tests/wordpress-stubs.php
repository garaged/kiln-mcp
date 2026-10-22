<?php

declare(strict_types=1);

/**
 * Static-analysis-only declarations for WordPress runtime APIs.
 *
 * @param callable $callback Callback registered for the hook.
 * @return void
 */
function add_action( string $hook_name, callable $callback ): void {
}

/**
 * @param string               $category Category identifier.
 * @param array<string, mixed> $args     Category configuration.
 * @return void
 */
function wp_register_ability_category( string $category, array $args ): void {
}

/**
 * @param string               $name Ability identifier.
 * @param array<string, mixed> $args Ability configuration.
 * @return void
 */
function wp_register_ability( string $name, array $args ): void {
}

/**
 * @return bool
 */
function current_user_can( string $capability ): bool {
	return false;
}

/**
 * @return string
 */
function get_bloginfo( string $show ): string {
	return '';
}

/**
 * @return bool
 */
function is_multisite(): bool {
	return false;
}

/**
 * @return string
 */
function get_stylesheet(): string {
	return '';
}

/**
 * @return string
 */
function __( string $text, string $domain = 'default' ): string {
	return $text;
}
