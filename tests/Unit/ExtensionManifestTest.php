<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use Closure;
use InvalidArgumentException;
use KilnMcp\Extension\ExtensionAbility;
use KilnMcp\Extension\ExtensionManifest;
use PHPUnit\Framework\TestCase;

final class ExtensionManifestTest extends TestCase {
	public function test_it_produces_a_canonical_extension_ability_name(): void {
		$ability = $this->ability( 'site-facts' );
		$manifest = new ExtensionManifest( 'example', '1.0.0', array( $ability ) );

		self::assertSame( 'kiln-ext/example--site-facts', $manifest->ability_name( $ability ) );
	}

	/**
	 * @dataProvider invalid_manifest_provider
	 *
	 * @param string                       $id        Invalid ID.
	 * @param string                       $version   Invalid version.
	 * @param array<int, ExtensionAbility> $abilities Invalid declarations.
	 */
	public function test_it_refuses_invalid_or_open_manifests( string $id, string $version, array $abilities ): void {
		$this->expectException( InvalidArgumentException::class );

		new ExtensionManifest( $id, $version, $abilities );
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: array<int, ExtensionAbility>}>
	 */
	public static function invalid_manifest_provider(): array {
		$open = new ExtensionAbility( 'open-schema', 'Open', 'Invalid schema.', array( 'type' => 'object', 'additionalProperties' => true, 'properties' => array(), 'required' => array() ), self::schema(), Closure::fromCallable( static fn( array $input ): array => array() ) );

		return array(
			'invalid ID' => array( 'A', '1.0.0', array() ),
			'invalid version' => array( 'example', 'v1', array() ),
			'ambiguous separator' => array( 'example--other', '1.0.0', array() ),
			'open schema' => array( 'example', '1.0.0', array( $open ) ),
		);
	}

	private function ability( string $id ): ExtensionAbility {
		return new ExtensionAbility( $id, 'Example ability', 'Returns a fixed example.', self::schema(), self::schema(), Closure::fromCallable( static fn( array $input ): array => array() ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function schema(): array {
		return array( 'type' => 'object', 'additionalProperties' => false, 'properties' => array(), 'required' => array() );
	}
}
