<?php
/**
 * Immutable M7 extension ability descriptor.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\\Extension;

use Closure;

/**
 * Holds one explicitly declared read-only extension ability.
 *
 * This record has no registration side effects.
 */
final readonly class ExtensionAbility {
	/**
	 * Creates one declared read-only extension ability.
	 *
	 * @param string               $id            Normalized extension-local ID.
	 * @param string               $label         Human-readable label.
	 * @param string               $description   Fixed capability description.
	 * @param array<string, mixed> $input_schema  Closed input object schema.
	 * @param array<string, mixed> $output_schema Closed output object schema.
	 * @param Closure              $executor      Installed extension executor.
	 */
	public function __construct(
		public string $id,
		public string $label,
		public string $description,
		public array $input_schema,
		public array $output_schema,
		public Closure $executor,
	) {
	}
}
