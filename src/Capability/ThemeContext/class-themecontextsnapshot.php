<?php
/**
 * Pure allow-listed records for theme context discovery.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\ThemeContext;

/**
 * Builds only output fields admitted by the M10 contract.
 */
final class ThemeContextSnapshot {
	/**
	 * Builds one active-theme metadata record.
	 *
	 * @param string $name           Theme display name.
	 * @param string $stylesheet     Theme stylesheet identifier.
	 * @param string $version        Theme version.
	 * @param bool   $has_parent     Whether a parent theme exists.
	 * @param bool   $is_block_theme Whether the active theme is block based.
	 * @return array<string, bool|string> Fixed active-theme record.
	 */
	public static function active_theme(
		string $name,
		string $stylesheet,
		string $version,
		bool $has_parent,
		bool $is_block_theme,
	): array {
		return array(
			'name'           => $name,
			'stylesheet'     => $stylesheet,
			'version'        => $version,
			'has_parent'     => $has_parent,
			'is_block_theme' => $is_block_theme,
		);
	}

	/**
	 * Builds one template metadata record.
	 *
	 * @param string $slug       Template slug.
	 * @param string $title      Template title.
	 * @param string $origin     Template origin class.
	 * @param bool   $customized Whether template content was customized.
	 * @return array<string, bool|string> Fixed template record.
	 */
	public static function template( string $slug, string $title, string $origin, bool $customized ): array {
		return array(
			'slug'       => $slug,
			'title'      => $title,
			'origin'     => $origin,
			'customized' => $customized,
		);
	}

	/**
	 * Builds one template-part metadata record.
	 *
	 * @param string $slug       Template-part slug.
	 * @param string $title      Template-part title.
	 * @param string $origin     Template-part origin class.
	 * @param bool   $customized Whether template-part content was customized.
	 * @param string $area       Template-part area.
	 * @return array<string, bool|string> Fixed template-part record.
	 */
	public static function template_part( string $slug, string $title, string $origin, bool $customized, string $area ): array {
		return array(
			'slug'       => $slug,
			'title'      => $title,
			'origin'     => $origin,
			'customized' => $customized,
			'area'       => $area,
		);
	}

	/**
	 * Builds one active-theme pattern metadata record.
	 *
	 * @param string             $slug       Pattern slug.
	 * @param string             $title      Pattern title.
	 * @param array<int, string> $categories Fixed category names.
	 * @return array<string, array<int, string>|string> Fixed pattern record.
	 */
	public static function pattern( string $slug, string $title, array $categories ): array {
		return array(
			'slug'       => $slug,
			'title'      => $title,
			'categories' => $categories,
		);
	}
}
