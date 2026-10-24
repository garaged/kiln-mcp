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
	 * @return array<string, int|string>
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
	 * @return array<string, bool|int|string>
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
