<?php
/**
 * Atomic manifest admission registry.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Extension;

use InvalidArgumentException;

/**
 * Retains only non-colliding complete extension manifests.
 */
final class ExtensionRegistry {
	/**
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
		if ( isset( $this->manifests[ $manifest->id ] ) || $this->has_ability_collision( $manifest ) ) {
			throw new InvalidArgumentException( 'Extension manifest conflicts with an admitted manifest.' );
		}

		$this->manifests[ $manifest->id ] = $manifest;
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
	 * @param ExtensionManifest $candidate Candidate manifest.
	 * @return bool Whether an ability name already exists.
	 */
	private function has_ability_collision( ExtensionManifest $candidate ): bool {
		$names = array();
		foreach ( $this->manifests as $manifest ) {
			foreach ( $manifest->abilities as $ability ) {
				$names[ $manifest->ability_name( $ability ) ] = true;
			}
		}

		foreach ( $candidate->abilities as $ability ) {
			if ( isset( $names[ $candidate->ability_name( $ability ) ] ) ) {
				return true;
			}
		}

		return false;
	}
}
