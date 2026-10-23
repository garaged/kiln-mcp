<?php
/**
 * Registers Kiln's explicit official-adapter server.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

/**
 * Keeps MCP server membership and transport admission core-owned.
 */
final class KilnMcpServerRegistrar {
	public const SERVER_ID = 'kiln-mcp';

	/**
	 * Registers the official adapter server.
	 *
	 * @param object $adapter Official MCP Adapter instance.
	 * @return void
	 */
	public function register_server( object $adapter ): void {
		if ( ! is_callable( array( $adapter, 'create_server' ) ) ) {
			return;
		}

		call_user_func_array(
			array( $adapter, 'create_server' ),
			array(
				self::SERVER_ID,
				'kiln-mcp',
				'mcp',
				'Kiln MCP',
				'Security-first, self-hosted WordPress development capabilities.',
				'0.1.0',
				array( '\\WP\\MCP\\Transport\\HttpTransport' ),
				'\\WP\\MCP\\Infrastructure\\ErrorHandling\\ErrorLogMcpErrorHandler',
				'\\WP\\MCP\\Infrastructure\\Observability\\NullMcpObservabilityHandler',
				$this->ability_names( $this->registered_abilities() ),
				array(),
				array(),
				array( $this, 'can_connect' ),
			)
		);
	}

	/**
	 * Allows only authenticated authors, editors, and administrators into the server.
	 *
	 * Individual ability callbacks retain their exact authorization checks.
	 *
	 * @return bool Whether the active WordPress user may reach the server.
	 */
	public function can_connect(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Produces the explicit Kiln capability allow-list.
	 *
	 * @param array<mixed> $registered_abilities Ability registry keyed by name.
	 * @return array<int, string> Sorted admitted capability names.
	 */
	public function ability_names( array $registered_abilities ): array {
		$names = self::core_ability_names();

		foreach ( array_keys( $registered_abilities ) as $name ) {
			if ( ! is_string( $name ) || ( ! str_starts_with( $name, 'kiln/' ) && ! str_starts_with( $name, 'kiln-ext/' ) ) ) {
				continue;
			}

			$names[] = $name;
		}

		$names = array_values( array_unique( $names ) );
		sort( $names, SORT_STRING );

		return $names;
	}

	/**
	 * Applies the pure transport-admission rule.
	 *
	 * @param bool $authenticated Whether WordPress authenticated the request.
	 * @param bool $can_edit_posts Whether the user may author posts.
	 * @return bool Whether transport admission is allowed.
	 */
	public static function is_transport_admitted( bool $authenticated, bool $can_edit_posts ): bool {
		return $authenticated && $can_edit_posts;
	}

	/**
	 * Returns the fixed core capability set.
	 *
	 * @return array<int, string> Kiln core ability names.
	 */
	public static function core_ability_names(): array {
		return array(
			'kiln/create-category',
			'kiln/create-post-draft',
			'kiln/create-tag',
			'kiln/list-categories',
			'kiln/list-navigation-menus',
			'kiln/list-posts',
			'kiln/list-supported-post-types',
			'kiln/list-supported-taxonomies',
			'kiln/list-tags',
			'kiln/publish-post',
			'kiln/read-post',
			'kiln/restore-post',
			'kiln/schedule-post-publication',
			'kiln/site-health-summary',
			'kiln/trash-post',
			'kiln/update-post-draft',
		);
	}

	/**
	 * Reads the WordPress ability registry when it is available.
	 *
	 * @return array<mixed> Registered abilities keyed by name.
	 */
	private function registered_abilities(): array {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return array();
		}

		return wp_get_abilities();
	}
}
