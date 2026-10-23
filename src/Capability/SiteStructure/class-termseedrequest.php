<?php
/**
 * Closed input contract for one standard taxonomy seed.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\SiteStructure;

use InvalidArgumentException;

/**
 * Validates one plain-text category or tag name.
 */
final class TermSeedRequest {
	public const MAX_NAME_CHARACTERS = 80;

	/**
	 * Creates an immutable term seed request.
	 *
	 * @param string $name      Plain-text term name.
	 * @param int    $parent_id Category parent, or zero for root/tag.
	 */
	private function __construct(
		public readonly string $name,
		public readonly int $parent_id,
	) {
	}

	/**
	 * Validates an exact standard-taxonomy seed request.
	 *
	 * @param string               $taxonomy Standard taxonomy identifier.
	 * @param array<string, mixed> $input    Ability input.
	 * @return self Validated request.
	 * @throws InvalidArgumentException When input is outside the M6 contract.
	 */
	public static function from_input( string $taxonomy, array $input ): self {
		$expected_keys = 'category' === $taxonomy ? array( 'name', 'parent_id' ) : array( 'name' );

		if ( ( 'category' !== $taxonomy && 'post_tag' !== $taxonomy )
			|| array_keys( $input ) !== $expected_keys
			|| ! is_string( $input['name'] )
			|| '' === $input['name']
			|| self::MAX_NAME_CHARACTERS < mb_strlen( $input['name'] )
			|| trim( $input['name'] ) !== $input['name']
			|| 1 === preg_match( '/[<>\[\]\x00-\x1F]/', $input['name'] ) ) {
			throw new InvalidArgumentException( 'Term seed input is invalid.' );
		}

		$parent_id = 0;
		if ( 'category' === $taxonomy ) {
			if ( ! is_int( $input['parent_id'] ) || 0 > $input['parent_id'] ) {
				throw new InvalidArgumentException( 'Term parent is invalid.' );
			}
			$parent_id = $input['parent_id'];
		}

		return new self( $input['name'], $parent_id );
	}
}
