<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use InvalidArgumentException;
use KilnMcp\Capability\ThemeManagement\ThemeAction;
use KilnMcp\Capability\ThemeManagement\ThemeApproval;
use PHPUnit\Framework\TestCase;

final class ThemeApprovalTest extends TestCase {
	public function test_it_binds_one_approval_to_the_exact_theme_state_and_action(): void {
		$token = str_repeat( 'a', 64 );
		$digest = str_repeat( 'b', 64 );
		$record = ThemeApproval::create( ThemeAction::ACTIVATE, 'candidate-theme', $digest, 'previous-theme', $token, 100 );

		self::assertTrue( ThemeApproval::matches( $record, ThemeAction::ACTIVATE, 'candidate-theme', $digest, 'previous-theme', $token, 101 ) );
		self::assertFalse( ThemeApproval::matches( $record, ThemeAction::UPDATE, 'candidate-theme', $digest, 'previous-theme', $token, 101 ) );
		self::assertFalse( ThemeApproval::matches( $record, ThemeAction::ACTIVATE, 'other-theme', $digest, 'previous-theme', $token, 101 ) );
		self::assertFalse( ThemeApproval::matches( $record, ThemeAction::ACTIVATE, 'candidate-theme', $digest, 'other-theme', $token, 101 ) );
		self::assertFalse( ThemeApproval::matches( $record, ThemeAction::ACTIVATE, 'candidate-theme', $digest, 'previous-theme', $token, 700 ) );
	}

	public function test_it_refuses_malformed_or_generic_action_bindings(): void {
		$this->expectException( InvalidArgumentException::class );

		ThemeApproval::create( 'delete', '../theme', 'not-a-digest', 'previous-theme', 'not-a-token', 100 );
	}
}
