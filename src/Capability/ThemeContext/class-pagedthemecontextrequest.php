<?php
/**
 * Closed pagination request for bounded theme context discovery.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ThemeContext;

use InvalidArgumentException;

/**
 * Validates one exact page request without filters or sort controls.
 */
final class PagedThemeContextRequest {
	public const MAX_ITEMS = 20;

	/**
	 * @param int $limit Maximum returned records.
	 * @param int $page  One-indexed page.
	 */
	private function __construct(
		public readonly int $limit,
		public readonly int $page,
	) {
	}

	/**
	 * @param array<string, mixed> $input Closed pagination input.
	 * @return self Validated bounded request.
	 * @throws InvalidArgumentException When input is not the exact page contract.
	 */
	public static function from_input( array $input ): self {
		if (
			array( 'limit', 'page' ) !== array_keys( $input )
			|| ! is_int( $input['limit'] )
			|| ! is_int( $input['page'] )
			|| 1 > $input['limit']
			|| self::MAX_ITEMS < $input['limit']
			|| 1 > $input['page']
		) {
			throw new InvalidArgumentException( 'Theme context input is invalid.' );
		}

		return new self( $input['limit'], $input['page'] );
	}
}
