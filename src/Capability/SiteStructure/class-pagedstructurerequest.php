<?php
/**
 * Closed pagination request for bounded structure discovery.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\SiteStructure;

use InvalidArgumentException;

/**
 * Validates one exact bounded page request.
 */
final class PagedStructureRequest {
	public const MAX_ITEMS = 20;

	/**
	 * Creates an immutable page request.
	 *
	 * @param int $limit Bounded number of records.
	 * @param int $page  One-indexed page number.
	 */
	private function __construct(
		public readonly int $limit,
		public readonly int $page,
	) {
	}

	/**
	 * Validates a closed discovery request.
	 *
	 * @param array<string, mixed> $input Request input.
	 * @return self Validated request.
	 * @throws InvalidArgumentException When input falls outside the page contract.
	 */
	public static function from_input( array $input ): self {
		if ( array( 'limit', 'page' ) !== array_keys( $input )
			|| ! is_int( $input['limit'] )
			|| ! is_int( $input['page'] )
			|| 1 > $input['limit']
			|| self::MAX_ITEMS < $input['limit']
			|| 1 > $input['page'] ) {
			throw new InvalidArgumentException( 'Structure discovery input is invalid.' );
		}

		return new self( $input['limit'], $input['page'] );
	}
}
