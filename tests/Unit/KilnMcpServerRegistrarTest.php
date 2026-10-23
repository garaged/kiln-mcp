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
		self::assertContains( 'kiln/site-health-summary', $adapter->arguments[9] );
		self::assertNotContains( 'unrelated/plugin-ability', $adapter->arguments[9] );
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
	/** @var array<int, mixed> */
	public array $arguments = array();

	/**
	 * @param mixed ...$arguments Server configuration.
	 * @return void
	 */
	public function create_server( mixed ...$arguments ): void {
		$this->arguments = $arguments;
	}
}
