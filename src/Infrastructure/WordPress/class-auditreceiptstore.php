<?php
/**
 * Local WordPress audit receipt store.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

/**
 * Retains a bounded collection of redacted audit receipts.
 */
final class AuditReceiptStore {
	private const OPTION_NAME  = 'kiln_mcp_audit_receipts';
	private const MAX_RECEIPTS = 100;

	/**
	 * Appends one pre-redacted receipt to local WordPress option storage.
	 *
	 * @param array<string, int|string> $receipt Receipt built by the capability contract.
	 * @return void
	 */
	public function append( array $receipt ): void {
		$receipts = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $receipts ) ) {
			$receipts = array();
		}

		$receipts[] = $receipt;
		$receipts   = array_slice( $receipts, -self::MAX_RECEIPTS );

		update_option( self::OPTION_NAME, $receipts, false );
	}
}
