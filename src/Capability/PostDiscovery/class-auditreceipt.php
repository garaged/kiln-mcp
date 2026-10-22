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
	public const ABILITY_NAME = 'kiln/list-posts';

	/**
	 * @return array<string, int|string>
	 */
	public static function create( string $outcome, string $capability_class, int $result_count, string $timestamp ): array {
		return array(
			'schema_version'          => '1.0',
			'timestamp'               => $timestamp,
			'ability'                 => self::ABILITY_NAME,
			'outcome'                 => $outcome,
			'caller_capability_class' => $capability_class,
			'result_count'            => $result_count,
		);
	}
}
