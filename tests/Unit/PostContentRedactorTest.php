<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Capability\PostRead\PostContentRedactor;
use PHPUnit\Framework\TestCase;

final class PostContentRedactorTest extends TestCase {
	public function test_it_normalizes_short_content_without_truncation(): void {
		$result = PostContentRedactor::redact( " First\n\nsecond\tthird " );

		self::assertSame(
			array(
				'content_text'      => 'First second third',
				'content_truncated' => false,
			),
			$result
		);
	}

	public function test_it_preserves_utf8_boundaries_when_truncating(): void {
		$result = PostContentRedactor::redact( str_repeat( 'é', PostContentRedactor::MAX_CHARACTERS + 1 ) );

		self::assertTrue( $result['content_truncated'] );
		self::assertSame( PostContentRedactor::MAX_CHARACTERS, mb_strlen( $result['content_text'], 'UTF-8' ) );
		self::assertSame( str_repeat( 'é', PostContentRedactor::MAX_CHARACTERS ), $result['content_text'] );
	}
}
