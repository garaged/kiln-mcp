<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use InvalidArgumentException;
use KilnMcp\Capability\PageDraftAuthoring\PageDraftCreateRequest;
use KilnMcp\Capability\PageDraftAuthoring\PageDraftUpdateRequest;
use PHPUnit\Framework\TestCase;

final class PageDraftAuthoringRequestTest extends TestCase {
	public function test_it_accepts_closed_plain_text_creation_input(): void {
		$request = PageDraftCreateRequest::from_input(
			array(
				'title'   => 'Kiln draft',
				'content' => "First line\nSecond line",
			)
		);

		self::assertSame( 'Kiln draft', $request->title );
		self::assertSame( "First line\nSecond line", $request->content );
	}

	public function test_it_accepts_closed_exact_target_update_input(): void {
		$request = PageDraftUpdateRequest::from_input(
			array(
				'page_id'                 => 42,
				'expected_modified_token' => str_repeat( 'a', 64 ),
				'title'                   => 'Updated draft',
				'content'                 => '',
			)
		);

		self::assertSame( 42, $request->page_id );
		self::assertSame( str_repeat( 'a', 64 ), $request->expected_modified_token );
	}

	/**
	 * @dataProvider invalid_create_input_provider
	 *
	 * @param array<string, mixed> $input Invalid creation input.
	 */
	public function test_it_rejects_non_plain_or_unbounded_create_input( array $input ): void {
		$this->expectException( InvalidArgumentException::class );

		PageDraftCreateRequest::from_input( $input );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function invalid_create_input_provider(): array {
		return array(
			'unknown field' => array(
				array(
					'title'   => 'Page draft',
					'content' => 'Body',
					'status'  => 'publish',
				),
			),
			'markup'        => array(
				array(
					'title'   => 'Page draft',
					'content' => '<script>alert(1)</script>',
				),
			),
			'empty title'   => array(
				array(
					'title'   => '   ',
					'content' => 'Body',
				),
			),
			'long body'     => array(
				array(
					'title'   => 'Page draft',
					'content' => str_repeat( 'x', PageDraftCreateRequest::MAX_CONTENT_CHARACTERS + 1 ),
				),
			),
		);
	}

	/**
	 * @dataProvider invalid_update_input_provider
	 *
	 * @param array<string, mixed> $input Invalid update input.
	 */
	public function test_it_rejects_invalid_update_target_or_token( array $input ): void {
		$this->expectException( InvalidArgumentException::class );

		PageDraftUpdateRequest::from_input( $input );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function invalid_update_input_provider(): array {
		return array(
			'string ID'    => array(
				array(
					'page_id'                 => '42',
					'expected_modified_token' => str_repeat( 'a', 64 ),
					'title'                   => 'Page draft',
					'content'                 => 'Body',
				),
			),
			'short token'  => array(
				array(
					'page_id'                 => 42,
					'expected_modified_token' => 'abc',
					'title'                   => 'Page draft',
					'content'                 => 'Body',
				),
			),
			'unknown field' => array(
				array(
					'page_id'                 => 42,
					'expected_modified_token' => str_repeat( 'a', 64 ),
					'title'                   => 'Page draft',
					'content'                 => 'Body',
					'author'                  => 1,
				),
			),
		);
	}
}
