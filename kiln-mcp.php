<?php
/**
 * Plugin Name: Kiln MCP
 * Plugin URI: https://github.com/garaged/kiln-mcp
 * Description: Fully FLOSS, self-hosted, security-first MCP development tools for WordPress.
 * Version: 0.1.0-dev
 * Requires at least: 6.9
 * Requires PHP: 8.2
 * Requires Plugins: mcp-adapter
 * Author: Kiln MCP contributors
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kiln-mcp
 *
 * @package KilnMcp
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

( new KilnMcp\Application\Plugin( '0.1.0-dev' ) )->register();
