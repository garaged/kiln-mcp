<?php
/**
 * Official adapter compatibility predicate.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

/**
 * Keeps the runtime dependency check deterministic and fail-closed.
 */
final class AdapterDependency {
	public const MINIMUM_VERSION = '0.7.1';

	/**
	 * Determines whether the supported official integration is available.
	 *
	 * @param bool        $adapter_class_available Whether the adapter class is loaded.
	 * @param bool        $abilities_api_available Whether WordPress exposes ability registration.
	 * @param string|null $adapter_version         Installed adapter version.
	 * @return bool Whether Kiln may register abilities.
	 */
	public static function is_available(
		bool $adapter_class_available,
		bool $abilities_api_available,
		?string $adapter_version,
	): bool {
		if ( ! $adapter_class_available || ! $abilities_api_available || null === $adapter_version ) {
			return false;
		}

		return version_compare( $adapter_version, self::MINIMUM_VERSION, '>=' );
	}
}
