<?php
/**
 * Fail-closed scanner for a local WordPress-theme ZIP.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ThemeManagement;

use ZipArchive;

/**
 * Inspects archive structure and PHP members without retaining their contents.
 */
final class ThemeArchiveScanner {
	/**
	 * Returns only an admission decision and fixed redacted refusal category.
	 *
	 * @param string $archive_path Internal administrator-upload staging path.
	 * @return array{accepted: bool, reason: 'archive_entry_limit'|'archive_member_too_large'|'archive_open'|'archive_path'|'archive_ratio'|'archive_size'|'archive_symlink'|'empty_archive'|'php_too_large'|'php_backtick_execution'|'php_dynamic_include'|'php_execution'|'php_obfuscation'|'php_preg_replace_e'|'unexpected_executable'|'zip_unavailable'|null} Fixed redacted decision.
	 */
	public static function scan( string $archive_path ): array {
		if ( ! class_exists( ZipArchive::class ) ) {
			return self::refused( 'zip_unavailable' );
		}

		$archive_size = filesize( $archive_path );
		if ( false === $archive_size || 0 >= $archive_size || ThemeArchivePolicy::MAX_ARCHIVE_BYTES < $archive_size ) {
			return self::refused( 'archive_size' );
		}

		$archive = new ZipArchive();
		if ( true !== $archive->open( $archive_path, ZipArchive::CHECKCONS ) ) {
			return self::refused( 'archive_open' );
		}

		try {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- ZipArchive public API.
			$file_count = $archive->numFiles;
			if ( 0 === $file_count ) {
				return self::refused( 'empty_archive' );
			}

			if ( ThemeArchivePolicy::MAX_ENTRY_COUNT < $file_count ) {
				return self::refused( 'archive_entry_limit' );
			}

			$total_uncompressed = 0;
			for ( $index = 0; $index < $file_count; $index++ ) {
				$stat = $archive->statIndex( $index );
				if ( false === $stat || ! isset( $stat['name'], $stat['size'], $stat['comp_size'] ) || ! is_string( $stat['name'] ) || ! is_int( $stat['size'] ) || ! is_int( $stat['comp_size'] ) ) {
					return self::refused( 'archive_open' );
				}

				$attributes       = 0;
				$operating_system = 0;
				$is_link          = $archive->getExternalAttributesIndex( $index, $operating_system, $attributes ) && 0120000 === ( ( $attributes >> 16 ) & 0170000 );
				$reason           = ThemeArchivePolicy::entry_rejection_reason( $stat['name'], $stat['size'], $stat['comp_size'], $is_link );
				if ( null !== $reason ) {
					return self::refused( $reason );
				}

				$total_uncompressed += $stat['size'];
				if ( ThemeArchivePolicy::MAX_UNCOMPRESSED_BYTES < $total_uncompressed ) {
					return self::refused( 'archive_member_too_large' );
				}

				if ( ! ThemeArchivePolicy::requires_php_scan( $stat['name'] ) ) {
					continue;
				}

				if ( ThemeArchivePolicy::MAX_MEMBER_BYTES < $stat['size'] ) {
					return self::refused( 'php_too_large' );
				}

				$source = $archive->getFromIndex( $index );
				if ( ! is_string( $source ) ) {
					return self::refused( 'archive_open' );
				}

				$php_reason = ThemePhpSecurityScanner::rejection_reason( $source );
				if ( null !== $php_reason ) {
					return self::refused( 'php_' . $php_reason );
				}
			}
		} finally {
			$archive->close();
		}

		return array(
			'accepted' => true,
			'reason'   => null,
		);
	}

	/**
	 * Builds a fixed scan refusal without source, member, or filesystem detail.
	 *
	 * @param 'archive_entry_limit'|'archive_member_too_large'|'archive_open'|'archive_path'|'archive_ratio'|'archive_size'|'archive_symlink'|'empty_archive'|'php_too_large'|'php_backtick_execution'|'php_dynamic_include'|'php_execution'|'php_obfuscation'|'php_preg_replace_e'|'unexpected_executable'|'zip_unavailable' $reason Fixed refusal category.
	 * @return array{accepted: bool, reason: string} Fixed refusal record.
	 */
	private static function refused( string $reason ): array {
		return array(
			'accepted' => false,
			'reason'   => $reason,
		);
	}
}
