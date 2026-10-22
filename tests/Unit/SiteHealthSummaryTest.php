<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Capability\SiteHealthSummary\SiteHealthSummary;
use PHPUnit\Framework\TestCase;

final class SiteHealthSummaryTest extends TestCase {
	public function test_it_returns_only_the_allow_listed_development_diagnostics(): void {
		$summary = new SiteHealthSummary( '0.1.0-dev' );

		$result = $summary->execute( '7.1.3', '8.2.35', false, 'twentytwentyfive' );

		self::assertSame(
			array(
				'kiln_version'            => '0.1.0-dev',
				'wordpress_version'       => '7.1',
				'php_version'             => '8.2',
				'is_multisite'            => false,
				'active_theme_stylesheet' => 'twentytwentyfive',
				'status'                  => 'available',
			),
			$result
		);
		self::assertArrayNotHasKey( 'site_url', $result );
		self::assertArrayNotHasKey( 'site_title', $result );
		self::assertArrayNotHasKey( 'users', $result );
		self::assertArrayNotHasKey( 'plugins', $result );
		self::assertArrayNotHasKey( 'database', $result );
		self::assertArrayNotHasKey( 'paths', $result );
	}

	public function test_its_schema_forbids_unexpected_input_and_output_fields(): void {
		$input_schema  = SiteHealthSummary::input_schema();
		$output_schema = SiteHealthSummary::output_schema();

		self::assertFalse( $input_schema['additionalProperties'] );
		self::assertFalse( $output_schema['additionalProperties'] );
		self::assertSame(
			array(
				'kiln_version',
				'wordpress_version',
				'php_version',
				'is_multisite',
				'active_theme_stylesheet',
				'status',
			),
			$output_schema['required']
		);
	}

	public function test_it_does_not_leak_unparseable_runtime_versions(): void {
		$result = ( new SiteHealthSummary( '0.1.0-dev' ) )->execute( 'development', 'nightly', true, 'theme' );

		self::assertSame( 'unknown', $result['wordpress_version'] );
		self::assertSame( 'unknown', $result['php_version'] );
	}
}
