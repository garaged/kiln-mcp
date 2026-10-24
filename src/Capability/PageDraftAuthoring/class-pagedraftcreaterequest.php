<?php
/**
 * Closed input contract for draft creation.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\PageDraftAuthoring;

use InvalidArgumentException;

/**
 * Validates the bounded title and plain-text content used to create a draft.
 */
final class PageDraftCreateRequest {
	public const MAX_TITLE_CHARACTERS   = 200;
	public const MAX_CONTENT_CHARACTERS = 20000;

	/**
	 * Creates an immutable page-draft-create request.
	 *
	 * @param string $title   Plain-text page title.
	 * @param string $content Plain-text page content.
	 */
	private function __construct(
		public readonly string $title,
		public readonly string $content,
	) {
	}

	/**
	 * Validates the closed page-draft-create input object.
	 *
	 * @param array<string, mixed> $input Ability input.
	 * @return self A validated request.
	 * @throws InvalidArgumentException When input falls outside the safe contract.
	 */
	public static function from_input( array $input ): self {
		if ( array( 'title', 'content' ) !== array_keys( $input ) || ! is_string( $input['title'] ) || ! is_string( $input['content'] ) ) {
			throw new InvalidArgumentException( 'Draft create input must contain only title and content strings.' );
		}

		self::assert_plain_text( $input['title'], self::MAX_TITLE_CHARACTERS, true );
		self::assert_plain_text( $input['content'], self::MAX_CONTENT_CHARACTERS, false );

		return new self( $input['title'], $input['content'] );
	}

	/**
	 * Rejects markup-like and control input without mutating the client request.
	 *
	 * @param string $value       Candidate input.
	 * @param int    $maximum     UTF-8 character bound.
	 * @param bool   $non_empty   Whether whitespace-only input is denied.
	 * @return void
	 * @throws InvalidArgumentException When text is outside the plain-text boundary.
	 */
	public static function assert_plain_text( string $value, int $maximum, bool $non_empty ): void {
		if ( ! mb_check_encoding( $value, 'UTF-8' )
			|| $maximum < mb_strlen( $value, 'UTF-8' )
			|| ( $non_empty && '' === trim( $value ) )
			|| 1 === preg_match( '/[<>]/u', $value )
			|| 1 === preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value ) ) {
			throw new InvalidArgumentException( 'Draft input must be bounded plain UTF-8 text.' );
		}
	}
}
