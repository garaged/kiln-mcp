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

		/** @var array<string, array<string, mixed>> $registered */
		$ability = $registered[ AuditReceipt::LIST_INSTALLED_THEMES_ABILITY_NAME ];
		/** @var array<string, mixed> $ability */
		self::assertIsArray( $ability['input_schema'] );
		/** @var array<string, mixed> $input_schema */
		$input_schema = $ability['input_schema'];
		self::assertSame( 'object', $input_schema['type'] );
		self::assertFalse( $input_schema['additionalProperties'] );
		self::assertIsArray( $ability['meta'] );
		self::assertIsArray( $ability['output_schema'] );
	}
}
