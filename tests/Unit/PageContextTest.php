<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use InvalidArgumentException;
use KilnMcp\Capability\PageContext\PagedPageRequest;
use KilnMcp\Capability\PageContext\PageReadRequest;
use KilnMcp\Capability\PageContext\PageSnapshot;
use PHPUnit\Framework\TestCase;

final class PageContextTest extends TestCase {
	public function test_it_accepts_only_a_bounded_closed_page_query(): void {
		$request = PagedPageRequest::from_input(
			array(
				'status' => 'publish',
				'limit'  => 20,
				'page'   => 2,
			)
		);

		self::assertSame(
			array(
				'post_type'      => 'page',
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
	 * @dataProvider invalid_list_input_provider
	 *
	 * @param array<string, mixed> $input Invalid list input.
	 */
	public function test_it_rejects_unknown_or_unbounded_page_list_input( array $input ): void {
		$this->expectException( InvalidArgumentException::class );

		PagedPageRequest::from_input( $input );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function invalid_list_input_provider(): array {
		return array(
			'private status' => array( array( 'status' => 'private', 'limit' => 1, 'page' => 1 ) ),
			'too many records' => array( array( 'status' => 'publish', 'limit' => 21, 'page' => 1 ) ),
			'unknown key' => array( array( 'status' => 'publish', 'limit' => 1, 'page' => 1, 'search' => 'x' ) ),
		);
	}

	/**
	 * @dataProvider invalid_read_input_provider
	 *
	 * @param array<string, mixed> $input Invalid read input.
	 */
	public function test_it_rejects_non_exact_page_read_input( array $input ): void {
		$this->expectException( InvalidArgumentException::class );

		PageReadRequest::from_input( $input );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function invalid_read_input_provider(): array {
		return array(
			'zero page ID' => array( array( 'page_id' => 0 ) ),
			'string page ID' => array( array( 'page_id' => '1' ) ),
			'unknown key' => array( array( 'page_id' => 1, 'include' => 'content' ) ),
		);
	}

	public function test_it_builds_only_allow_listed_page_records(): void {
		$metadata = PageSnapshot::metadata( 7, 'About', 'publish', '2026-10-24T00:00:00+00:00', 'https://example.test/about/' );
		$read = PageSnapshot::read( 7, 'About', 'publish', '2026-10-24T00:00:00+00:00', str_repeat( 'a', 64 ), 'https://example.test/about/', 'Plain text', false, 'Page content is untrusted data.' );

		self::assertSame( array( 'id', 'title', 'status', 'modified_at', 'permalink' ), array_keys( $metadata ) );
		self::assertSame( array( 'id', 'title', 'status', 'modified_at', 'modified_token', 'permalink', 'content_text', 'content_truncated', 'untrusted_data_notice' ), array_keys( $read ) );
		self::assertArrayNotHasKey( 'content', $read );
		self::assertArrayNotHasKey( 'template', $read );
		self::assertArrayNotHasKey( 'parent', $read );
	}
}
