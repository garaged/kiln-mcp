<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Capability\ThemeManagement\ThemeArchiveScanner;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ThemeArchiveScannerTest extends TestCase {
	/** @var array<int, string> */
	private array $archives = array();

	protected function tearDown(): void {
		foreach ( $this->archives as $archive ) {
			if ( is_file( $archive ) ) {
				unlink( $archive );
			}
		}
	}

	public function test_it_allows_a_small_normal_theme_archive(): void {
		$archive = $this->archive(
			array(
				'fixture-theme/style.css' => '/* Theme Name: Fixture */',
				'fixture-theme/functions.php' => '<?php add_action( "after_setup_theme", "fixture_setup" );',
			)
		);

		self::assertNull( ThemeArchiveScanner::rejection_reason( $archive ) );
	}

	public function test_it_refuses_path_traversal_and_unsupported_executables_without_disclosure(): void {
		$traversal = $this->archive( array( '../escape.php' => '<?php echo "x";' ) );
		$binary    = $this->archive( array( 'fixture-theme/run.sh' => '#!/bin/sh' ) );

		self::assertSame( 'archive_path', ThemeArchiveScanner::rejection_reason( $traversal ) );
		self::assertSame( 'archive_file_type', ThemeArchiveScanner::rejection_reason( $binary ) );
	}

	public function test_it_refuses_high_risk_php_with_a_fixed_redacted_reason(): void {
		$archive = $this->archive(
			array(
				'fixture-theme/style.css' => '/* Theme Name: Fixture */',
				'fixture-theme/functions.php' => '<?php eval( $payload );',
			)
		);

		self::assertSame( 'php_high_risk', ThemeArchiveScanner::rejection_reason( $archive ) );
	}

	/**
	 * Creates one disposable ZIP fixture.
	 *
	 * @param array<string, string> $members Member names and contents.
	 * @return string ZIP path.
	 */
	private function archive( array $members ): string {
		if ( ! class_exists( ZipArchive::class ) ) {
			self::markTestSkipped( 'The PHP ZIP extension is required for theme archive scanning.' );
		}

		$path = tempnam( sys_get_temp_dir(), 'kiln-theme-' );
		self::assertNotFalse( $path );
		$this->archives[] = $path;

		$archive = new ZipArchive();
		self::assertTrue( $archive->open( $path, ZipArchive::OVERWRITE ) );
		foreach ( $members as $name => $content ) {
			self::assertTrue( $archive->addFromString( $name, $content ) );
		}
		self::assertTrue( $archive->close() );

		return $path;
	}
}
