<?php
/**
 * Default-deny policy model for future capability registration.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Domain\Policy;

use InvalidArgumentException;

/**
 * Immutable policy deciding whether a declared tool may be exposed.
 *
 * A caller's WordPress capability is necessary but never sufficient. A tool
 * must also be explicitly permitted by this product policy.
 */
final readonly class ToolPolicy {
	/**
	 * @param array<string, ToolRisk> $allowed_tools Explicit allow-list keyed by stable tool ID.
	 */
	private function __construct(
		private array $allowed_tools,
	) {
	}

	/**
	 * Produces the safe baseline with no exposed tools.
	 *
	 * @return self
	 */
	public static function deny_by_default(): self {
		return new self( array() );
	}

	/**
	 * Produces a policy allowing only declared tool IDs at their declared risk.
	 *
	 * @param array<string, ToolRisk> $allowed_tools Explicit allow-list.
	 * @return self
	 */
	public static function from_allow_list( array $allowed_tools ): self {
		foreach ( $allowed_tools as $tool_id => $risk ) {
			if ( ! self::is_valid_tool_id( $tool_id ) || ! $risk instanceof ToolRisk ) {
				throw new InvalidArgumentException( 'Tool policies require stable IDs and ToolRisk values.' );
			}
		}

		return new self( $allowed_tools );
	}

	/**
	 * Whether the product policy permits this tool at the requested risk.
	 *
	 * @param string   $tool_id Tool identifier.
	 * @param ToolRisk $risk Requested risk.
	 * @return bool
	 */
	public function permits( string $tool_id, ToolRisk $risk ): bool {
		return isset( $this->allowed_tools[ $tool_id ] ) && $this->allowed_tools[ $tool_id ] === $risk;
	}

	/**
	 * @param string $tool_id Tool identifier.
	 * @return bool
	 */
	private static function is_valid_tool_id( string $tool_id ): bool {
		return 1 === preg_match( '/^[a-z][a-z0-9_]*\/[a-z][a-z0-9_]*$/', $tool_id );
	}
}
