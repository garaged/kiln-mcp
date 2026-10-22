<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use InvalidArgumentException;
use KilnMcp\Capability\PostRead\PostReadRequest;
use PHPUnit\Framework\TestCase;

final class PostReadRequestTest extends TestCase {
	public function test_it_accepts_exactly_one_positive_post_id(): void {
		$request = PostReadRequest::from_input( array( 'post_id' => 42 ) );

		self::assertSame( 42, $request->post_id );
	}

	/**
	 * @dataProvider invalid_input_provider
	 *
	 * @param array<string, mixed> $input Invalid ability input.
	 */
	public function test_it_rejects_unknown_or_invalid_input( array $input ): void {
		$this->expectException( InvalidArgumentException::class );

		PostReadRequest::from_input( $input );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function invalid_input_provider(): array {
		return array(
			'missing identifier' => array( array() ),
			'zero identifier'    => array( array( 'post_id' => 0 ) ),
			'string identifier'  => array( array( 'post_id' => '42' ) ),
			'unknown property'   => array( array( 'post_id' => 42, 'fields' => 'content' ) ),
		);
	}
}
