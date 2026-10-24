<?php
/**
 * Closed input contract for page draft update.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\DraftAuthoring;

use InvalidArgumentException;

/**
 * Validates the exact target and optimistic-concurrency token for a page draft update.
 */
final class PageDraftUpdateRequest {
	/**
	 * Creates an immutable page-draft-update request.
	 *
	 * @param int    $page_id                 Positive WordPress page identifier.
	 * @param string $expected_modified_token Current durable page modification token.
	 * @param string $title                   Plain-text post title.
	 * @param string $content                 Plain-text post content.
	 */
	private function __construct(
		public readonly int $page_id,
		public readonly string $expected_modified_token,
		public readonly string $title,
		public readonly string $content,
	) {
	}

	/**
	 * Validates the closed page-draft-update input object.
	 *
	 * @param array<string, mixed> $input Ability input.
	 * @return self A validated request.
	 * @throws InvalidArgumentException When input falls outside the safe contract.
	 */
	public static function from_input( array $input ): self {
		if ( array( 'page_id', 'expected_modified_token', 'title', 'content' ) !== array_keys( $input )
			|| ! is_int( $input['page_id'] )
			|| 1 > $input['page_id']
			|| ! is_string( $input['expected_modified_token'] )
			|| ! is_string( $input['title'] )
			|| ! is_string( $input['content'] )
			|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/D', $input['expected_modified_token'] ) ) {
			throw new InvalidArgumentException( 'Draft update input is invalid.' );
		}

		DraftCreateRequest::assert_plain_text( $input['title'], DraftCreateRequest::MAX_TITLE_CHARACTERS, true );
		DraftCreateRequest::assert_plain_text( $input['content'], DraftCreateRequest::MAX_CONTENT_CHARACTERS, false );

		return new self( $input['page_id'], $input['expected_modified_token'], $input['title'], $input['content'] );
	}
}
