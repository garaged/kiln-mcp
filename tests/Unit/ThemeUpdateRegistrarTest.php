<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use KilnMcp\Infrastructure\WordPress\AuditReceiptStore;
use KilnMcp\Infrastructure\WordPress\ThemeApprovalStore;
use KilnMcp\Infrastructure\WordPress\ThemeUpdateRegistrar;
use PHPUnit\Framework\TestCase;

final class ThemeUpdateRegistrarTest extends TestCase {
	protected function setUp(): void {
		unset( $GLOBALS['kiln_mcp_test_registered_abilities'] );
	}

	public function test_it_registers_one_closed_theme_update_ability(): void {
		$registrar = new ThemeUpdateRegistrar(
			new AuditReceiptStore(),
			new ThemeApprovalStore()
		);

		$registrar->register_ability();

		$registered = $GLOBALS['kiln_mcp_test_registered_abilities'];
		self::assertIsArray( $registered );
		self::assertArrayHasKey( AuditReceipt::UPDATE_THEME_ABILITY_NAME, $registered );

		/** @var array<string, array<string, mixed>> $registered */
		$ability = $registered[ AuditReceipt::UPDATE_THEME_ABILITY_NAME ];
		/** @var array<string, mixed> $ability */
		self::assertSame( 'kiln-theme-management', $ability['category'] );
		self::assertIsArray( $ability['input_schema'] );
		/** @var array<string, mixed> $input_schema */
		$input_schema = $ability['input_schema'];
		self::assertFalse( $input_schema['additionalProperties'] );
		self::assertSame(
			array( 'target', 'identity', 'expected_active_stylesheet', 'approval_token' ),
			$input_schema['required']
		);
	}
}
