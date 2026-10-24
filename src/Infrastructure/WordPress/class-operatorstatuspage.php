<?php
/**
 * Bounded operator compatibility diagnostics.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

/**
 * Renders a local administrator-only, read-only compatibility view.
 */
final class OperatorStatusPage {
	private const PAGE_SLUG = 'kiln-mcp-status';

	/**
	 * Creates the bounded status page.
	 *
	 * @param string $version Kiln plugin version.
	 */
	public function __construct(
		private readonly string $version,
	) {
	}

	/**
	 * Registers the local Tools-page entry.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_management_page(
			__( 'Kiln MCP status', 'kiln-mcp' ),
			__( 'Kiln MCP status', 'kiln-mcp' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Renders only the fixed, non-sensitive compatibility contract.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to view Kiln MCP status.', 'kiln-mcp' ) );
		}

		$adapter_version = defined( 'WP_MCP_VERSION' ) && is_string( WP_MCP_VERSION ) ? WP_MCP_VERSION : null;
		$statuses        = self::statuses(
			$this->version,
			PHP_VERSION,
			function_exists( 'wp_register_ability' ),
			class_exists( 'WP\\MCP\\Core\\McpAdapter' ),
			$adapter_version,
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Kiln MCP status', 'kiln-mcp' ); ?></h1>
			<p><?php esc_html_e( 'This read-only view reports dependency compatibility. It never configures transport, credentials, or permissions.', 'kiln-mcp' ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Check', 'kiln-mcp' ); ?></th>
						<th scope="col"><?php esc_html_e( 'State', 'kiln-mcp' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Value', 'kiln-mcp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $statuses as $status ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $status['label'] ); ?></th>
							<td><?php echo esc_html( $status['state'] ); ?></td>
							<td><?php echo esc_html( $status['value'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><?php esc_html_e( 'Use the operator guide for installation, release verification, and secure STDIO or HTTPS HTTP setup.', 'kiln-mcp' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Builds the allow-listed diagnostic contract without reading WordPress state.
	 *
	 * @param string      $kiln_version            Kiln version.
	 * @param string      $php_version             Active PHP version.
	 * @param bool        $abilities_api_available Whether ability registration exists.
	 * @param bool        $adapter_class_available Whether the official adapter class is loaded.
	 * @param string|null $adapter_version         Official adapter version.
	 * @return array<int, array{label: string, state: string, value: string}> Fixed safe statuses.
	 */
	public static function statuses(
		string $kiln_version,
		string $php_version,
		bool $abilities_api_available,
		bool $adapter_class_available,
		?string $adapter_version,
	): array {
		$php_compatible     = version_compare( $php_version, '8.2.0', '>=' );
		$adapter_compatible = AdapterDependency::is_available(
			$adapter_class_available,
			$abilities_api_available,
			$adapter_version,
		);

		return array(
			array(
				'label' => __( 'Kiln MCP', 'kiln-mcp' ),
				'state' => 'ready',
				'value' => $kiln_version,
			),
			array(
				'label' => __( 'PHP runtime', 'kiln-mcp' ),
				'state' => $php_compatible ? 'ready' : 'blocked',
				'value' => $php_version,
			),
			array(
				'label' => __( 'WordPress Abilities API', 'kiln-mcp' ),
				'state' => $abilities_api_available ? 'ready' : 'blocked',
				'value' => $abilities_api_available ? __( 'available', 'kiln-mcp' ) : __( 'unavailable', 'kiln-mcp' ),
			),
			array(
				'label' => __( 'Official MCP Adapter', 'kiln-mcp' ),
				'state' => $adapter_compatible ? 'ready' : 'blocked',
				'value' => null === $adapter_version ? __( 'not detected', 'kiln-mcp' ) : $adapter_version,
			),
		);
	}
}
