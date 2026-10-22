<?php
/**
 * Closed set of M5 lifecycle effects.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ContentWorkflow;

/**
 * Names the only content workflow effects admitted by M5.
 */
final class WorkflowAction {
	public const PUBLISH  = 'publish';
	public const SCHEDULE = 'schedule';
	public const TRASH    = 'trash';
	public const RESTORE  = 'restore';

	/**
	 * Checks whether an action belongs to the closed M5 effect set.
	 *
	 * @param string $action Candidate action.
	 * @return bool Whether the action is supported.
	 */
	public static function is_supported( string $action ): bool {
		return in_array( $action, array( self::PUBLISH, self::SCHEDULE, self::TRASH, self::RESTORE ), true );
	}

	/**
	 * Checks whether an action needs a future publish time.
	 *
	 * @param string $action Supported action.
	 * @return bool Whether publish_at is required.
	 */
	public static function requires_publish_at( string $action ): bool {
		return self::SCHEDULE === $action;
	}
}
