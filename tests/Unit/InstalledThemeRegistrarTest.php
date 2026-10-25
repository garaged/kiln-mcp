<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use KilnMcp\Infrastructure\WordPress\AuditReceiptStore;
use KilnMcp\Infrastructure\WordPress\InstalledThemeRegistrar;
use PHPUnit\Framework\TestCase;

final class InstalledThemeRegistrarTest extends TestCase {
	protected function setUp(): void {
		unset( $GLOBALS['kiln_mcp_test_registered_abilities'] );
	}

	public function test_it_registers_one_closed_read_only_inventory_ability(): void {
		$registrar = new InstalledThemeRegistrar( new AuditReceiptStore() );

		$registrar->register_ability();

		$registered = $GLOBALS['kiln_mcp_test_registered_abilities'];
		self::assertIsArray( $registered );
		self::assertArrayHasKey( AuditReceipt::LIST_INSTALLED_THEMES_ABILITY_NAME, $registered );

		$ability = $registered[ AuditReceipt::LIST_INSTALLED_THEMES_ABILITY_NAME ];
		self::assertSame( 'object', $ability['input_schema']['type'] );
		self::assertFalse( $ability['input_schema']['additionalProperties'] );
		self::assertTrue( $ability['meta']['annotations']['readonly'] );
		self::assertSame( 20, $ability['output_schema']['properties']['items']['maxItems'] );
	}
}
