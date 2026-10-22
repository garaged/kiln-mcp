<?php
/**
 * Redacted local audit receipt contract.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\PostDiscovery;

/**
 * Creates a receipt without caller, request, or content data.
 */
final class AuditReceipt {
	public const LIST_POSTS_ABILITY_NAME = 'kiln/list-posts';
	public const READ_POST_ABILITY_NAME  = 'kiln/read-post';

	/**
	 * Creates one fixed-shape redacted audit receipt.
	 *
	 * @param string $ability          Kiln ability identifier.
	 * @param string $outcome          Allowed or denied outcome.
	 * @param string $capability_class Capability class used for the decision.
	 * @param int    $result_count     Number of records returned.
	 * @param string $timestamp        ISO 8601 receipt timestamp.
	 * @return array<string, int|string>
	 */
	public static function create( string $ability, string $outcome, string $capability_class, int $result_count, string $timestamp ): array {
		return array(
			'schema_version'          => '1.0',
			'timestamp'               => $timestamp,
			'ability'                 => $ability,
			'outcome'                 => $outcome,
			'caller_capability_class' => $capability_class,
			'result_count'            => $result_count,
		);
	}
}
