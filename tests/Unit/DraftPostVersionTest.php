<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use InvalidArgumentException;
use KilnMcp\Infrastructure\WordPress\PostVersion;
use PHPUnit\Framework\TestCase;

final class DraftPostVersionTest extends TestCase {
	public function test_it_builds_a_fixed_length_deterministic_opaque_token(): void {
		$token = PostVersion::token( 42, '2026-10-22 20:00:00', 'revision-one' );

		self::assertSame( 64, strlen( $token ) );
		self::assertSame( $token, PostVersion::token( 42, '2026-10-22 20:00:00', 'revision-one' ) );
		self::assertNotSame( $token, PostVersion::token( 42, '2026-10-22 20:00:00', 'revision-two' ) );
		self::assertNotSame( $token, PostVersion::token( 42, '2026-10-22 20:00:01', 'revision-one' ) );
	}

	public function test_it_rejects_an_unidentified_post_version(): void {
		$this->expectException( InvalidArgumentException::class );

		PostVersion::token( 0, '', '' );
	}
}
