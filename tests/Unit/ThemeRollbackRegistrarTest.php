<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use KilnMcp\Infrastructure\WordPress\AuditReceiptStore;
use KilnMcp\Infrastructure\WordPress\ThemeApprovalStore;
use KilnMcp\Infrastructure\WordPress\ThemeRollbackRegistrar;
use PHPUnit\Framework\TestCase;

final class ThemeRollbackRegistrarTest extends TestCase {
	protected function setUp(): void {
		unset( $GLOBALS['kiln_mcp_test_registered_abilities'] );
	}

	public function test_it_registers_one_closed_approval_gated_rollback_ability(): void {
		$registrar = new ThemeRollbackRegistrar( new AuditReceiptStore(), new ThemeApprovalStore() );

		$registrar->register_ability();

		$registered = $GLOBALS['kiln_mcp_test_registered_abilities'];
		self::assertIsArray( $registered );
		self::assertArrayHasKey( AuditReceipt::ROLLBACK_THEME_ABILITY_NAME, $registered );

		/** @var array<string, array<string, mixed>> $registered */
		$ability = $registered[ AuditReceipt::ROLLBACK_THEME_ABILITY_NAME ];
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
		self::assertIsArray( $ability['output_schema'] );
	}
}
