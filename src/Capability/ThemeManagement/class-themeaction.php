<?php
/**
 * Closed M13 theme-management action names.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ThemeManagement;

/**
 * Prevents generic theme operations from entering the approval path.
 */
final class ThemeAction {
	public const INSTALL_WORDPRESS_ORG = 'install_wordpress_org';
	public const INSTALL_UPLOADED_ZIP  = 'install_uploaded_zip';
	public const UPDATE                = 'update';
	public const ACTIVATE              = 'activate';
	public const ROLLBACK              = 'rollback';

	/**
	 * Checks one closed action name.
	 *
	 * @param string $action Candidate action.
	 * @return bool Whether M13 supports it.
	 */
	public static function is_supported( string $action ): bool {
		return in_array(
			$action,
			array(
				self::INSTALL_WORDPRESS_ORG,
				self::INSTALL_UPLOADED_ZIP,
				self::UPDATE,
				self::ACTIVATE,
				self::ROLLBACK,
			),
			true
		);
	}
}
