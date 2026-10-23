<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use Closure;
use InvalidArgumentException;
use KilnMcp\Extension\ExtensionAbility;
use KilnMcp\Extension\ExtensionManifest;
use KilnMcp\Extension\ExtensionRegistry;
use PHPUnit\Framework\TestCase;

final class ExtensionRegistryTest extends TestCase {
	public function test_it_refuses_a_duplicate_manifest_without_replacing_the_admitted_one(): void {
		$registry = new ExtensionRegistry();
		$first = $this->manifest( 'example', 'facts' );
		$registry->register( $first );

		$this->expectException( InvalidArgumentException::class );
		$registry->register( $this->manifest( 'example', 'other' ) );

		self::assertSame( $first, $registry->manifests()['example'] );
	}

	public function test_it_admits_distinct_manifests(): void {
		$registry = new ExtensionRegistry();
		$registry->register( $this->manifest( 'example', 'facts' ) );
		$registry->register( $this->manifest( 'second', 'facts' ) );

		self::assertCount( 2, $registry->manifests() );
	}

	private function manifest( string $extension, string $ability ): ExtensionManifest {
		return new ExtensionManifest( $extension, '1.0.0', array( new ExtensionAbility( $ability, 'Facts', 'Fixed facts.', self::schema(), self::schema(), Closure::fromCallable( static fn( array $input ): array => array() ) ) ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function schema(): array {
		return array( 'type' => 'object', 'additionalProperties' => false, 'properties' => array(), 'required' => array() );
	}
}
