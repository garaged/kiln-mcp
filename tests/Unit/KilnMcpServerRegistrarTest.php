<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Infrastructure\WordPress\KilnMcpServerRegistrar;
use PHPUnit\Framework\TestCase;

final class KilnMcpServerRegistrarTest extends TestCase {
	public function test_it_registers_only_the_official_http_server_with_its_explicit_allow_list(): void {
		$adapter   = new KilnMcpServerRecorder();
		$registrar = new KilnMcpServerRegistrar();

		$registrar->register_server( $adapter );

		self::assertSame( KilnMcpServerRegistrar::SERVER_ID, $adapter->arguments[0] );
		self::assertSame( 'kiln-mcp', $adapter->arguments[1] );
		self::assertSame( 'mcp', $adapter->arguments[2] );
		self::assertSame( array( '\\WP\\MCP\\Transport\\HttpTransport' ), $adapter->arguments[6] );
		$configured_abilities = $adapter->arguments[9];
		if ( ! is_array( $configured_abilities ) ) {
			self::fail( 'The adapter server must receive an ability list.' );
		}
		/** @var array<int, string> $ability_names */
		$ability_names = $configured_abilities;
		self::assertContains( 'kiln/site-health-summary', $ability_names );
		self::assertContains( 'kiln/get-active-theme-context', $ability_names );
		self::assertContains( 'kiln/list-installed-themes', $ability_names );
		self::assertContains( 'kiln/activate-theme', $ability_names );
		self::assertContains( 'kiln/rollback-theme', $ability_names );
		self::assertContains( 'kiln/create-page-draft', $ability_names );
		self::assertContains( 'kiln/update-page-draft', $ability_names );
		self::assertContains( 'kiln/list-block-templates', $ability_names );
		self::assertContains( 'kiln/list-block-template-parts', $ability_names );
		self::assertContains( 'kiln/list-active-theme-patterns', $ability_names );
		self::assertNotContains( 'unrelated/plugin-ability', $ability_names );
	}

	public function test_it_includes_admitted_extensions_but_refuses_unrelated_ability_names(): void {
		$registrar = new KilnMcpServerRegistrar();

		$names = $registrar->ability_names(
			array(
				'kiln-ext/example--facts' => new \stdClass(),
				'kiln/custom-core'         => new \stdClass(),
				'unrelated/plugin-ability' => new \stdClass(),
			)
		);

		self::assertContains( 'kiln-ext/example--facts', $names );
		self::assertContains( 'kiln/custom-core', $names );
		self::assertNotContains( 'unrelated/plugin-ability', $names );
	}

	public function test_it_requires_authentication_and_authoring_transport_capability(): void {
		self::assertTrue( KilnMcpServerRegistrar::is_transport_admitted( true, true ) );
		self::assertFalse( KilnMcpServerRegistrar::is_transport_admitted( false, true ) );
		self::assertFalse( KilnMcpServerRegistrar::is_transport_admitted( true, false ) );
	}
}

final class KilnMcpServerRecorder {
	/** @var array<int|string, mixed> */
	public array $arguments = array();

	/**
	 * @param mixed ...$arguments Server configuration.
	 * @return void
	 */
	public function create_server( mixed ...$arguments ): void {
		$this->arguments = $arguments;
	}
}
