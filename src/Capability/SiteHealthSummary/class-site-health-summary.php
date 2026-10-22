<?php
/**
 * Bounded, read-only runtime summary.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\SiteHealthSummary;

/**
 * Defines the stable data contract for the first Kiln ability.
 */
final class SiteHealthSummary {
	public const ABILITY_NAME = 'kiln/site-health-summary';

	public function __construct(
		private readonly string $kiln_version,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(),
			'additionalProperties' => false,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function output_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'kiln_version'            => array( 'type' => 'string' ),
				'wordpress_version'       => array( 'type' => 'string' ),
				'php_version'             => array( 'type' => 'string' ),
				'is_multisite'            => array( 'type' => 'boolean' ),
				'active_theme_stylesheet' => array( 'type' => 'string' ),
				'status'                  => array(
					'type' => 'string',
					'enum' => array( 'available' ),
				),
			),
			'required'             => array(
				'kiln_version',
				'wordpress_version',
				'php_version',
				'is_multisite',
				'active_theme_stylesheet',
				'status',
			),
		);
	}

	/**
	 * @return array<string, bool|string>
	 */
	public function execute(
		string $wordpress_version,
		string $php_version,
		bool $is_multisite,
		string $active_theme_stylesheet,
	): array {
		return array(
			'kiln_version'            => $this->kiln_version,
			'wordpress_version'       => $this->major_minor_version( $wordpress_version ),
			'php_version'             => $this->major_minor_version( $php_version ),
			'is_multisite'            => $is_multisite,
			'active_theme_stylesheet' => $active_theme_stylesheet,
			'status'                  => 'available',
		);
	}

	private function major_minor_version( string $version ): string {
		if ( 1 !== preg_match( '/^([0-9]+\.[0-9]+)/', $version, $matches ) ) {
			return 'unknown';
		}

		return $matches[1];
	}
}
