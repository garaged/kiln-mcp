<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Capability\ThemeManagement\ThemeArchivePolicy;
use PHPUnit\Framework\TestCase;

final class ThemeArchivePolicyTest extends TestCase {
	/**
	 * @dataProvider rejected_member_provider
	 *
	 * @param string $member ZIP member name.
	 * @param int    $size Uncompressed member bytes.
	 * @param int    $compressed Compressed member bytes.
	 * @param bool   $link Whether ZIP metadata identifies a link.
	 * @param string $reason Expected fixed rejection category.
	 */
	public function test_it_refuses_unsafe_or_expansive_members( string $member, int $size, int $compressed, bool $link, string $reason ): void {
		self::assertSame( $reason, ThemeArchivePolicy::entry_rejection_reason( $member, $size, $compressed, $link ) );
	}

	/**
	 * @return array<string, array{0: string, 1: int, 2: int, 3: bool, 4: string}>
	 */
	public static function rejected_member_provider(): array {
		return array(
			'traversal' => array( 'theme/../wp-config.php', 10, 10, false, 'archive_path' ),
			'absolute path' => array( '/etc/passwd', 10, 10, false, 'archive_path' ),
			'windows drive' => array( 'C:/temp/payload.php', 10, 10, false, 'archive_path' ),
			'link' => array( 'theme/link', 10, 10, true, 'archive_symlink' ),
			'compression bomb' => array( 'theme/assets/data.json', 101, 1, false, 'archive_ratio' ),
			'executable' => array( 'theme/payload.exe', 10, 10, false, 'unexpected_executable' ),
			'web configuration' => array( 'theme/.htaccess', 10, 10, false, 'unexpected_executable' ),
		);
	}

	public function test_it_admits_bounded_theme_members_and_identifies_php_for_scanning(): void {
		self::assertNull( ThemeArchivePolicy::entry_rejection_reason( 'theme/functions.php', 100, 100, false ) );
		self::assertTrue( ThemeArchivePolicy::requires_php_scan( 'theme/functions.php' ) );
		self::assertTrue( ThemeArchivePolicy::requires_php_scan( 'theme/includes/setup.inc' ) );
		self::assertFalse( ThemeArchivePolicy::requires_php_scan( 'theme/style.css' ) );
	}
}
