<?php
/**
 * Closed input contract for one approval-gated workflow effect.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ContentWorkflow;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Validates an exact target, current version, one-time approval, and optional schedule.
 */
final class WorkflowRequest {
	/**
	 * Creates an immutable workflow request.
	 *
	 * @param int         $post_id                 Positive WordPress post ID.
	 * @param string      $expected_modified_token Current post version token.
	 * @param string      $approval_token          Opaque one-time administrator approval.
	 * @param string|null $publish_at              Future UTC time for schedule.
	 */
	private function __construct(
		public readonly int $post_id,
		public readonly string $expected_modified_token,
		public readonly string $approval_token,
		public readonly ?string $publish_at,
	) {
	}

	/**
	 * Validates one exact action's closed request shape.
	 *
	 * @param string               $action Supported M5 action.
	 * @param array<string, mixed> $input Ability input.
	 * @return self Validated request.
	 * @throws InvalidArgumentException When the action or input falls outside M5.
	 */
	public static function from_input( string $action, array $input ): self {
		if ( ! WorkflowAction::is_supported( $action ) ) {
			throw new InvalidArgumentException( 'Workflow action is not supported.' );
		}

		$expected_keys = WorkflowAction::requires_publish_at( $action )
			? array( 'post_id', 'expected_modified_token', 'approval_token', 'publish_at' )
			: array( 'post_id', 'expected_modified_token', 'approval_token' );

		if ( $expected_keys !== array_keys( $input )
			|| ! is_int( $input['post_id'] )
			|| 1 > $input['post_id']
			|| ! is_string( $input['expected_modified_token'] )
			|| ! is_string( $input['approval_token'] )
			|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/D', $input['expected_modified_token'] )
			|| 1 !== preg_match( '/\A[a-f0-9]{64}\z/D', $input['approval_token'] ) ) {
			throw new InvalidArgumentException( 'Workflow input is invalid.' );
		}

		$publish_at = null;
		if ( WorkflowAction::requires_publish_at( $action ) ) {
			if ( ! is_string( $input['publish_at'] ) ) {
				throw new InvalidArgumentException( 'Workflow schedule time is invalid.' );
			}

			$parsed = DateTimeImmutable::createFromFormat( DateTimeInterface::ATOM, $input['publish_at'], new DateTimeZone( 'UTC' ) );
			if ( ! $parsed instanceof DateTimeImmutable || $input['publish_at'] !== $parsed->format( DateTimeInterface::ATOM ) || 0 !== $parsed->getOffset() ) {
				throw new InvalidArgumentException( 'Workflow schedule time must be a canonical UTC timestamp.' );
			}
			$publish_at = $input['publish_at'];
		}

		return new self( $input['post_id'], $input['expected_modified_token'], $input['approval_token'], $publish_at );
	}
}
