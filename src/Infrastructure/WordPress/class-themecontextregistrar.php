<?php
/**
 * WordPress Abilities API adapter for bounded theme context.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use InvalidArgumentException;
use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use KilnMcp\Capability\ThemeContext\PagedThemeContextRequest;
use KilnMcp\Capability\ThemeContext\ThemeContextSnapshot;
use WP_Error;

/**
 * Registers M10 active-theme metadata discovery without source or mutation.
 */
final class ThemeContextRegistrar {
	private const CATEGORY = 'kiln-theme-context';

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param AuditReceiptStore $audit_store Locally retained receipt store.
	 */
	public function __construct(
		private readonly AuditReceiptStore $audit_store,
	) {
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @return void
	 */
	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Kiln theme context', 'kiln-mcp' ),
				'description' => __( 'Bounded, read-only active-theme context provided by Kiln MCP.', 'kiln-mcp' ),
			)
		);
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		$this->register( AuditReceipt::GET_ACTIVE_THEME_CONTEXT_ABILITY_NAME, __( 'Get active theme context', 'kiln-mcp' ), array( $this, 'get_active_theme_context' ), array( $this, 'can_get_active_theme_context' ), self::empty_input_schema(), self::active_theme_output_schema() );
		$this->register( AuditReceipt::LIST_BLOCK_TEMPLATES_ABILITY_NAME, __( 'List block templates', 'kiln-mcp' ), array( $this, 'list_block_templates' ), array( $this, 'can_list_block_templates' ), self::page_input_schema(), self::template_output_schema() );
		$this->register( AuditReceipt::LIST_BLOCK_TEMPLATE_PARTS_ABILITY_NAME, __( 'List block template parts', 'kiln-mcp' ), array( $this, 'list_block_template_parts' ), array( $this, 'can_list_block_template_parts' ), self::page_input_schema(), self::template_part_output_schema() );
		$this->register( AuditReceipt::LIST_ACTIVE_THEME_PATTERNS_ABILITY_NAME, __( 'List active theme patterns', 'kiln-mcp' ), array( $this, 'list_active_theme_patterns' ), array( $this, 'can_list_active_theme_patterns' ), self::page_input_schema(), self::pattern_output_schema() );
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @return bool Whether the active identity may inspect active-theme metadata.
	 */
	public function can_get_active_theme_context(): bool {
		return $this->can( AuditReceipt::GET_ACTIVE_THEME_CONTEXT_ABILITY_NAME );
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @return bool Whether the active identity may inspect block templates.
	 */
	public function can_list_block_templates(): bool {
		return $this->can( AuditReceipt::LIST_BLOCK_TEMPLATES_ABILITY_NAME );
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @return bool Whether the active identity may inspect block template parts.
	 */
	public function can_list_block_template_parts(): bool {
		return $this->can( AuditReceipt::LIST_BLOCK_TEMPLATE_PARTS_ABILITY_NAME );
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @return bool Whether the active identity may inspect active-theme patterns.
	 */
	public function can_list_active_theme_patterns(): bool {
		return $this->can( AuditReceipt::LIST_ACTIVE_THEME_PATTERNS_ABILITY_NAME );
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param string $ability Exact ability identifier.
	 * @return bool Whether the active identity may inspect theme context.
	 */
	private function can( string $ability ): bool {
		$allowed = current_user_can( 'edit_theme_options' );
		if ( ! $allowed ) {
			$this->audit_store->append( AuditReceipt::create( $ability, 'denied', 'not_edit_theme_options', 0, gmdate( 'c' ) ) );
		}

		return $allowed;
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param array<string, mixed> $input Closed empty input.
	 * @return array<string, bool|string>|WP_Error Fixed active-theme metadata or refusal.
	 */
	public function get_active_theme_context( array $input ): array|WP_Error {
		if ( array() !== $input ) {
			return $this->deny( AuditReceipt::GET_ACTIVE_THEME_CONTEXT_ABILITY_NAME, 'invalid_input', 'kiln_theme_context_invalid_input' );
		}

		$theme  = wp_get_theme();
		$record = ThemeContextSnapshot::active_theme(
			(string) $theme->get( 'Name' ),
			(string) $theme->get_stylesheet(),
			(string) $theme->get( 'Version' ),
			false !== $theme->parent(),
			wp_is_block_theme(),
		);

		$this->audit_store->append( AuditReceipt::create( AuditReceipt::GET_ACTIVE_THEME_CONTEXT_ABILITY_NAME, 'allowed', 'edit_theme_options', 1, gmdate( 'c' ) ) );

		return $record;
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param array<string, mixed> $input Closed page input.
	 * @return array<string, array<int, array<string, bool|string>>>|WP_Error Fixed metadata page or refusal.
	 */
	public function list_block_templates( array $input ): array|WP_Error {
		return $this->list_templates( 'wp_template', AuditReceipt::LIST_BLOCK_TEMPLATES_ABILITY_NAME, $input );
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param array<string, mixed> $input Closed page input.
	 * @return array<string, array<int, array<string, bool|string>>>|WP_Error Fixed metadata page or refusal.
	 */
	public function list_block_template_parts( array $input ): array|WP_Error {
		return $this->list_templates( 'wp_template_part', AuditReceipt::LIST_BLOCK_TEMPLATE_PARTS_ABILITY_NAME, $input );
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param array<string, mixed> $input Closed page input.
	 * @return array<string, array<int, array<string, array<int, string>|string>>>|WP_Error Fixed metadata page or refusal.
	 */
	public function list_active_theme_patterns( array $input ): array|WP_Error {
		$request = $this->request( $input, AuditReceipt::LIST_ACTIVE_THEME_PATTERNS_ABILITY_NAME );
		if ( $request instanceof WP_Error ) {
			return $request;
		}

		if ( ! wp_is_block_theme() ) {
			return $this->paged_allowed( AuditReceipt::LIST_ACTIVE_THEME_PATTERNS_ABILITY_NAME, array(), $request );
		}

		$theme = wp_get_theme();
		$items = array();
		foreach ( $theme->get_block_patterns() as $pattern ) {
			if (
				! is_array( $pattern )
				|| ! isset( $pattern['slug'], $pattern['title'] )
				|| ! is_string( $pattern['slug'] )
				|| ! is_string( $pattern['title'] )
			) {
				continue;
			}

			$categories = array();
			if ( isset( $pattern['categories'] ) && is_array( $pattern['categories'] ) ) {
				foreach ( $pattern['categories'] as $category ) {
					if ( is_string( $category ) ) {
						$categories[] = $category;
					}
				}
			}
			$items[] = ThemeContextSnapshot::pattern( $pattern['slug'], $pattern['title'], array_slice( $categories, 0, PagedThemeContextRequest::MAX_ITEMS ) );
		}

		return $this->paged_allowed( AuditReceipt::LIST_ACTIVE_THEME_PATTERNS_ABILITY_NAME, $items, $request );
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param string               $type    WordPress block-template type.
	 * @param string               $ability Exact ability identifier.
	 * @param array<string, mixed> $input   Closed page input.
	 * @return array<string, array<int, array<string, bool|string>>>|WP_Error Fixed metadata page or refusal.
	 */
	private function list_templates( string $type, string $ability, array $input ): array|WP_Error {
		$request = $this->request( $input, $ability );
		if ( $request instanceof WP_Error ) {
			return $request;
		}

		if ( ! wp_is_block_theme() ) {
			return $this->paged_allowed( $ability, array(), $request );
		}

		$stylesheet = get_stylesheet();
		$items      = array();
		foreach ( get_block_templates( array(), $type ) as $template ) {
			if (
				! is_object( $template )
				|| ! isset( $template->theme, $template->slug, $template->title, $template->source )
				|| $stylesheet !== $template->theme
				|| ! is_string( $template->slug )
				|| ! is_string( $template->title )
				|| ! is_string( $template->source )
			) {
				continue;
			}

			$origin     = isset( $template->origin ) && is_string( $template->origin ) ? $template->origin : $template->source;
			$customized = 'custom' === $template->source;
			if ( 'wp_template_part' === $type ) {
				$area    = isset( $template->area ) && is_string( $template->area ) ? $template->area : '';
				$items[] = ThemeContextSnapshot::template_part( $template->slug, $template->title, $origin, $customized, $area );
				continue;
			}
			$items[] = ThemeContextSnapshot::template( $template->slug, $template->title, $origin, $customized );
		}

		return $this->paged_allowed( $ability, $items, $request );
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param array<string, mixed> $input   Closed page input.
	 * @param string               $ability Exact ability identifier.
	 * @return PagedThemeContextRequest|WP_Error Valid request or stable refusal.
	 */
	private function request( array $input, string $ability ): PagedThemeContextRequest|WP_Error {
		try {
			return PagedThemeContextRequest::from_input( $input );
		} catch ( InvalidArgumentException ) {
			return $this->deny( $ability, 'invalid_input', 'kiln_theme_context_invalid_input' );
		}
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param string                           $ability Ability identifier.
	 * @param array<int, array<string, mixed>> $items   Candidate fixed records.
	 * @param PagedThemeContextRequest         $request Bounded page request.
	 * @return array<string, array<int, array<string, mixed>>> Fixed page.
	 */
	private function paged_allowed( string $ability, array $items, PagedThemeContextRequest $request ): array {
		usort(
			$items,
			static function ( array $left, array $right ): int {
				return strcmp( (string) $left['slug'], (string) $right['slug'] );
			}
		);
		$page = array_slice( $items, ( $request->page - 1 ) * $request->limit, $request->limit );
		$this->audit_store->append( AuditReceipt::create( $ability, 'allowed', 'edit_theme_options', count( $page ), gmdate( 'c' ) ) );

		return array( 'items' => $page );
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param string $ability Ability identifier.
	 * @param string $capability_class Redacted cause class.
	 * @param string $code    Stable public error code.
	 * @return WP_Error Stable refusal.
	 */
	private function deny( string $ability, string $capability_class, string $code ): WP_Error {
		$this->audit_store->append( AuditReceipt::create( $ability, 'denied', $capability_class, 0, gmdate( 'c' ) ) );

		return new WP_Error( $code, __( 'Theme context is unavailable.', 'kiln-mcp' ) );
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param string               $name       Ability identifier.
	 * @param string               $label      User-facing label.
	 * @param callable             $execute    Fixed executor.
	 * @param callable             $permission Fixed permission callback.
	 * @param array<string, mixed> $input      Closed input schema.
	 * @param array<string, mixed> $output     Closed output schema.
	 * @return void
	 */
	private function register( string $name, string $label, callable $execute, callable $permission, array $input, array $output ): void {
		wp_register_ability(
			$name,
			array(
				'label'               => $label,
				'description'         => __( 'Bounded, read-only active-theme metadata provided by Kiln MCP.', 'kiln-mcp' ),
				'category'            => self::CATEGORY,
				'input_schema'        => $input,
				'output_schema'       => $output,
				'execute_callback'    => $execute,
				'permission_callback' => $permission,
				'meta'                => array(
					'annotations'  => array(
						'readonly'   => true,
						'idempotent' => true,
					),
					'public'       => true,
					'show_in_rest' => false,
				),
			)
		);
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function empty_input_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(),
			'required'             => array(),
		);
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function page_input_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'limit' => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => PagedThemeContextRequest::MAX_ITEMS,
				),
				'page'  => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
			'required'             => array( 'limit', 'page' ),
		);
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function active_theme_output_schema(): array {
		return self::record_schema(
			array(
				'name'           => array( 'type' => 'string' ),
				'stylesheet'     => array( 'type' => 'string' ),
				'version'        => array( 'type' => 'string' ),
				'has_parent'     => array( 'type' => 'boolean' ),
				'is_block_theme' => array( 'type' => 'boolean' ),
			)
		);
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function template_output_schema(): array {
		return self::list_schema(
			array(
				'slug'       => array( 'type' => 'string' ),
				'title'      => array( 'type' => 'string' ),
				'origin'     => array( 'type' => 'string' ),
				'customized' => array( 'type' => 'boolean' ),
			)
		);
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function template_part_output_schema(): array {
		return self::list_schema(
			array(
				'slug'       => array( 'type' => 'string' ),
				'title'      => array( 'type' => 'string' ),
				'origin'     => array( 'type' => 'string' ),
				'customized' => array( 'type' => 'boolean' ),
				'area'       => array( 'type' => 'string' ),
			)
		);
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function pattern_output_schema(): array {
		return self::list_schema(
			array(
				'slug'       => array( 'type' => 'string' ),
				'title'      => array( 'type' => 'string' ),
				'categories' => array(
					'type'     => 'array',
					'maxItems' => PagedThemeContextRequest::MAX_ITEMS,
					'items'    => array( 'type' => 'string' ),
				),
			)
		);
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param array<string, mixed> $properties Fixed field map.
	 * @return array<string, mixed> Closed record schema.
	 */
	private static function record_schema( array $properties ): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => $properties,
			'required'             => array_keys( $properties ),
		);
	}

	/**
	 * Applies the bounded theme-context contract.
	 *
	 * @param array<string, mixed> $properties Fixed field map.
	 * @return array<string, mixed> Closed list schema.
	 */
	private static function list_schema( array $properties ): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'items' => array(
					'type'     => 'array',
					'maxItems' => PagedThemeContextRequest::MAX_ITEMS,
					'items'    => self::record_schema( $properties ),
				),
			),
			'required'             => array( 'items' ),
		);
	}
}
