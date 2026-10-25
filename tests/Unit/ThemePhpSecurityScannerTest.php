<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use KilnMcp\Capability\ThemeManagement\ThemePhpSecurityScanner;
use PHPUnit\Framework\TestCase;

final class ThemePhpSecurityScannerTest extends TestCase {
	/**
	 * @dataProvider blocked_source_provider
	 *
	 * @param string $source Expected-to-be-refused PHP source.
	 * @param string $reason Fixed redacted refusal category.
	 */
	public function test_it_refuses_high_risk_php_without_exposing_matches( string $source, string $reason ): void {
		self::assertSame( $reason, ThemePhpSecurityScanner::rejection_reason( $source ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function blocked_source_provider(): array {
		return array(
			'eval' => array( '<?php eval( $payload );', 'execution' ),
			'shell execution' => array( '<?php shell_exec( $command );', 'execution' ),
			'legacy preg replace' => array( '<?php preg_replace( "/x/e", $replacement, $value );', 'preg_replace_e' ),
			'dynamic require' => array( '<?php require $candidate;', 'dynamic_include' ),
			'obfuscated payload' => array( '<?php $payload = base64_decode( $value );', 'obfuscation' ),
			'backtick execution' => array( '<?php $output = ' . chr( 96 ) . 'id' . chr( 96 ) . ';', 'backtick_execution' ),
		);
	}

	public function test_it_allows_normal_theme_php_without_returning_source_or_match_detail(): void {
		$source = '<?php add_action( "after_setup_theme", "kiln_theme_setup" );';

		self::assertNull( ThemePhpSecurityScanner::rejection_reason( $source ) );
	}
}
