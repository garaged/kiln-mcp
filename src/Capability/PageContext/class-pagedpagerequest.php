<?php
/**
 * Closed, bounded page-discovery request.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\PageContext;

use InvalidArgumentException;

/**
 * Validates a fixed page metadata query.
 */
final class PagedPageRequest {
	public const MAX_ITEMS = 20;

	/**
	 * Applies the bounded page-context contract.
	 *
	 * @param string $status Admitted page status.
	 * @param int    $limit  Bounded page size.
	 * @param int    $page   One-based page number.
	 */
	private function __construct(
		public readonly string $status,
		public readonly int $limit,
		public readonly int $page,
	) {
	}

	/**
	 * Creates one closed page query.
	 *
	 * @param array<string, mixed> $input Candidate request.
	 * @return self Validated request.
	 * @throws InvalidArgumentException When input is not a closed bounded page query.
	 */
	public static function from_input( array $input ): self {
		if (
			array( 'status', 'limit', 'page' ) !== array_keys( $input )
			|| ! is_string( $input['status'] )
			|| ! is_int( $input['limit'] )
			|| ! is_int( $input['page'] )
			|| ! in_array( $input['status'], array( 'publish', 'draft' ), true )
			|| $input['limit'] < 1
			|| self::MAX_ITEMS < $input['limit']
			|| $input['page'] < 1
		) {
			throw new InvalidArgumentException( 'Invalid page discovery input.' );
		}

		return new self( $input['status'], $input['limit'], $input['page'] );
	}

	/**
	 * Returns fixed WordPress query arguments.
	 *
	 * @return array<string, int|string>
	 */
	public function to_query_arguments(): array {
		return array(
			'post_type'      => 'page',
			'post_status'    => $this->status,
			'posts_per_page' => $this->limit,
			'paged'          => $this->page,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		);
	}
}
