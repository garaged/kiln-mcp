<?php
/**
 * Atomic manifest admission registry.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Extension;

use InvalidArgumentException;
use LogicException;

/**
 * Retains only non-colliding complete extension manifests.
 */
final class ExtensionRegistry {
	/**
	 * Maps canonical extension IDs to admitted manifests.
	 *
	 * @var array<string, ExtensionManifest>
	 */
	private array $manifests = array();

	/**
	 * Admits one complete manifest or refuses it without mutation.
	 *
	 * @param ExtensionManifest $manifest Candidate manifest.
	 * @return void
	 * @throws InvalidArgumentException When an ID or ability name collides.
	 */
	public function register( ExtensionManifest $manifest ): void {
		$this->register_all( array( $manifest ) );
	}

	/**
	 * Atomically admits a batch of complete manifests.
	 *
	 * @param array<int, mixed> $manifests Candidate manifests.
	 * @return void
	 * @throws InvalidArgumentException When a declaration is invalid or collides.
	 */
	public function register_all( array $manifests ): void {
		$staged = new self();

		foreach ( $this->manifests as $manifest ) {
			$staged->register_one( $manifest );
		}

		foreach ( $manifests as $manifest ) {
			if ( ! $manifest instanceof ExtensionManifest ) {
				throw new InvalidArgumentException( 'Extension manifest declaration is invalid.' );
			}

			$staged->register_one( $manifest );
		}

		$this->manifests = $staged->manifests;
	}

	/**
	 * Returns admitted manifests in registration order.
	 *
	 * @return array<string, ExtensionManifest> Admitted manifests.
	 */
	public function manifests(): array {
		return $this->manifests;
	}

	/**
	 * Commits one manifest into a staging registry.
	 *
	 * @param ExtensionManifest $manifest Candidate manifest.
	 * @return void
	 * @throws InvalidArgumentException When an ID or ability name collides.
	 */
	private function register_one( ExtensionManifest $manifest ): void {
		if ( isset( $this->manifests[ $manifest->id ] ) || $this->has_ability_collision( $manifest ) ) {
			throw new InvalidArgumentException( 'Extension manifest conflicts with an admitted manifest.' );
		}

		$this->manifests[ $manifest->id ] = $manifest;
	}

	/**
	 * Checks whether one candidate reuses an admitted ability.
	 *
	 * @param ExtensionManifest $candidate Candidate manifest.
	 * @return bool Whether an ability name already exists.
	 * @throws LogicException When an admitted manifest violates its invariant.
	 */
	private function has_ability_collision( ExtensionManifest $candidate ): bool {
		$names = array();
		foreach ( $this->manifests as $manifest ) {
			foreach ( $manifest->abilities as $ability ) {
				if ( ! $ability instanceof ExtensionAbility ) {
					throw new LogicException( 'An admitted manifest violated its ability invariant.' );
				}
				$names[ $manifest->ability_name( $ability ) ] = true;
			}
		}

		foreach ( $candidate->abilities as $ability ) {
			if ( ! $ability instanceof ExtensionAbility ) {
				throw new LogicException( 'An admitted manifest violated its ability invariant.' );
			}
			if ( isset( $names[ $candidate->ability_name( $ability ) ] ) ) {
				return true;
			}
		}

		return false;
	}
}
