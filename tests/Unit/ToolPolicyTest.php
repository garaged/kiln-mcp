<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use InvalidArgumentException;
use KilnMcp\Domain\Policy\ToolPolicy;
use KilnMcp\Domain\Policy\ToolRisk;
use PHPUnit\Framework\TestCase;

final class ToolPolicyTest extends TestCase {
	public function test_it_denies_every_tool_without_an_explicit_allow_list(): void {
		$policy = ToolPolicy::deny_by_default();

		self::assertFalse( $policy->permits( 'content/read_post', ToolRisk::READ ) );
		self::assertFalse( $policy->permits( 'content/publish_post', ToolRisk::PUBLISH ) );
	}

	public function test_it_allows_only_the_declared_tool_and_exact_risk(): void {
		$policy = ToolPolicy::from_allow_list(
			array(
				'content/read_post' => ToolRisk::READ,
			)
		);

		self::assertTrue( $policy->permits( 'content/read_post', ToolRisk::READ ) );
		self::assertFalse( $policy->permits( 'content/read_post', ToolRisk::DRAFT_WRITE ) );
		self::assertFalse( $policy->permits( 'content/list_posts', ToolRisk::READ ) );
	}

	public function test_it_rejects_unstable_or_ambiguous_tool_identifiers(): void {
		$this->expectException( InvalidArgumentException::class );

		ToolPolicy::from_allow_list(
			array(
				'content/read/post' => ToolRisk::READ,
			)
		);
	}
}
