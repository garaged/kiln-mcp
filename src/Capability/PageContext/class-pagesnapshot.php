<?php
/**
 * Fixed page-context output records.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\PageContext;

/**
 * Creates only allow-listed page records.
 */
final class PageSnapshot {
	/**
	 * Creates one fixed page metadata record.
	 *
	 * @param int    $id          Page identifier.
	 * @param string $title       Page title.
	 * @param string $status      Page status.
	 * @param string $modified_at ISO 8601 modification time.
	 * @param string $permalink   Page permalink.
	 * @return array<string, int|string> Fixed metadata record.
	 */
	public static function metadata( int $id, string $title, string $status, string $modified_at, string $permalink ): array {
		return array(
			'id'          => $id,
			'title'       => $title,
			'status'      => $status,
			'modified_at' => $modified_at,
			'permalink'   => $permalink,
		);
	}

	/**
	 * Creates one fixed redacted page-read record.
	 *
	 * @param int    $id                Page identifier.
	 * @param string $title             Page title.
	 * @param string $status            Page status.
	 * @param string $modified_at       ISO 8601 modification time.
	 * @param string $modified_token    Opaque page version token.
	 * @param string $permalink         Page permalink.
	 * @param string $content_text      Redacted plain-text content.
	 * @param bool   $content_truncated Whether text exceeded the bound.
	 * @param string $notice            Fixed untrusted-data notice.
	 * @return array<string, bool|int|string> Fixed read record.
	 */
	public static function read( int $id, string $title, string $status, string $modified_at, string $modified_token, string $permalink, string $content_text, bool $content_truncated, string $notice ): array {
		return array(
			'id'                    => $id,
			'title'                 => $title,
			'status'                => $status,
			'modified_at'           => $modified_at,
			'modified_token'        => $modified_token,
			'permalink'             => $permalink,
			'content_text'          => $content_text,
			'content_truncated'     => $content_truncated,
			'untrusted_data_notice' => $notice,
		);
	}
}
