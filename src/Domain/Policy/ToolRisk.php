<?php
/**
 * Capability risk classes.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Domain\Policy;

/**
 * A tool's maximum permitted effect, independent of the caller's capability.
 */
enum ToolRisk: string {
	case READ = 'read';
	case DRAFT_WRITE = 'draft_write';
	case PUBLISH = 'publish';
	case DESTRUCTIVE = 'destructive';
}
