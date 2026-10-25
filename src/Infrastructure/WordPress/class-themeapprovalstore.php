<?php
/**
 * Local, bounded, hash-only theme-management approval store.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use KilnMcp\Capability\ThemeManagement\ThemeApproval;

/**
 * Issues and atomically consumes administrator-issued M13 approvals.
 */
final class ThemeApprovalStore {
	private const OPTION_NAME   = 'kiln_mcp_theme_approvals';
	private const LOCK_PREFIX   = 'kiln_mcp_theme_claim_';
	private const MAX_APPROVALS = 50;

	/**
	 * Issues one short-lived exact approval without persisting its raw value.
	 *
	 * @param string $action              Exact theme action.
	 * @param string $target              Exact stylesheet or WordPress.org slug.
	 * @param string $artifact_or_version Exact identity digest.
	 * @param string $active_stylesheet   Current active stylesheet.
	 * @return string Raw token shown exactly once to the administrator.
	 */
	public function issue( string $action, string $target, string $artifact_or_version, string $active_stylesheet ): string {
		$this->prune();

		$approval_token = bin2hex( random_bytes( 32 ) );
		$records        = $this->records();
		$records[]      = ThemeApproval::create( $action, $target, $artifact_or_version, $active_stylesheet, $approval_token, time() );
		$records        = array_slice( $records, -self::MAX_APPROVALS );

		if ( ! update_option( self::OPTION_NAME, $records, false ) ) {
			throw new \RuntimeException( 'The theme approval could not be stored.' );
		}

		return $approval_token;
	}

	/**
	 * Claims one exact approval before a theme effect is attempted.
	 *
	 * @param string $action              Exact theme action.
	 * @param string $target              Exact stylesheet or WordPress.org slug.
	 * @param string $artifact_or_version Exact identity digest.
	 * @param string $active_stylesheet   Current active stylesheet.
	 * @param string $approval_token      Raw caller token.
	 * @return bool Whether the exact approval was consumed.
	 */
	public function consume( string $action, string $target, string $artifact_or_version, string $active_stylesheet, string $approval_token ): bool {
		$token_hash = hash( 'sha256', $approval_token );
		$lock_name  = self::LOCK_PREFIX . $token_hash;

		if ( ! add_option( $lock_name, '1', '', false ) ) {
			return false;
		}

		$records = $this->records();
		foreach ( $records as $index => $record ) {
			if ( ! ThemeApproval::matches( $record, $action, $target, $artifact_or_version, $active_stylesheet, $approval_token, time() ) ) {
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
	 * Drops expired records and their replay-claim locks.
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

		update_option( self::OPTION_NAME, array_slice( $records, -self::MAX_APPROVALS ), false );
	}

	/**
	 * Loads only array-shaped candidate records from local option storage.
	 *
	 * @return array<int, array<string, mixed>> Valid candidate records.
	 */
	private function records(): array {
		$records = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $records ) ) {
			return array();
		}

		$valid_records = array();
		foreach ( $records as $record ) {
			if ( is_array( $record ) ) {
				$valid_records[] = $record;
			}
		}

		return $valid_records;
	}
}
