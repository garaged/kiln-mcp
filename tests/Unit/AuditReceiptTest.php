<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use PHPUnit\Framework\TestCase;

final class AuditReceiptTest extends TestCase {
	public function test_it_keeps_only_redacted_allow_listed_receipt_fields(): void {
		$receipt = AuditReceipt::create( AuditReceipt::READ_POST_ABILITY_NAME, 'allowed', 'edit_post', 1, '2026-10-22T00:00:00+00:00' );

		self::assertSame(
			array(
				'schema_version'          => '1.0',
				'timestamp'               => '2026-10-22T00:00:00+00:00',
				'ability'                 => 'kiln/read-post',
				'outcome'                 => 'allowed',
				'caller_capability_class' => 'edit_post',
				'result_count'            => 1,
			),
			$receipt
		);
		self::assertArrayNotHasKey( 'post_id', $receipt );
		self::assertArrayNotHasKey( 'user_id', $receipt );
		self::assertArrayNotHasKey( 'title', $receipt );
		self::assertArrayNotHasKey( 'content', $receipt );
		self::assertArrayNotHasKey( 'permalink', $receipt );
		self::assertArrayNotHasKey( 'input', $receipt );
	}
}
