<?php
/**
 * Local, bounded, hash-only workflow approval store.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use KilnMcp\Capability\ContentWorkflow\WorkflowApproval;

/**
 * Issues and atomically claims local administrator approvals.
 */
final class WorkflowApprovalStore {
	private const OPTION_NAME   = 'kiln_mcp_workflow_approvals';
	private const LOCK_PREFIX   = 'kiln_mcp_workflow_claim_';
	private const MAX_APPROVALS = 50;

	/**
	 * Issues one short-lived approval without persisting its raw value.
	 *
	 * The caller is responsible for enforcing the local administrator gate.
	 *
	 * @param string $action         Exact lifecycle action.
	 * @param int    $post_id        Exact post identifier.
	 * @param string $modified_token Current post version token.
	 * @return string Raw token to show once to the local administrator.
	 * @throws \RuntimeException When durable local storage refuses the record.
	 */
	public function issue( string $action, int $post_id, string $modified_token ): string {
		$this->prune();

		$approval_token = bin2hex( random_bytes( 32 ) );
		$records        = $this->records();
		$records[]      = WorkflowApproval::create( $action, $post_id, $modified_token, $approval_token, time() );
		$records        = array_slice( $records, -self::MAX_APPROVALS );

		if ( ! update_option( self::OPTION_NAME, $records, false ) ) {
			throw new \RuntimeException( 'The workflow approval could not be stored.' );
		}

		return $approval_token;
	}

	/**
	 * Claims one exact current approval before an effect is attempted.
	 *
	 * A lock option provides a database-backed, fail-closed first-claimer gate.
	 * A claim remains locked if persistence fails, intentionally preferring
	 * refusal over a possible replay.
	 *
	 * @param string $action         Exact lifecycle action.
	 * @param int    $post_id        Exact post identifier.
	 * @param string $modified_token Current post version token.
	 * @param string $approval_token Raw caller-supplied approval.
	 * @return bool Whether this request consumed the exact approval.
	 */
	public function consume( string $action, int $post_id, string $modified_token, string $approval_token ): bool {
		$token_hash = hash( 'sha256', $approval_token );
		$lock_name  = self::LOCK_PREFIX . $token_hash;

		if ( ! add_option( $lock_name, '1', '', false ) ) {
			return false;
		}

		$records = $this->records();
		foreach ( $records as $index => $record ) {
			if ( ! WorkflowApproval::matches( $record, $action, $post_id, $modified_token, $approval_token, time() ) ) {
				continue;
			}

			$records[ $index ]['consumed_at'] = time();

			return update_option( self::OPTION_NAME, $records, false );
		}

		if ( ! delete_option( $lock_name ) ) {
			return false;
		}

		return false;
	}

	/**
	 * Drops expired and consumed approval records and their claim locks.
	 *
	 * @return void
	 */
	private function prune(): void {
		$now     = time();
		$records = array();

		foreach ( $this->records() as $record ) {
			if ( ! isset( $record['token_hash'], $record['expires_at'] )
				|| ! is_string( $record['token_hash'] )
				|| ! is_int( $record['expires_at'] )
				|| $now >= $record['expires_at'] ) {
				if ( isset( $record['token_hash'] ) && is_string( $record['token_hash'] ) && ! delete_option( self::LOCK_PREFIX . $record['token_hash'] ) ) {
					continue;
				}
				continue;
			}

			$records[] = $record;
		}

		if ( ! update_option( self::OPTION_NAME, array_slice( $records, -self::MAX_APPROVALS ), false ) ) {
			return;
		}
	}

	/**
	 * Loads only an array of candidate records from local option storage.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function records(): array {
		$records = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $records ) ) {
			return array();
		}

		$valid_records = array();
		foreach ( $records as $record ) {
			if ( is_array( $record ) ) {
				/**
				 * Narrows untrusted option storage to a string-keyed record.
				 *
				 * @var array<string, mixed> $record
				 */
				$valid_records[] = $record;
			}
		}

		return $valid_records;
	}
}
