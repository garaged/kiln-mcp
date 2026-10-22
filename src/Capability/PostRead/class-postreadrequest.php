<?php
/**
 * Closed input contract for single-post read.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\PostRead;

use InvalidArgumentException;

/**
 * Validates the exact post identifier accepted by the reader.
 */
final class PostReadRequest {
	/**
	 * Creates an immutable post-read request.
	 *
	 * @param int $post_id Positive WordPress post identifier.
	 */
	private function __construct(
		public readonly int $post_id,
	) {
	}

	/**
	 * Validates a closed post-read input object.
	 *
	 * @param array<string, mixed> $input Ability input.
	 * @return self A validated request.
	 * @throws InvalidArgumentException When the input is not one positive post ID.
	 */
	public static function from_input( array $input ): self {
		if ( array( 'post_id' ) !== array_keys( $input ) || ! is_int( $input['post_id'] ) || 1 > $input['post_id'] ) {
			throw new InvalidArgumentException( 'Post read input must contain one positive post_id.' );
		}

		return new self( $input['post_id'] );
	}
}
