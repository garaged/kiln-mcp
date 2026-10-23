<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use Closure;
use KilnMcp\Extension\ExtensionAbility;
use KilnMcp\Extension\ExtensionManifest;
use KilnMcp\Extension\ExtensionRegistry;
use KilnMcp\Infrastructure\WordPress\AuditReceiptStore;
use KilnMcp\Infrastructure\WordPress\ExtensionRegistrar;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WP_Hook;

final class ExtensionPolicyTest extends TestCase {
	/** @var mixed */
	private mixed $previous_hook;

	protected function setUp(): void {
		$this->previous_hook = $GLOBALS['wp_filter'] ?? null;
		$GLOBALS['kiln_mcp_test_registered_abilities'] = array();
	}

	protected function tearDown(): void {
		if ( null === $this->previous_hook ) {
			unset( $GLOBALS['wp_filter'] );
		} else {
			$GLOBALS['wp_filter'] = $this->previous_hook;
		}

		unset( $GLOBALS['kiln_mcp_test_registered_abilities'] );
	}

	public function test_it_exposes_only_core_governed_read_only_abilities(): void {
		$this->set_providers(
			array(
				static function ( ExtensionRegistry $registry ): void {
					$registry->register( self::manifest( 'example', 'facts' ) );
				},
			)
		);

		( new ExtensionRegistrar( new AuditReceiptStore() ) )->register_abilities();

		$registered = self::registered_ability( 'kiln-ext/example--facts' );
		self::assertSame( 'Kiln extension facts', $registered['label'] );
		self::assertSame( self::schema(), $registered['input_schema'] );
		self::assertSame( self::schema(), $registered['output_schema'] );
		self::assertTrue( $registered['meta']['annotations']['readonly'] );
		self::assertTrue( $registered['meta']['annotations']['idempotent'] );
		self::assertTrue( $registered['meta']['public'] );
		self::assertFalse( $registered['meta']['show_in_rest'] );
		self::assertFalse( ( $registered['permission_callback'] )() );
	}

	public function test_it_discards_a_throwing_provider_and_continues_to_a_valid_provider(): void {
		$this->set_providers(
			array(
				static function ( ExtensionRegistry $registry ): void {
					$registry->register( self::manifest( 'broken', 'partial' ) );

					throw new RuntimeException( 'Sensitive implementation detail.' );
				},
				static function ( ExtensionRegistry $registry ): void {
					$registry->register( self::manifest( 'valid', 'facts' ) );
				},
			)
		);

		( new ExtensionRegistrar( new AuditReceiptStore() ) )->register_abilities();

		$registered = $GLOBALS['kiln_mcp_test_registered_abilities'];
		self::assertArrayNotHasKey( 'kiln-ext/broken--partial', $registered );
		self::assertArrayHasKey( 'kiln-ext/valid--facts', $registered );
	}

	/**
	 * @param array<int, Closure> $providers Extension provider callbacks.
	 * @return void
	 */
	private function set_providers( array $providers ): void {
		$hook = new WP_Hook();
		$callbacks = array();
		foreach ( $providers as $index => $provider ) {
			$callbacks[ 'provider-' . $index ] = array( 'function' => $provider );
		}
		$hook->callbacks = array( 10 => $callbacks );
		$GLOBALS['wp_filter'] = array( 'kiln_mcp_register_extensions' => $hook );
	}

	/**
	 * @param string $extension Extension ID.
	 * @param string $ability   Ability ID.
	 * @return ExtensionManifest Valid fixture manifest.
	 */
	private static function manifest( string $extension, string $ability ): ExtensionManifest {
		return new ExtensionManifest(
			$extension,
			'1.0.0',
			array(
				new ExtensionAbility(
					$ability,
					'Kiln extension facts',
					'Returns fixed facts.',
					self::schema(),
					self::schema(),
					Closure::fromCallable( static fn( array $input ): array => array() )
				),
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function schema(): array {
		return array( 'type' => 'object', 'additionalProperties' => false, 'properties' => array(), 'required' => array() );
	}

	/**
	 * @param string $name Registered ability name.
	 * @return array<string, mixed> Registered ability configuration.
	 */
	private static function registered_ability( string $name ): array {
		$registered = $GLOBALS['kiln_mcp_test_registered_abilities'];

		self::assertIsArray( $registered );
		self::assertArrayHasKey( $name, $registered );
		self::assertIsArray( $registered[ $name ] );

		return $registered[ $name ];
	}
}
