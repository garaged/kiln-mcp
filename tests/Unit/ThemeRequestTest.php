<?php

declare(strict_types=1);

namespace KilnMcp\Tests\Unit;

use InvalidArgumentException;
use KilnMcp\Capability\ThemeManagement\ThemeAction;
use KilnMcp\Capability\ThemeManagement\ThemeRequest;
use PHPUnit\Framework\TestCase;

final class ThemeRequestTest extends TestCase {
	public function test_it_accepts_one_closed_uploaded_theme_request(): void {
		$request = ThemeRequest::from_input(
			ThemeAction::INSTALL_UPLOADED_ZIP,
			array(
				'target'                    => 'candidate-theme',
				'identity'                  => str_repeat( 'a', 64 ),
				'expected_active_stylesheet' => 'previous-theme',
				'approval_token'            => str_repeat( 'b', 64 ),
				'artifact_id'               => str_repeat( 'c', 32 ),
			)
		);

		self::assertSame( 'candidate-theme', $request->target );
		self::assertSame( str_repeat( 'c', 32 ), $request->artifact_id );
	}

	/**
	 * @dataProvider invalid_input_provider
	 *
	 * @param string               $action Action selected by the ability.
	 * @param array<string, mixed> $input Invalid input.
	 */
	public function test_it_refuses_urls_paths_and_unknown_fields( string $action, array $input ): void {
		$this->expectException( InvalidArgumentException::class );

		ThemeRequest::from_input( $action, $input );
	}

	/**
	 * @return array<string, array{0: string, 1: array<string, mixed>}>
	 */
	public static function invalid_input_provider(): array {
		$valid = array(
			'target'                    => 'candidate-theme',
			'identity'                  => str_repeat( 'a', 64 ),
			'expected_active_stylesheet' => 'previous-theme',
			'approval_token'            => str_repeat( 'b', 64 ),
		);

		return array(
			'remote URL' => array( ThemeAction::ACTIVATE, array_merge( $valid, array( 'source' => 'https://example.invalid/theme.zip' ) ) ),
			'filesystem path' => array( ThemeAction::ACTIVATE, array_merge( $valid, array( 'target' => '../theme' ) ) ),
			'raw archive field' => array( ThemeAction::INSTALL_UPLOADED_ZIP, array_merge( $valid, array( 'archive' => 'base64' ) ) ),
			'missing local artifact' => array( ThemeAction::INSTALL_UPLOADED_ZIP, $valid ),
		);
	}
}
