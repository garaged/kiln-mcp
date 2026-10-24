<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Application\Plugin;
use KilnMcp\Infrastructure\WordPress\OperatorStatusPage;
use PHPUnit\Framework\TestCase;

final class PluginOperatorStatusTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['kiln_mcp_test_actions'] = array();
	}

	public function test_it_registers_operator_status_even_when_the_adapter_dependency_is_unavailable(): void {
		( new Plugin( '0.1.0-dev' ) )->bootstrap();

		$actions = $GLOBALS['kiln_mcp_test_actions'];
		if ( ! is_array( $actions ) ) {
			self::fail( 'Expected the WordPress action recorder to retain actions.' );
		}

		$matches = array_filter(
			$actions,
			static fn( mixed $action ): bool => is_array( $action )
				&& 'admin_menu' === ( $action['hook'] ?? null )
				&& is_array( $action['callback'] ?? null )
				&& ( $action['callback'][0] ?? null ) instanceof OperatorStatusPage
				&& 'register_page' === ( $action['callback'][1] ?? null ),
		);

		self::assertCount( 1, $matches );
	}
}
