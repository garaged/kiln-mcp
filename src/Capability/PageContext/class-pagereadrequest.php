<?php
/**
 * Exact page-read request.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\PageContext;

use InvalidArgumentException;

/**
 * Validates one exact page identifier.
 */
final class PageReadRequest {
	/**
	 * Applies the bounded page-context contract.
	 *
	 * @param int $page_id Admitted page identifier.
	 */
	private function __construct(
		public readonly int $page_id,
	) {
	}

	/**
	 * Creates one closed page-read request.
	 *
	 * @param array<string, mixed> $input Candidate request.
	 * @return self Validated request.
	 * @throws InvalidArgumentException When input is not one exact positive page ID.
	 */
	public static function from_input( array $input ): self {
		if (
			array( 'page_id' ) !== array_keys( $input )
			|| ! is_int( $input['page_id'] )
			|| $input['page_id'] < 1
		) {
			throw new InvalidArgumentException( 'Invalid page read input.' );
		}

		return new self( $input['page_id'] );
	}
}
