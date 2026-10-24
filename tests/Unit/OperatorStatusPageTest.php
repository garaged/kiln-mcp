<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Infrastructure\WordPress\OperatorStatusPage;
use PHPUnit\Framework\TestCase;

final class OperatorStatusPageTest extends TestCase {
	public function test_it_reports_only_the_fixed_supported_compatibility_contract(): void {
		$statuses = OperatorStatusPage::statuses( '0.1.0-dev', '8.2.24', true, true, '0.7.1' );

		self::assertSame(
			array(
				array(
					'label' => 'Kiln MCP',
					'state' => 'ready',
					'value' => '0.1.0-dev',
				),
				array(
					'label' => 'PHP runtime',
					'state' => 'ready',
					'value' => '8.2.24',
				),
				array(
					'label' => 'WordPress Abilities API',
					'state' => 'ready',
					'value' => 'available',
				),
				array(
					'label' => 'Official MCP Adapter',
					'state' => 'ready',
					'value' => '0.7.1',
				),
			),
			$statuses,
		);
	}

	public function test_it_fails_closed_for_an_absent_or_incompatible_dependency_without_sensitive_diagnostics(): void {
		$statuses = OperatorStatusPage::statuses( '0.1.0-dev', '8.1.27', false, false, null );
		$encoded  = serialize( $statuses );

		self::assertSame( 'blocked', $statuses[1]['state'] );
		self::assertSame( 'blocked', $statuses[2]['state'] );
		self::assertSame( 'blocked', $statuses[3]['state'] );
		self::assertSame( 'not detected', $statuses[3]['value'] );
		self::assertStringNotContainsString( 'password', strtolower( $encoded ) );
		self::assertStringNotContainsString( 'http://', strtolower( $encoded ) );
		self::assertStringNotContainsString( 'https://', strtolower( $encoded ) );
		self::assertStringNotContainsString( '/var/', strtolower( $encoded ) );
	}
}
