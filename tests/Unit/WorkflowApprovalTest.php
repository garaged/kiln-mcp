<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Capability\ContentWorkflow\WorkflowAction;
use KilnMcp\Capability\ContentWorkflow\WorkflowApproval;
use PHPUnit\Framework\TestCase;

final class WorkflowApprovalTest extends TestCase {
	public function test_it_stores_only_a_hash_and_exact_binding(): void {
		$record = WorkflowApproval::create(
			WorkflowAction::PUBLISH,
			42,
			str_repeat( 'a', 64 ),
			str_repeat( 'b', 64 ),
			100
		);

		self::assertSame(
			array( 'action', 'post_id', 'modified_token', 'token_hash', 'expires_at', 'consumed_at' ),
			array_keys( $record )
		);
		self::assertSame( hash( 'sha256', str_repeat( 'b', 64 ) ), $record['token_hash'] );
		self::assertSame( 700, $record['expires_at'] );
		self::assertNull( $record['consumed_at'] );
		self::assertArrayNotHasKey( 'approval_token', $record );
	}

	public function test_it_accepts_only_an_unconsumed_unexpired_exact_binding(): void {
		$record = WorkflowApproval::create(
			WorkflowAction::PUBLISH,
			42,
			str_repeat( 'a', 64 ),
			str_repeat( 'b', 64 ),
			100
		);

		self::assertTrue( WorkflowApproval::matches( $record, WorkflowAction::PUBLISH, 42, str_repeat( 'a', 64 ), str_repeat( 'b', 64 ), 699 ) );
		self::assertFalse( WorkflowApproval::matches( $record, WorkflowAction::TRASH, 42, str_repeat( 'a', 64 ), str_repeat( 'b', 64 ), 699 ) );
		self::assertFalse( WorkflowApproval::matches( $record, WorkflowAction::PUBLISH, 43, str_repeat( 'a', 64 ), str_repeat( 'b', 64 ), 699 ) );
		self::assertFalse( WorkflowApproval::matches( $record, WorkflowAction::PUBLISH, 42, str_repeat( 'c', 64 ), str_repeat( 'b', 64 ), 699 ) );
		self::assertFalse( WorkflowApproval::matches( $record, WorkflowAction::PUBLISH, 42, str_repeat( 'a', 64 ), str_repeat( 'b', 64 ), 700 ) );

		$record['consumed_at'] = 699;
		self::assertFalse( WorkflowApproval::matches( $record, WorkflowAction::PUBLISH, 42, str_repeat( 'a', 64 ), str_repeat( 'b', 64 ), 699 ) );
	}
}
