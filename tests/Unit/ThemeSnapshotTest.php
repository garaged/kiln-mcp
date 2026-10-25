<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Capability\ThemeManagement\ThemeSnapshot;
use PHPUnit\Framework\TestCase;

final class ThemeSnapshotTest extends TestCase {
	public function test_it_builds_only_allow_listed_theme_inventory_fields(): void {
		$record = ThemeSnapshot::installed(
			'Candidate theme',
			'candidate-theme',
			'1.2.3',
			false,
			true,
			true
		);

		self::assertSame(
			array(
				'name',
				'stylesheet',
				'version',
				'active',
				'has_parent',
				'update',
				'identity',
			),
			array_keys( $record )
		);
		self::assertSame( hash( 'sha256', '1.2.3' ), $record['identity'] );
		self::assertArrayNotHasKey( 'path', $record );
		self::assertArrayNotHasKey( 'source', $record );
		self::assertArrayNotHasKey( 'url', $record );
	}
}
