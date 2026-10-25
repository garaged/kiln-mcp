<?php
/**
 * Hash-only record contract for one local theme-management approval.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ThemeManagement;

use InvalidArgumentException;

/**
 * Builds and verifies an exact administrator approval without persisting its raw token.
 */
final class ThemeApproval {
	public const TTL_SECONDS = 600;

	/**
	 * Creates a fixed-shape theme approval record.
	 *
	 * @param string $action                  Exact M13 action.
	 * @param string $target                  Stylesheet or WordPress.org slug.
	 * @param string $artifact_or_version     SHA-256 artifact digest or installed version.
	 * @param string $active_stylesheet       Current active stylesheet before the action.
	 * @param string $approval_token          Raw one-time token displayed locally.
	 * @param int    $issued_at               Unix issuance time.
	 * @return array<string, int|string|null> Persistable hash-only record.
	 */
	public static function create( string $action, string $target, string $artifact_or_version, string $active_stylesheet, string $approval_token, int $issued_at ): array {
		if ( ! ThemeAction::is_supported( $action )
			|| ! self::is_identifier( $target )
			|| ! self::is_identifier( $active_stylesheet )
			|| 1 !== preg_match( '/\\A[a-f0-9]{64}\\z/D', $artifact_or_version )
			|| 1 !== preg_match( '/\\A[a-f0-9]{64}\\z/D', $approval_token )
			|| 0 >= $issued_at ) {
			throw new InvalidArgumentException( 'Theme approval binding is invalid.' );
		}

		return array(
			'action'              => $action,
			'target'              => $target,
			'artifact_or_version' => $artifact_or_version,
			'active_stylesheet'   => $active_stylesheet,
			'token_hash'          => hash( 'sha256', $approval_token ),
			'expires_at'          => $issued_at + self::TTL_SECONDS,
			'consumed_at'         => null,
		);
	}

	/**
	 * Checks one current, unconsumed approval against its exact operation binding.
	 *
	 * @param array<string, mixed> $record              Stored approval.
	 * @param string               $action              Requested action.
	 * @param string               $target              Requested target.
	 * @param string               $artifact_or_version Requested artifact/version digest.
	 * @param string               $active_stylesheet   Requested pre-action active stylesheet.
	 * @param string               $approval_token      Caller-supplied raw token.
	 * @param int                  $now                 Unix decision time.
	 * @return bool Whether the record is an exact active match.
	 */
	public static function matches( array $record, string $action, string $target, string $artifact_or_version, string $active_stylesheet, string $approval_token, int $now ): bool {
		$expected_keys = array( 'action', 'target', 'artifact_or_version', 'active_stylesheet', 'token_hash', 'expires_at', 'consumed_at' );

		if ( array_keys( $record ) !== $expected_keys
			|| ! is_string( $record['action'] )
			|| ! is_string( $record['target'] )
			|| ! is_string( $record['artifact_or_version'] )
			|| ! is_string( $record['active_stylesheet'] )
			|| ! is_string( $record['token_hash'] )
			|| ! is_int( $record['expires_at'] )
			|| ( ! is_int( $record['consumed_at'] ) && null !== $record['consumed_at'] )
			|| null !== $record['consumed_at']
			|| $now >= $record['expires_at'] ) {
			return false;
		}

		return hash_equals( $record['action'], $action )
			&& hash_equals( $record['target'], $target )
			&& hash_equals( $record['artifact_or_version'], $artifact_or_version )
			&& hash_equals( $record['active_stylesheet'], $active_stylesheet )
			&& hash_equals( $record['token_hash'], hash( 'sha256', $approval_token ) );
	}

	/**
	 * Admits the WordPress stylesheet and WordPress.org slug character subset.
	 *
	 * @param string $identifier Candidate identifier.
	 * @return bool Whether it is a bounded identifier.
	 */
	private static function is_identifier( string $identifier ): bool {
		return 1 === preg_match( '/\\A[a-z0-9][a-z0-9_-]{0,99}\\z/D', $identifier );
	}
}
