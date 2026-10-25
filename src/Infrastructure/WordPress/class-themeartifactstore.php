<?php
/**
 * Bounded local metadata store for scanned theme ZIP artifacts.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use KilnMcp\Capability\ThemeManagement\ThemeArchiveScanner;

/**
 * Retains only an opaque ID and digest after a locally uploaded archive passes.
 */
final class ThemeArtifactStore {
	private const OPTION_NAME   = 'kiln_mcp_theme_artifacts';
	private const DIRECTORY     = 'kiln-mcp-theme-artifacts';
	private const MAX_ARTIFACTS = 10;

	/**
	 * Scans and moves one locally-uploaded ZIP into a controlled private location.
	 *
	 * @param string $uploaded_path Path supplied solely by wp_handle_upload().
	 * @return array{id: string, digest: string}|null Opaque artifact reference or refusal.
	 */
	public function retain( string $uploaded_path ): ?array {
		if ( null !== ThemeArchiveScanner::rejection_reason( $uploaded_path ) ) {
			return null;
		}

		$digest = hash_file( 'sha256', $uploaded_path );
		if ( false === $digest ) {
			return null;
		}

		$directory = $this->directory();
		if ( null === $directory || ! wp_mkdir_p( $directory ) ) {
			return null;
		}

		$this->prune();

		$id          = bin2hex( random_bytes( 16 ) );
		$destination = $directory . '/' . $id . '.zip';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- The upload is moved once into a non-public, deterministic Kiln artifact location before any later theme install.
		if ( ! rename( $uploaded_path, $destination ) ) {
			return null;
		}

		$records   = $this->records();
		$records[] = array(
			'id'         => $id,
			'digest'     => $digest,
			'created_at' => time(),
		);
		$records   = array_slice( $records, -self::MAX_ARTIFACTS );

		if ( ! update_option( self::OPTION_NAME, $records, false ) ) {
			wp_delete_file( $destination );
			return null;
		}

		return array(
			'id'     => $id,
			'digest' => $digest,
		);
	}

	/**
	 * Returns the private package path only for a matching persisted artifact.
	 *
	 * @param string $id     Opaque artifact identifier.
	 * @param string $digest Expected package digest.
	 * @return string|null Local path or null when no current exact artifact exists.
	 */
	public function path_for( string $id, string $digest ): ?string {
		foreach ( $this->records() as $record ) {
			if ( hash_equals( $record['id'], $id ) && hash_equals( $record['digest'], $digest ) ) {
				$path = $this->directory();
				if ( null === $path ) {
					return null;
				}

				$candidate = $path . '/' . $id . '.zip';
				if ( is_file( $candidate ) && hash_equals( $digest, (string) hash_file( 'sha256', $candidate ) ) ) {
					return $candidate;
				}
			}
		}

		return null;
	}

	/**
	 * Drops expired bounded records and their deterministic local package files.
	 *
	 * @return void
	 */
	private function prune(): void {
		$records = array_slice( $this->records(), -self::MAX_ARTIFACTS + 1 );
		$kept    = array();
		$ids     = array();

		foreach ( $records as $record ) {
			$kept[] = $record;
			$ids[]  = $record['id'];
		}

		foreach ( $this->records() as $record ) {
			if ( in_array( $record['id'], $ids, true ) ) {
				continue;
			}

			$directory = $this->directory();
			if ( null !== $directory ) {
				wp_delete_file( $directory . '/' . $record['id'] . '.zip' );
			}
		}

		if ( ! update_option( self::OPTION_NAME, $kept, false ) ) {
			return;
		}
	}

	/**
	 * Resolves one controlled artifacts directory without retaining a path in options.
	 *
	 * @return string|null Local controlled directory or null.
	 */
	private function directory(): ?string {
		$upload_directory = wp_upload_dir();
		if ( ! is_array( $upload_directory )
			|| ! isset( $upload_directory['basedir'] )
			|| ! is_string( $upload_directory['basedir'] )
			|| '' === $upload_directory['basedir'] ) {
			return null;
		}

		return $upload_directory['basedir'] . '/' . self::DIRECTORY;
	}

	/**
	 * Returns only complete, bounded persisted artifact metadata.
	 *
	 * @return array<int, array{id: string, digest: string, created_at: int}> Artifact records.
	 */
	private function records(): array {
		$stored = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}

		$records = array();
		foreach ( $stored as $record ) {
			if ( ! is_array( $record )
				|| array_keys( $record ) !== array( 'id', 'digest', 'created_at' )
				|| ! is_string( $record['id'] )
				|| ! is_string( $record['digest'] )
				|| ! is_int( $record['created_at'] )
				|| 1 !== preg_match( '/\\A[a-f0-9]{32}\\z/D', $record['id'] )
				|| 1 !== preg_match( '/\\A[a-f0-9]{64}\\z/D', $record['digest'] ) ) {
				continue;
			}

			$records[] = array(
				'id'         => $record['id'],
				'digest'     => $record['digest'],
				'created_at' => $record['created_at'],
			);
		}

		return $records;
	}
}
