<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use InvalidArgumentException;
use KilnMcp\Capability\ContentWorkflow\WorkflowAction;
use KilnMcp\Capability\ContentWorkflow\WorkflowRequest;
use PHPUnit\Framework\TestCase;

final class WorkflowRequestTest extends TestCase {
	public function test_it_accepts_an_exact_publish_request(): void {
		$request = WorkflowRequest::from_input(
			WorkflowAction::PUBLISH,
			array(
				'post_id'                 => 42,
				'expected_modified_token' => str_repeat( 'a', 64 ),
				'approval_token'          => str_repeat( 'b', 64 ),
			)
		);

		self::assertSame( 42, $request->post_id );
		self::assertNull( $request->publish_at );
	}

	public function test_it_accepts_a_canonical_utc_schedule_request(): void {
		$request = WorkflowRequest::from_input(
			WorkflowAction::SCHEDULE,
			array(
				'post_id'                 => 42,
				'expected_modified_token' => str_repeat( 'a', 64 ),
				'approval_token'          => str_repeat( 'b', 64 ),
				'publish_at'              => '2026-10-23T12:00:00+00:00',
			)
		);

		self::assertSame( '2026-10-23T12:00:00+00:00', $request->publish_at );
	}

	/**
	 * @dataProvider invalid_request_provider
	 *
	 * @param string               $action Unsupported action or valid action.
	 * @param array<string, mixed> $input Invalid workflow input.
	 */
	public function test_it_rejects_unknown_fields_tokens_and_noncanonical_times( string $action, array $input ): void {
		$this->expectException( InvalidArgumentException::class );

		WorkflowRequest::from_input( $action, $input );
	}

	/**
	 * @return array<string, array{0: string, 1: array<string, mixed>}>
	 */
	public static function invalid_request_provider(): array {
		return array(
			'unknown field' => array( WorkflowAction::PUBLISH, array( 'post_id' => 42, 'expected_modified_token' => str_repeat( 'a', 64 ), 'approval_token' => str_repeat( 'b', 64 ), 'status' => 'publish' ) ),
			'bad approval'  => array( WorkflowAction::TRASH, array( 'post_id' => 42, 'expected_modified_token' => str_repeat( 'a', 64 ), 'approval_token' => 'approval' ) ),
			'missing time'  => array( WorkflowAction::SCHEDULE, array( 'post_id' => 42, 'expected_modified_token' => str_repeat( 'a', 64 ), 'approval_token' => str_repeat( 'b', 64 ) ) ),
			'local time'    => array( WorkflowAction::SCHEDULE, array( 'post_id' => 42, 'expected_modified_token' => str_repeat( 'a', 64 ), 'approval_token' => str_repeat( 'b', 64 ), 'publish_at' => '2026-10-23T12:00:00-06:00' ) ),
		);
	}
}
