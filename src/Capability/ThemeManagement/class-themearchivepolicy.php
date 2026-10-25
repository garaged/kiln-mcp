<?php
/**
 * Pure bounds and member-admission policy for a local theme ZIP.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ThemeManagement;

/**
 * Keeps archive checks deterministic and independent of filesystem paths.
 */
final class ThemeArchivePolicy {
	public const MAX_ARCHIVE_BYTES      = 26214400;
	public const MAX_ENTRY_COUNT        = 1000;
	public const MAX_UNCOMPRESSED_BYTES = 52428800;
	public const MAX_MEMBER_BYTES       = 5242880;
	public const MAX_EXPANSION_RATIO    = 100;

	/**
	 * Returns the fixed, redacted reason an archive member is not admitted.
	 *
	 * @param string $member_name       ZIP member name.
	 * @param int    $uncompressed_size Member size after extraction.
	 * @param int    $compressed_size   Member stored size.
	 * @param bool   $is_link           Whether the ZIP metadata identifies a link.
	 * @return 'archive_member_too_large'|'archive_path'|'archive_ratio'|'archive_symlink'|'unexpected_executable'|null Fixed refusal category.
	 */
	public static function entry_rejection_reason( string $member_name, int $uncompressed_size, int $compressed_size, bool $is_link ): ?string {
		if ( $is_link ) {
			return 'archive_symlink';
		}

		if ( self::is_unsafe_path( $member_name ) ) {
			return 'archive_path';
		}

		if ( 0 > $uncompressed_size || 0 > $compressed_size || self::MAX_MEMBER_BYTES < $uncompressed_size ) {
			return 'archive_member_too_large';
		}

		if ( self::MAX_EXPANSION_RATIO < $uncompressed_size / max( 1, $compressed_size ) ) {
			return 'archive_ratio';
		}

		if ( self::is_unexpected_executable( $member_name ) ) {
			return 'unexpected_executable';
		}

		return null;
	}

	/**
	 * Identifies PHP-bearing members that require static source inspection.
	 *
	 * @param string $member_name ZIP member name already admitted by this policy.
	 * @return bool Whether source must be scanned.
	 */
	public static function requires_php_scan( string $member_name ): bool {
		return 1 === preg_match( '/\\.(?:inc|php[0-9]?|phtml)\\z/iD', $member_name );
	}

	/**
	 * Refuses traversal, absolute, Windows-drive, or malformed archive paths.
	 *
	 * @param string $member_name ZIP member name.
	 * @return bool Whether the name is unsafe.
	 */
	private static function is_unsafe_path( string $member_name ): bool {
		if ( '' === $member_name || str_contains( $member_name, "\\0" ) || str_starts_with( $member_name, '/' ) || str_starts_with( $member_name, '\\\\' ) || 1 === preg_match( '/\\A[a-z]:/iD', $member_name ) ) {
			return true;
		}

		$parts = explode( '/', rtrim( $member_name, '/' ) );
		foreach ( $parts as $part ) {
			if ( '' === $part || '.' === $part || '..' === $part || str_contains( $part, '\\\\' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Rejects payloads WordPress themes do not need to contain.
	 *
	 * @param string $member_name ZIP member name.
	 * @return bool Whether the member is an unexpected executable/configuration payload.
	 */
	private static function is_unexpected_executable( string $member_name ): bool {
		return 1 === preg_match( '/(?:\\.(?:bat|bin|cgi|cmd|com|dll|exe|msi|phar|py|rb|sh|so)|(?:^|\\/)(?:\\.htaccess|\\.user\\.ini))\\z/iD', $member_name );
	}
}
