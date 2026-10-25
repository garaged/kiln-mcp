<?php
/**
 * Private, short-lived metadata store for administrator-uploaded theme archives.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use KilnMcp\Capability\ThemeManagement\ThemeArchiveScanner;

/**
 * Keeps staging locations internal while exposing only opaque artifact identities.
 */
final class ThemeArtifactStore {
	private const OPTION_NAME   = 'kiln_mcp_theme_artifacts';
	private const MAX_ARTIFACTS = 10;
	private const TTL_SECONDS   = 600;

	/**
	 * Scans and retains one internal staging file for a short local approval window.
	 *
	 * @param string $internal_path Local upload staging path; never returned by this class.
	 * @return array{id: string, digest: string}|null Opaque artifact identity or null when refused.
	 */
	public function retain_scanned( string $internal_path ): ?array {
		$this->prune();

		$scan = ThemeArchiveScanner::scan( $internal_path );
		if ( ! $scan['accepted'] ) {
			$this->remove( $internal_path );
			return null;
		}

		$digest = hash_file( 'sha256', $internal_path );
		if ( false === $digest ) {
			$this->remove( $internal_path );
			return null;
		}

		$id = bin2hex( random_bytes( 16 ) );

		$records = $this->records();

		$records[] = array(
			'id'         => $id,
			'digest'     => $digest,
			'path'       => $internal_path,
			'expires_at' => time() + self::TTL_SECONDS,
		);

		$records = array_slice( $records, -self::MAX_ARTIFACTS );
		if ( ! update_option( self::OPTION_NAME, $records, false ) ) {
			$this->remove( $internal_path );
			return null;
		}

		return array(
			'id'     => $id,
			'digest' => $digest,
		);
	}

	/**
	 * Resolves a still-current opaque artifact to its verified internal staging path.
	 *
	 * @param string $id Opaque administrator-provided artifact identity.
	 * @return array{digest: string, path: string}|null Verified internal detail, never MCP output.
	 */
	public function resolve( string $id ): ?array {
		$this->prune();

		foreach ( $this->records() as $record ) {
			if ( ! self::is_valid_record( $record ) || ! hash_equals( $record['id'], $id ) || ! is_file( $record['path'] ) ) {
				continue;
			}

			$digest = hash_file( 'sha256', $record['path'] );
			if ( false === $digest || ! hash_equals( $record['digest'], $digest ) ) {
				$this->discard( $id );
				return null;
			}

			return array(
				'digest' => $record['digest'],
				'path'   => $record['path'],
			);
		}

		return null;
	}

	/**
	 * Removes a consumed artifact and its internal staging file.
	 *
	 * @param string $id Opaque artifact identifier.
	 * @return void
	 */
	public function discard( string $id ): void {
		$records = array();
		foreach ( $this->records() as $record ) {
			if ( self::is_valid_record( $record ) && hash_equals( $record['id'], $id ) ) {
				$this->remove( $record['path'] );
				continue;
			}
			$records[] = $record;
		}

		update_option( self::OPTION_NAME, $records, false );
	}

	/**
	 * Deletes expired or malformed staging records and files.
	 *
	 * @return void
	 */
	private function prune(): void {
		$records = array();
		foreach ( $this->records() as $record ) {
			if ( ! self::is_valid_record( $record ) || time() >= $record['expires_at'] ) {
				if ( is_array( $record ) && isset( $record['path'] ) && is_string( $record['path'] ) ) {
					$this->remove( $record['path'] );
				}
				continue;
			}
			$records[] = $record;
		}

		update_option( self::OPTION_NAME, array_slice( $records, -self::MAX_ARTIFACTS ), false );
	}

	/**
	 * Narrows untrusted option data to the private fixed record shape.
	 *
	 * @param array<string, mixed> $record Candidate record.
	 * @return bool Whether record is structurally valid.
	 */
	private static function is_valid_record( array $record ): bool {
		return array_keys( $record ) === array( 'id', 'digest', 'path', 'expires_at' )
			&& isset( $record['id'], $record['digest'], $record['path'], $record['expires_at'] )
			&& is_string( $record['id'] )
			&& is_string( $record['digest'] )
			&& is_string( $record['path'] )
			&& is_int( $record['expires_at'] )
			&& 1 === preg_match( '/\\A[a-f0-9]{32}\\z/D', $record['id'] )
			&& 1 === preg_match( '/\\A[a-f0-9]{64}\\z/D', $record['digest'] );
	}

	/**
	 * Loads only array-shaped candidate records.
	 *
	 * @return array<int, array<string, mixed>> Candidate records.
	 */
	private function records(): array {
		$records = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $records ) ) {
			return array();
		}

		$candidates = array();
		foreach ( $records as $record ) {
			if ( is_array( $record ) ) {
				$candidates[] = $record;
			}
		}

		return $candidates;
	}

	/**
	 * Best-effort removal of an internal short-lived staging file.
	 *
	 * @param string $path Internal staging path.
	 * @return void
	 */
	private function remove( string $path ): void {
		if ( is_file( $path ) ) {
			wp_delete_file( $path );
		}
	}
}
