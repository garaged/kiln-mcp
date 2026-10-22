<?php
/**
 * Closed input contract for post discovery.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\PostDiscovery;

use InvalidArgumentException;

/**
 * Validates the narrow post-discovery request shape.
 */
final class PostDiscoveryRequest {
	/**
	 * Creates an immutable validated discovery request.
	 *
	 * @param string $status Requested public or draft status.
	 * @param int    $limit  Maximum result count.
	 * @param int    $page   One-based page number.
	 */
	private function __construct(
		public readonly string $status,
		public readonly int $limit,
		public readonly int $page,
	) {
	}

	/**
	 * Validates a complete closed discovery input object.
	 *
	 * @param array<string, mixed> $input MCP ability input.
	 * @return self A validated request.
	 * @throws InvalidArgumentException When the input is not an allowed bounded shape.
	 */
	public static function from_input( array $input ): self {
		$allowed_keys = array( 'status', 'limit', 'page' );
		if ( array_diff( array_keys( $input ), $allowed_keys ) || count( $input ) !== count( $allowed_keys ) ) {
			throw new InvalidArgumentException( 'Post discovery input has an unsupported shape.' );
		}

		$status = $input['status'] ?? null;
		$limit  = $input['limit'] ?? null;
		$page   = $input['page'] ?? null;

		if ( ! is_string( $status ) || ! in_array( $status, array( 'publish', 'draft' ), true ) ) {
			throw new InvalidArgumentException( 'Post discovery status is invalid.' );
		}

		if ( ! is_int( $limit ) || 1 > $limit || 20 < $limit ) {
			throw new InvalidArgumentException( 'Post discovery limit is invalid.' );
		}

		if ( ! is_int( $page ) || 1 > $page ) {
			throw new InvalidArgumentException( 'Post discovery page is invalid.' );
		}

		return new self( $status, $limit, $page );
	}

	/**
	 * Converts a validated request into bounded WordPress query arguments.
	 *
	 * @return array<string, int|string>
	 */
	public function to_query_arguments(): array {
		return array(
			'post_type'      => 'post',
			'post_status'    => $this->status,
			'posts_per_page' => $this->limit,
			'paged'          => $this->page,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		);
	}
}
