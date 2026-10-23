<?php
/**
 * Typed M7 extension manifest contract.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\\Extension;

use InvalidArgumentException;

/**
 * Validates one atomic, read-only extension declaration.
 *
 * All manifest failures refuse the complete declaration.
 */
final readonly class ExtensionManifest {
	public const MAX_ABILITIES = 10;

	/**
	 * @param string                       $id        Normalized extension ID.
	 * @param string                       $version   Semantic version.
	 * @param array<int, ExtensionAbility> $abilities Declared abilities.
	 */
	public function __construct(
		public string $id,
		public string $version,
		public array $abilities,
	) {
		self::assert_valid( $id, $version, $abilities );
	}

	/**
	 * Returns the canonical core-owned ability name.
	 *
	 * The core, rather than the extension, owns this public name.
	 *
	 * @param ExtensionAbility $ability Extension ability.
	 * @return string Canonical ability name.
	 */
	public function ability_name( ExtensionAbility $ability ): string {
		return 'kiln-ext/' . $this->id . '/' . $ability->id;
	}

	/**
	 * Validates all manifest fields before registration.
	 *
	 * @param string                       $id        Candidate extension ID.
	 * @param string                       $version   Candidate semantic version.
	 * @param array<int, ExtensionAbility> $abilities Candidate abilities.
	 * @return void
	 * @throws InvalidArgumentException When the declaration is not admitted.
	 */
	private static function assert_valid( string $id, string $version, array $abilities ): void {
		if ( 1 !== preg_match( '/^[a-z][a-z0-9-]{2,31}$/', $id ) || 1 !== preg_match( '/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?$/', $version ) || count( $abilities ) > self::MAX_ABILITIES ) {
			throw new InvalidArgumentException( 'Extension identity, version, or size is invalid.' );
		}

		$ids = array();
		foreach ( $abilities as $ability ) {
			if ( ! $ability instanceof ExtensionAbility || 1 !== preg_match( '/^[a-z][a-z0-9-]{2,31}$/', $ability->id ) || isset( $ids[ $ability->id ] ) || ! self::is_closed_object_schema( $ability->input_schema ) || ! self::is_closed_object_schema( $ability->output_schema ) ) {
				throw new InvalidArgumentException( 'Extension ability declaration is invalid.' );
			}
			$ids[ $ability->id ] = true;
		}
	}

	/**
	 * Checks one schema against the closed-object profile.
	 *
	 * @param array<string, mixed> $schema Candidate schema.
	 * @return bool Whether it is a closed object schema.
	 */
	private static function is_closed_object_schema( array $schema ): bool {
		return 'object' === ( $schema['type'] ?? null ) && false === ( $schema['additionalProperties'] ?? null ) && isset( $schema['properties'], $schema['required'] ) && is_array( $schema['properties'] ) && is_array( $schema['required'] );
	}
}
