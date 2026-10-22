<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Infrastructure\WordPress\AdapterDependency;
use PHPUnit\Framework\TestCase;

final class AdapterDependencyTest extends TestCase {
	public function test_it_requires_both_runtime_apis_and_the_minimum_adapter_version(): void {
		self::assertTrue( AdapterDependency::is_available( true, true, '0.7.1' ) );
		self::assertTrue( AdapterDependency::is_available( true, true, '0.8.0' ) );
		self::assertFalse( AdapterDependency::is_available( false, true, '0.7.1' ) );
		self::assertFalse( AdapterDependency::is_available( true, false, '0.7.1' ) );
		self::assertFalse( AdapterDependency::is_available( true, true, '0.7.0' ) );
		self::assertFalse( AdapterDependency::is_available( true, true, null ) );
	}
}
