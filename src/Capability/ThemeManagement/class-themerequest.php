<?php
/**
 * Closed input contract for one exact M13 theme effect.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ThemeManagement;

use InvalidArgumentException;

/**
 * Validates action-specific inputs without accepting URLs, paths, or source.
 */
final class ThemeRequest {
	/**
	 * @param string      $target                    Stylesheet or WordPress.org slug.
	 * @param string      $identity                  SHA-256 artifact/version identity.
	 * @param string      $expected_active_stylesheet Current active stylesheet.
	 * @param string      $approval_token            One-time local approval token.
	 * @param string|null $artifact_id               Opaque local ZIP reference, when relevant.
	 */
	private function __construct(
		public readonly string $target,
		public readonly string $identity,
		public readonly string $expected_active_stylesheet,
		public readonly string $approval_token,
		public readonly ?string $artifact_id,
	) {
	}

	/**
	 * Creates one exact request for a preselected M13 action.
	 *
	 * @param string               $action Fixed action selected by the ability.
	 * @param array<string, mixed> $input  Closed action input.
	 * @return self Validated request.
	 * @throws InvalidArgumentException When input is not exactly admitted.
	 */
	public static function from_input( string $action, array $input ): self {
		$expected_keys = array( 'target', 'identity', 'expected_active_stylesheet', 'approval_token' );
		if ( ThemeAction::INSTALL_UPLOADED_ZIP === $action ) {
			$expected_keys[] = 'artifact_id';
		}

		if ( ! ThemeAction::is_supported( $action ) || $expected_keys !== array_keys( $input ) ) {
			throw new InvalidArgumentException( 'Theme request is invalid.' );
		}

		$target = $input['target'];
		$identity = $input['identity'];
		$active = $input['expected_active_stylesheet'];
		$token = $input['approval_token'];
		$artifact_id = $input['artifact_id'] ?? null;

		if ( ! is_string( $target )
			|| ! is_string( $identity )
			|| ! is_string( $active )
			|| ! is_string( $token )
			|| ( ! is_string( $artifact_id ) && null !== $artifact_id )
			|| ! self::is_identifier( $target )
			|| ! self::is_identifier( $active )
			|| 1 !== preg_match( '/\\A[a-f0-9]{64}\\z/D', $identity )
			|| 1 !== preg_match( '/\\A[a-f0-9]{64}\\z/D', $token )
			|| ( null !== $artifact_id && 1 !== preg_match( '/\\A[a-f0-9]{32}\\z/D', $artifact_id ) ) ) {
			throw new InvalidArgumentException( 'Theme request is invalid.' );
		}

		return new self( $target, $identity, $active, $token, $artifact_id );
	}

	/**
	 * Admits the WordPress stylesheet and WordPress.org slug character subset.
	 *
	 * @param string $identifier Candidate identifier.
	 * @return bool Whether it is a bounded identifier.
	 */
	private static function is_identifier( string $identifier ): bool {
		return 1 === preg_match( '/\\A[a-z0-9][a-z0-9_-]{0,99}\\z/D', $identifier );
	}
}
