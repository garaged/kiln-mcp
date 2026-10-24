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
