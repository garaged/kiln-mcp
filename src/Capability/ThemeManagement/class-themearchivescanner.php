<?php
/**
 * Fail-closed scanner for locally uploaded theme ZIP archives.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ThemeManagement;

use ZipArchive;

/**
 * Checks archive structure and every PHP-bearing member before approval.
 *
 * The fixed result deliberately contains no member name, path, source, or
 * matching scanner detail. This is a guardrail, not a malware certification.
 */
final class ThemeArchiveScanner {
	private const MAX_ENTRIES            = 2000;
	private const MAX_MEMBER_BYTES       = 2097152;
	private const MAX_UNCOMPRESSED_BYTES = 26214400;
	private const MAX_COMPRESSION_RATIO  = 100;
	private const MAX_PHP_SOURCE_BYTES   = 1048576;

	/**
	 * Returns a fixed rejection category, or null only for a fully admitted ZIP.
	 *
	 * @param string $archive_path Local path supplied only by the admin upload path.
	 * @return 'archive_expansion'|'archive_file_type'|'archive_invalid'|'archive_limit'|'archive_link'|'archive_path'|'archive_unavailable'|'php_high_risk'|null Fixed redacted refusal category.
	 */
	public static function rejection_reason( string $archive_path ): ?string {
		if ( '' === $archive_path || ! is_file( $archive_path ) || ! class_exists( ZipArchive::class ) ) {
			return 'archive_unavailable';
		}

		$archive = new ZipArchive();
		if ( true !== $archive->open( $archive_path ) ) {
			return 'archive_invalid';
		}

		try {
			if ( 0 === $archive->count() || self::MAX_ENTRIES < $archive->numFiles ) {
				return 'archive_limit';
			}

			$total_uncompressed = 0;
			$total_compressed   = 0;
			for ( $index = 0; $index < $archive->numFiles; ++$index ) {
				$stat = $archive->statIndex( $index );
				if ( false === $stat
					|| ! isset( $stat['name'], $stat['size'], $stat['comp_size'] )
					|| ! is_string( $stat['name'] )
					|| ! is_int( $stat['size'] )
					|| ! is_int( $stat['comp_size'] ) ) {
					return 'archive_invalid';
				}

				if ( self::is_unsafe_path( $stat['name'] ) ) {
					return 'archive_path';
				}

				if ( self::is_link( $archive, $index ) ) {
					return 'archive_link';
				}

				if ( str_ends_with( $stat['name'], '/' ) ) {
					continue;
				}

				if ( ! self::is_allowed_member( $stat['name'] ) ) {
					return 'archive_file_type';
				}

				if ( 0 > $stat['size'] || self::MAX_MEMBER_BYTES < $stat['size'] ) {
					return 'archive_limit';
				}

				$total_uncompressed += $stat['size'];
				$total_compressed   += $stat['comp_size'];
				if ( self::MAX_UNCOMPRESSED_BYTES < $total_uncompressed ) {
					return 'archive_limit';
				}

				if ( 0 < $total_compressed && self::MAX_COMPRESSION_RATIO * $total_compressed < $total_uncompressed ) {
					return 'archive_expansion';
				}

				if ( self::is_php_member( $stat['name'] ) ) {
					if ( self::MAX_PHP_SOURCE_BYTES < $stat['size'] ) {
						return 'archive_limit';
					}

					$source = $archive->getFromIndex( $index );
					if ( false === $source ) {
						return 'archive_invalid';
					}

					if ( null !== ThemePhpSecurityScanner::rejection_reason( $source ) ) {
						return 'php_high_risk';
					}
				}
			}
		} finally {
			$archive->close();
		}

		return null;
	}

	/**
	 * Rejects paths with traversal, alternate separators, NULs, or absolute roots.
	 *
	 * @param string $name Archive member name.
	 * @return bool Whether the member name is unsafe.
	 */
	private static function is_unsafe_path( string $name ): bool {
		if ( '' === $name
			|| str_contains( $name, "\0" )
			|| str_contains( $name, '\\' )
			|| str_starts_with( $name, '/' ) ) {
			return true;
		}

		foreach ( explode( '/', $name ) as $segment ) {
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Rejects symbolic links when ZIP metadata declares a Unix symlink.
	 *
	 * @param ZipArchive $archive Open archive.
	 * @param int        $index   Member index.
	 * @return bool Whether the member is a link.
	 */
	private static function is_link( ZipArchive $archive, int $index ): bool {
		$operations = 0;
		$attributes = 0;
		if ( ! $archive->getExternalAttributesIndex( $index, $operations, $attributes ) ) {
			return false;
		}

		return 0120000 === ( ( $attributes >> 16 ) & 0170000 );
	}

	/**
	 * Allows the bounded non-executable files expected in a WordPress theme.
	 *
	 * @param string $name Archive member name.
	 * @return bool Whether the file type is admitted.
	 */
	private static function is_allowed_member( string $name ): bool {
		$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

		return in_array(
			$extension,
			array(
				'css',
				'gif',
				'htm',
				'html',
				'jpeg',
				'jpg',
				'json',
				'js',
				'mo',
				'otf',
				'php',
				'png',
				'po',
				'pot',
				'svg',
				'text',
				'txt',
				'ttf',
				'webp',
				'woff',
				'woff2',
			),
			true
		);
	}

	/**
	 * Identifies PHP-bearing files which must receive the source scanner.
	 *
	 * @param string $name Archive member name.
	 * @return bool Whether the member contains PHP source.
	 */
	private static function is_php_member( string $name ): bool {
		return 'php' === strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	}
}
