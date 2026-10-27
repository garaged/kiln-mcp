<?php
/**
 * Fixed, redacted installed-theme metadata records.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ThemeManagement;

/**
 * Produces only the M13 installed-theme inventory contract.
 */
final class ThemeSnapshot {
	/**
	 * Builds one allow-listed installed-theme record.
	 *
	 * @param string $name       Theme display name.
	 * @param string $stylesheet Theme stylesheet identifier.
	 * @param string $version    Installed theme version.
	 * @param bool   $active     Whether WordPress currently activates it.
	 * @param bool   $has_parent Whether a parent theme exists.
	 * @param bool   $update     Whether WordPress reports an update.
	 * @return array<string, bool|string> Fixed redacted metadata.
	 */
	public static function installed(
		string $name,
		string $stylesheet,
		string $version,
		bool $active,
		bool $has_parent,
		bool $update,
	): array {
		return array(
			'name'       => $name,
			'stylesheet' => $stylesheet,
			'version'    => $version,
			'active'     => $active,
			'has_parent' => $has_parent,
			'update'     => $update,
			'identity'   => hash( 'sha256', $version ),
		);
	}
}
