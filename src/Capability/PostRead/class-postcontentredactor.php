<?php
/**
 * Bounded plain-text content transformation.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\PostRead;

/**
 * Normalizes already shortcode-stripped WordPress text for tool output.
 */
final class PostContentRedactor {
	public const MAX_CHARACTERS = 12000;

	/**
	 * Removes residual markup, normalizes whitespace, and bounds UTF-8 output.
	 *
	 * @param string $content WordPress content after shortcode removal.
	 * @return array{content_text: string, content_truncated: bool} Safe text result.
	 */
	public static function redact( string $content ): array {
		$text = wp_strip_all_tags( $content, true );
		$text = preg_replace( '/\s+/u', ' ', trim( $text ) );
		$text = is_string( $text ) ? $text : '';

		if ( self::MAX_CHARACTERS >= mb_strlen( $text, 'UTF-8' ) ) {
			return array(
				'content_text'      => $text,
				'content_truncated' => false,
			);
		}

		return array(
			'content_text'      => mb_substr( $text, 0, self::MAX_CHARACTERS, 'UTF-8' ),
			'content_truncated' => true,
		);
	}
}
