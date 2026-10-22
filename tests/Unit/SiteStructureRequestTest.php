<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use InvalidArgumentException;
use KilnMcp\Capability\SiteStructure\PagedStructureRequest;
use KilnMcp\Capability\SiteStructure\TermSeedRequest;
use PHPUnit\Framework\TestCase;

final class SiteStructureRequestTest extends TestCase {
	public function test_it_accepts_a_bounded_structure_page(): void {
		$request = PagedStructureRequest::from_input( array( 'limit' => 20, 'page' => 2 ) );

		self::assertSame( 20, $request->limit );
		self::assertSame( 2, $request->page );
	}

	public function test_it_accepts_plain_text_category_and_tag_seeds(): void {
		$category = TermSeedRequest::from_input( 'category', array( 'name' => 'Engineering', 'parent_id' => 7 ) );
		$tag      = TermSeedRequest::from_input( 'post_tag', array( 'name' => 'Release' ) );

		self::assertSame( 'Engineering', $category->name );
		self::assertSame( 7, $category->parent_id );
		self::assertSame( 0, $tag->parent_id );
	}

	/**
	 * @dataProvider invalid_input_provider
	 *
	 * @param string               $taxonomy Taxonomy identifier.
	 * @param array<string, mixed> $input    Invalid input.
	 */
	public function test_it_rejects_unknown_unbounded_or_markup_like_input( string $taxonomy, array $input ): void {
		$this->expectException( InvalidArgumentException::class );

		TermSeedRequest::from_input( $taxonomy, $input );
	}

	/**
	 * @return array<string, array{0: string, 1: array<string, mixed>}>
	 */
	public static function invalid_input_provider(): array {
		return array(
			'unknown field' => array( 'post_tag', array( 'name' => 'Release', 'slug' => 'release' ) ),
			'markup'        => array( 'category', array( 'name' => '<b>Unsafe</b>', 'parent_id' => 0 ) ),
			'trimmed'       => array( 'post_tag', array( 'name' => ' Release ' ) ),
			'negative parent' => array( 'category', array( 'name' => 'Engineering', 'parent_id' => -1 ) ),
		);
	}
}
