<?php
/**
 * Plugin Name: Kiln MCP Test HTTP Authentication
 * Description: Enables Application Password authentication only in the disposable HTTP test site.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

add_filter( 'wp_is_application_passwords_available', '__return_true' );
