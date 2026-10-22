<?php
/**
 * Hash-only record contract for one local workflow approval.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ContentWorkflow;

use InvalidArgumentException;

/**
 * Builds and verifies the non-secret portion of an administrator approval.
 */
final class WorkflowApproval {
	public const TTL_SECONDS = 600;

	/**
	 * Creates one fixed-shape, hash-only approval record.
	 *
	 * @param string $action                 Exact lifecycle effect.
	 * @param int    $post_id                Exact post identifier.
	 * @param string $modified_token         Current post version token.
	 * @param string $approval_token         Raw approval shown only at issuance.
	 * @param int    $issued_at              Unix issuance time.
	 * @return array<string, int|string|null> Persistable approval record.
	 * @throws InvalidArgumentException When the approval binding is malformed.
	 */
	public static function create( string $action, int $post_id, string $modified_token, string $approval_token, int $issued_at ): array {
		if ( ! WorkflowAction::is_supported( $action )
			|| 1 > $post_id
			|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/D', $modified_token )
			|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/D', $approval_token )
			|| 0 >= $issued_at ) {
			throw new InvalidArgumentException( 'Workflow approval binding is invalid.' );
		}

		return array(
			'action'         => $action,
			'post_id'        => $post_id,
			'modified_token' => $modified_token,
			'token_hash'     => hash( 'sha256', $approval_token ),
			'expires_at'     => $issued_at + self::TTL_SECONDS,
			'consumed_at'    => null,
		);
	}

	/**
	 * Checks whether a fixed record authorizes this exact request at one instant.
	 *
	 * @param array<string, mixed> $record          Stored approval record.
	 * @param string               $action          Requested lifecycle effect.
	 * @param int                  $post_id         Requested post ID.
	 * @param string               $modified_token  Requested post version token.
	 * @param string               $approval_token  Raw approval supplied by the caller.
	 * @param int                  $now             Unix time of the decision.
	 * @return bool Whether the record is an unconsumed exact match.
	 */
	public static function matches( array $record, string $action, int $post_id, string $modified_token, string $approval_token, int $now ): bool {
		$expected_keys = array( 'action', 'post_id', 'modified_token', 'token_hash', 'expires_at', 'consumed_at' );

		if ( array_keys( $record ) !== $expected_keys
			|| ! is_string( $record['action'] )
			|| ! is_int( $record['post_id'] )
			|| ! is_string( $record['modified_token'] )
			|| ! is_string( $record['token_hash'] )
			|| ! is_int( $record['expires_at'] )
			|| ( ! is_int( $record['consumed_at'] ) && null !== $record['consumed_at'] )
			|| null !== $record['consumed_at']
			|| $now >= $record['expires_at'] ) {
			return false;
		}

		return hash_equals( $record['action'], $action )
			&& $record['post_id'] === $post_id
			&& hash_equals( $record['modified_token'], $modified_token )
			&& hash_equals( $record['token_hash'], hash( 'sha256', $approval_token ) );
	}
}
