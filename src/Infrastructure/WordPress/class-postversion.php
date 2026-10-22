<?php
/**
 * Stable opaque post modification-token construction.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use InvalidArgumentException;

/**
 * Builds a non-secret token from a post's observable version fields.
 */
final class PostVersion {
	public const META_KEY = '_kiln_mcp_draft_revision';

	/**
	 * Builds a fixed-length token suitable for optimistic concurrency checks.
	 *
	 * @param int    $post_id           Positive post identifier.
	 * @param string $modified_gmt      WordPress GMT modification timestamp.
	 * @param string $revision          Locally refreshed opaque revision value.
	 * @return string A deterministic 64-character hexadecimal token.
	 * @throws InvalidArgumentException When input does not identify a post version.
	 */
	public static function token( int $post_id, string $modified_gmt, string $revision ): string {
		if ( 1 > $post_id || '' === $modified_gmt ) {
			throw new InvalidArgumentException( 'A post version requires an identifier and modification time.' );
		}

		return hash( 'sha256', $post_id . "\0" . $modified_gmt . "\0" . $revision );
	}
}
