<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use InvalidArgumentException;
use KilnMcp\Capability\PostDiscovery\PostDiscoveryRequest;
use PHPUnit\Framework\TestCase;

final class PostDiscoveryTest extends TestCase {
	public function test_it_accepts_only_a_bounded_closed_post_query(): void {
		$request = PostDiscoveryRequest::from_input(
			array(
				'status' => 'publish',
				'limit'  => 20,
				'page'   => 2,
			)
		);

		self::assertSame(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'paged'          => 2,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			),
			$request->to_query_arguments()
		);
	}

	/**
	 * @dataProvider invalid_input_provider
	 *
	 * @param array<string, mixed> $input Invalid request input.
	 */
	public function test_it_rejects_unbounded_or_unknown_input( array $input ): void {
		$this->expectException( InvalidArgumentException::class );

		PostDiscoveryRequest::from_input( $input );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function invalid_input_provider(): array {
		return array(
			'private status' => array(
				array(
					'status' => 'private',
					'limit'  => 1,
					'page'   => 1,
				),
			),
			'large page'     => array(
				array(
					'status' => 'publish',
					'limit'  => 21,
					'page'   => 1,
				),
			),
			'unknown filter' => array(
				array(
					'status' => 'publish',
					'limit'  => 1,
					'page'   => 1,
					'search' => 'unbounded',
				),
			),
		);
	}
}
