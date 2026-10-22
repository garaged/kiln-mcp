<?php

declare(strict_types=1);

class WP_Post {
	public int $ID;
	public string $post_title;
	public string $post_status;
	public string $post_type;
	public string $post_password;
	public string $post_content;
	public int $post_author;
	public string $post_modified_gmt;
}

class WP_Error {
	public string $code;
	public string $message;

	/**
	 * @param string $code Error code.
	 * @param string $message Error message.
	 */
	public function __construct( string $code = '', string $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}
}

/**
 * Static-analysis-only declarations for WordPress runtime APIs.
 *
 * @param callable $callback Callback registered for the hook.
 * @return void
 */
function add_action( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
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
function current_user_can( string $capability, mixed ...$args ): bool {
	return false;
}

/**
 * @return int
 */
function get_current_user_id(): int {
	return 0;
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

/**
 * @param array<string, int|string> $args Query arguments.
 * @return array<int, WP_Post>
 */
function get_posts( array $args ): array {
	return array();
}

/**
 * @param WP_Post $post Post returned by query.
 * @return string
 */
function get_post_modified_time( string $format, bool $gmt, WP_Post $post ): string {
	return '';
}

/**
 * @param WP_Post $post Post returned by query.
 * @return string
 */
function get_permalink( WP_Post $post ): string {
	return '';
}

/**
 * @param mixed $default Default value.
 * @return mixed
 */
function get_option( string $option, mixed $default = false ): mixed {
	return $default;
}

/**
 * @param mixed $value Option value.
 * @return bool
 */
function update_option( string $option, mixed $value, bool $autoload = false ): bool {
	return true;
}


/**
 * @return WP_Post|null
 */
function get_post( int $post_id ): ?WP_Post {
	if ( 0 === $post_id ) {
		return null;
	}

	return new WP_Post();
}

/**
 * @return string
 */
function strip_shortcodes( string $content ): string {
	return $content;
}

/**
 * @return string
 */
function wp_strip_all_tags( string $content, bool $remove_breaks = false ): string {
	return $content;
}

/**
 * @param array<string, mixed> $postarr Post fields.
 * @return int|WP_Error
 */
function wp_insert_post( array $postarr, bool $wp_error = false ): int|WP_Error {
	return 1;
}

/**
 * @param array<string, mixed> $postarr Post fields.
 * @return int|WP_Error
 */
function wp_update_post( array $postarr, bool $wp_error = false ): int|WP_Error {
	return 1;
}

/**
 * @param array<string, mixed> $value Data to slash.
 * @return array<string, mixed>
 */
function wp_slash( array $value ): array {
	return $value;
}

/**
 * @phpstan-assert-if-true WP_Error $thing
 */
function is_wp_error( mixed $thing ): bool {
	return $thing instanceof WP_Error;
}

function wp_generate_uuid4(): string {
	return '00000000-0000-4000-8000-000000000000';
}

function update_post_meta( int $post_id, string $meta_key, string $meta_value ): bool {
	return true;
}

function get_post_meta( int $post_id, string $meta_key, bool $single = false ): string {
	return '';
}
