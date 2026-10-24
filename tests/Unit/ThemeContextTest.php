<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use InvalidArgumentException;
use KilnMcp\Capability\ThemeContext\PagedThemeContextRequest;
use KilnMcp\Capability\ThemeContext\ThemeContextSnapshot;
use PHPUnit\Framework\TestCase;

final class ThemeContextTest extends TestCase {
	public function test_it_accepts_only_a_bounded_closed_page_request(): void {
		$request = PagedThemeContextRequest::from_input( array( 'limit' => 20, 'page' => 2 ) );

		self::assertSame( 20, $request->limit );
		self::assertSame( 2, $request->page );
	}

	/**
	 * @dataProvider invalid_page_input_provider
	 *
	 * @param array<string, mixed> $input Invalid input.
	 */
	public function test_it_rejects_unknown_or_unbounded_page_input( array $input ): void {
		$this->expectException( InvalidArgumentException::class );

		PagedThemeContextRequest::from_input( $input );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function invalid_page_input_provider(): array {
		return array(
			'unknown key' => array( array( 'limit' => 1, 'page' => 1, 'source' => 'theme' ) ),
			'too many records' => array( array( 'limit' => 21, 'page' => 1 ) ),
			'zero page' => array( array( 'limit' => 1, 'page' => 0 ) ),
			'string limit' => array( array( 'limit' => '1', 'page' => 1 ) ),
		);
	}

	public function test_it_builds_only_allow_listed_theme_records(): void {
		$theme = ThemeContextSnapshot::active_theme( 'Kiln Theme', 'kiln-theme', '1.0.0', true, true );
		$template = ThemeContextSnapshot::template( 'home', 'Home', 'theme', false );
		$part = ThemeContextSnapshot::template_part( 'header', 'Header', 'theme', true, 'header' );
		$pattern = ThemeContextSnapshot::pattern( 'kiln-theme/hero', 'Hero', array( 'featured' ) );

		self::assertSame(
			array( 'name', 'stylesheet', 'version', 'has_parent', 'is_block_theme' ),
			array_keys( $theme )
		);
		self::assertSame( array( 'slug', 'title', 'origin', 'customized' ), array_keys( $template ) );
		self::assertSame( array( 'slug', 'title', 'origin', 'customized', 'area' ), array_keys( $part ) );
		self::assertSame( array( 'slug', 'title', 'categories' ), array_keys( $pattern ) );
		self::assertArrayNotHasKey( 'content', $template );
		self::assertArrayNotHasKey( 'path', $part );
		self::assertArrayNotHasKey( 'filePath', $pattern );
	}
}
