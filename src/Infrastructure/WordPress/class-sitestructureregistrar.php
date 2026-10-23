<?php
/**
 * WordPress Abilities API adapter for bounded site structure.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use InvalidArgumentException;
use KilnMcp\Capability\PostDiscovery\AuditReceipt;
use KilnMcp\Capability\SiteStructure\PagedStructureRequest;
use KilnMcp\Capability\SiteStructure\TermSeedRequest;
use WP_Error;
use WP_Term;

/**
 * Registers M6 allow-listed structure discovery and taxonomy seeding.
 */
final class SiteStructureRegistrar {
	private const CATEGORY = 'kiln-site-structure';

	/**
	 * Creates the site-structure adapter.
	 *
	 * @param AuditReceiptStore $audit_store Locally retained receipt store.
	 */
	public function __construct(
		private readonly AuditReceiptStore $audit_store,
	) {
	}

	/**
	 * Registers the site-structure capability category.
	 *
	 * @return void
	 */
	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Kiln site structure', 'kiln-mcp' ),
				'description' => __( 'Bounded site structure discovery and taxonomy seeding provided by Kiln MCP.', 'kiln-mcp' ),
			)
		);
	}

	/**
	 * Registers every explicit M6 ability.
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		$this->register( AuditReceipt::LIST_SUPPORTED_POST_TYPES_ABILITY_NAME, __( 'List supported post types', 'kiln-mcp' ), array( $this, 'list_post_types' ), array( $this, 'can_manage_options' ), self::empty_input_schema(), self::post_type_output_schema() );
		$this->register( AuditReceipt::LIST_SUPPORTED_TAXONOMIES_ABILITY_NAME, __( 'List supported taxonomies', 'kiln-mcp' ), array( $this, 'list_taxonomies' ), array( $this, 'can_manage_options' ), self::empty_input_schema(), self::taxonomy_output_schema() );
		$this->register( AuditReceipt::LIST_NAVIGATION_MENUS_ABILITY_NAME, __( 'List navigation menus', 'kiln-mcp' ), array( $this, 'list_menus' ), array( $this, 'can_edit_theme_options' ), self::empty_input_schema(), self::menu_output_schema() );
		$this->register( AuditReceipt::LIST_CATEGORIES_ABILITY_NAME, __( 'List categories', 'kiln-mcp' ), array( $this, 'list_categories' ), array( $this, 'can_manage_categories' ), self::page_input_schema(), self::term_output_schema() );
		$this->register( AuditReceipt::LIST_TAGS_ABILITY_NAME, __( 'List tags', 'kiln-mcp' ), array( $this, 'list_tags' ), array( $this, 'can_manage_categories' ), self::page_input_schema(), self::term_output_schema() );
		$this->register( AuditReceipt::CREATE_CATEGORY_ABILITY_NAME, __( 'Create category', 'kiln-mcp' ), array( $this, 'create_category' ), array( $this, 'can_manage_categories' ), self::category_seed_schema(), self::term_record_schema() );
		$this->register( AuditReceipt::CREATE_TAG_ABILITY_NAME, __( 'Create tag', 'kiln-mcp' ), array( $this, 'create_tag' ), array( $this, 'can_manage_categories' ), self::tag_seed_schema(), self::term_record_schema() );
	}

	/**
	 * Checks administrator-only registered-structure discovery.
	 *
	 * @return bool Whether the active identity may inspect registered structure.
	 */
	public function can_manage_options(): bool {
		return $this->can( 'manage_options', AuditReceipt::LIST_SUPPORTED_POST_TYPES_ABILITY_NAME );
	}

	/**
	 * Checks theme-options access for menu metadata discovery.
	 *
	 * @return bool Whether the active identity may inspect menus.
	 */
	public function can_edit_theme_options(): bool {
		return $this->can( 'edit_theme_options', AuditReceipt::LIST_NAVIGATION_MENUS_ABILITY_NAME );
	}

	/**
	 * Checks standard taxonomy management access.
	 *
	 * @return bool Whether the active identity may manage categories.
	 */
	public function can_manage_categories(): bool {
		return $this->can( 'manage_categories', AuditReceipt::LIST_CATEGORIES_ABILITY_NAME );
	}

	/**
	 * Lists a bounded allow-list of public registered post-type metadata.
	 *
	 * @param array<string, mixed> $input Closed empty input.
	 * @return array<string, array<int, array<string, bool|string>>>|WP_Error Fixed discovery output or refusal.
	 */
	public function list_post_types( array $input ): array|WP_Error {
		if ( ! $this->is_empty_input( $input ) ) {
			return $this->deny( AuditReceipt::LIST_SUPPORTED_POST_TYPES_ABILITY_NAME, 'invalid_input', 'kiln_structure_invalid_input', __( 'Structure input is invalid.', 'kiln-mcp' ) );
		}

		$items = array();
		foreach ( array_slice( get_post_types( array( 'public' => true ), 'objects' ), 0, PagedStructureRequest::MAX_ITEMS, true ) as $type ) {
			$items[] = array(
				'name'         => $type->name,
				'label'        => $type->label,
				'hierarchical' => $type->hierarchical,
			);
		}

		return $this->allowed( AuditReceipt::LIST_SUPPORTED_POST_TYPES_ABILITY_NAME, 'manage_options', $items );
	}

	/**
	 * Lists a bounded allow-list of public registered taxonomy metadata.
	 *
	 * @param array<string, mixed> $input Closed empty input.
	 * @return array<string, array<int, array<string, bool|string>>>|WP_Error Fixed discovery output or refusal.
	 */
	public function list_taxonomies( array $input ): array|WP_Error {
		if ( ! $this->is_empty_input( $input ) ) {
			return $this->deny( AuditReceipt::LIST_SUPPORTED_TAXONOMIES_ABILITY_NAME, 'invalid_input', 'kiln_structure_invalid_input', __( 'Structure input is invalid.', 'kiln-mcp' ) );
		}

		$items = array();
		foreach ( array_slice( get_taxonomies( array( 'public' => true ), 'objects' ), 0, PagedStructureRequest::MAX_ITEMS, true ) as $taxonomy ) {
			$items[] = array(
				'name'         => $taxonomy->name,
				'label'        => $taxonomy->label,
				'hierarchical' => $taxonomy->hierarchical,
			);
		}

		return $this->allowed( AuditReceipt::LIST_SUPPORTED_TAXONOMIES_ABILITY_NAME, 'manage_options', $items );
	}

	/**
	 * Lists bounded navigation menu term metadata without items or URLs.
	 *
	 * @param array<string, mixed> $input Closed empty input.
	 * @return array<string, array<int, array<string, int|string>>>|WP_Error Fixed discovery output or refusal.
	 */
	public function list_menus( array $input ): array|WP_Error {
		if ( ! $this->is_empty_input( $input ) ) {
			return $this->deny( AuditReceipt::LIST_NAVIGATION_MENUS_ABILITY_NAME, 'invalid_input', 'kiln_structure_invalid_input', __( 'Structure input is invalid.', 'kiln-mcp' ) );
		}

		$items = array();
		foreach ( array_slice( wp_get_nav_menus(), 0, PagedStructureRequest::MAX_ITEMS ) as $menu ) {
			$items[] = array(
				'id'         => $menu->term_id,
				'name'       => $menu->name,
				'item_count' => $menu->count,
			);
		}

		return $this->allowed( AuditReceipt::LIST_NAVIGATION_MENUS_ABILITY_NAME, 'edit_theme_options', $items );
	}

	/**
	 * Lists one bounded category page.
	 *
	 * @param array<string, mixed> $input Closed page input.
	 * @return array<string, array<int, array<string, int|string>>>|WP_Error Fixed discovery output or refusal.
	 */
	public function list_categories( array $input ): array|WP_Error {
		return $this->list_terms( 'category', AuditReceipt::LIST_CATEGORIES_ABILITY_NAME, $input );
	}

	/**
	 * Lists one bounded tag page.
	 *
	 * @param array<string, mixed> $input Closed page input.
	 * @return array<string, array<int, array<string, int|string>>>|WP_Error Fixed discovery output or refusal.
	 */
	public function list_tags( array $input ): array|WP_Error {
		return $this->list_terms( 'post_tag', AuditReceipt::LIST_TAGS_ABILITY_NAME, $input );
	}

	/**
	 * Creates or returns exactly one standard category.
	 *
	 * @param array<string, mixed> $input Closed seed input.
	 * @return array<string, int|string>|WP_Error Fixed term metadata or refusal.
	 */
	public function create_category( array $input ): array|WP_Error {
		return $this->seed_term( 'category', AuditReceipt::CREATE_CATEGORY_ABILITY_NAME, $input );
	}

	/**
	 * Creates or returns exactly one standard tag.
	 *
	 * @param array<string, mixed> $input Closed seed input.
	 * @return array<string, int|string>|WP_Error Fixed term metadata or refusal.
	 */
	public function create_tag( array $input ): array|WP_Error {
		return $this->seed_term( 'post_tag', AuditReceipt::CREATE_TAG_ABILITY_NAME, $input );
	}

	/**
	 * Registers one fixed ability definition.
	 *
	 * @param string               $name        Ability name.
	 * @param string               $label       User-facing label.
	 * @param callable             $execute     Fixed executor.
	 * @param callable             $permission  Fixed permission callback.
	 * @param array<string, mixed> $input       Input schema.
	 * @param array<string, mixed> $output      Output schema.
	 * @return void
	 */
	private function register( string $name, string $label, callable $execute, callable $permission, array $input, array $output ): void {
		wp_register_ability(
			$name,
			array(
				'label'               => $label,
				'description'         => __( 'Bounded WordPress site-structure capability provided by Kiln MCP.', 'kiln-mcp' ),
				'category'            => self::CATEGORY,
				'input_schema'        => $input,
				'output_schema'       => $output,
				'execute_callback'    => $execute,
				'permission_callback' => $permission,
				'meta'                => array(
					'annotations'  => array(
						'readonly'   => false,
						'idempotent' => false,
					),
					'public'       => true,
					'show_in_rest' => false,
				),
			)
		);
	}

	/**
	 * Runs the appropriate standard capability check and records denial.
	 *
	 * @param string $capability WordPress capability.
	 * @param string $ability    Representative ability for the receipt.
	 * @return bool Whether the active identity is authorized.
	 */
	private function can( string $capability, string $ability ): bool {
		$allowed = current_user_can( $capability );
		if ( ! $allowed ) {
			$this->audit_store->append( AuditReceipt::create( $ability, 'denied', 'not_' . $capability, 0, gmdate( 'c' ) ) );
		}

		return $allowed;
	}

	/**
	 * Lists one standard taxonomy with closed pagination.
	 *
	 * @param string               $taxonomy Standard taxonomy.
	 * @param string               $ability  Ability identifier.
	 * @param array<string, mixed> $input    Closed page input.
	 * @return array<string, array<int, array<string, int|string>>>|WP_Error Fixed output or refusal.
	 */
	private function list_terms( string $taxonomy, string $ability, array $input ): array|WP_Error {
		try {
			$request = PagedStructureRequest::from_input( $input );
		} catch ( InvalidArgumentException ) {
			return $this->deny( $ability, 'invalid_input', 'kiln_structure_invalid_input', __( 'Structure input is invalid.', 'kiln-mcp' ) );
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => $request->limit,
				'offset'     => ( $request->page - 1 ) * $request->limit,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return $this->deny( $ability, 'term_query_failed', 'kiln_structure_unavailable', __( 'Structure metadata is unavailable.', 'kiln-mcp' ) );
		}

		$items = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term ) {
				$items[] = $this->term_record( $term );
			}
		}

		return $this->allowed( $ability, 'manage_categories', $items );
	}

	/**
	 * Seeds one standard taxonomy term without allowing update or deletion.
	 *
	 * @param string               $taxonomy Standard taxonomy.
	 * @param string               $ability  Ability identifier.
	 * @param array<string, mixed> $input    Closed seed input.
	 * @return array<string, int|string>|WP_Error Fixed term metadata or refusal.
	 */
	private function seed_term( string $taxonomy, string $ability, array $input ): array|WP_Error {
		try {
			$request = TermSeedRequest::from_input( $taxonomy, $input );
		} catch ( InvalidArgumentException ) {
			return $this->deny( $ability, 'invalid_input', 'kiln_structure_invalid_input', __( 'Term input is invalid.', 'kiln-mcp' ) );
		}

		if ( 'category' === $taxonomy && 0 < $request->parent_id ) {
			$parent = get_term( $request->parent_id, 'category' );
			if ( ! $parent instanceof WP_Term || 'category' !== $parent->taxonomy ) {
				return $this->deny( $ability, 'parent_not_admitted', 'kiln_structure_parent_invalid', __( 'The category parent is not available.', 'kiln-mcp' ) );
			}
		}

		$existing = term_exists( $request->name, $taxonomy, $request->parent_id );
		$term_id  = $this->term_id( $existing );
		if ( null !== $term_id ) {
			$term = get_term( $term_id, $taxonomy );
			if ( $term instanceof WP_Term ) {
				$this->audit_store->append( AuditReceipt::create( $ability, 'allowed', 'manage_categories', 1, gmdate( 'c' ) ) );
				return $this->term_record( $term );
			}
		}

		$inserted = wp_insert_term( $request->name, $taxonomy, array( 'parent' => $request->parent_id ) );
		if ( is_wp_error( $inserted ) || ! is_array( $inserted ) || ! isset( $inserted['term_id'] ) || ! is_int( $inserted['term_id'] ) ) {
			return $this->deny( $ability, 'term_insert_failed', 'kiln_structure_seed_failed', __( 'The term could not be created.', 'kiln-mcp' ) );
		}

		$term = get_term( $inserted['term_id'], $taxonomy );
		if ( ! $term instanceof WP_Term ) {
			return $this->deny( $ability, 'term_recheck_failed', 'kiln_structure_seed_failed', __( 'The term could not be created.', 'kiln-mcp' ) );
		}

		$this->audit_store->append( AuditReceipt::create( $ability, 'allowed', 'manage_categories', 1, gmdate( 'c' ) ) );

		return $this->term_record( $term );
	}

	/**
	 * Normalizes a WordPress term-exists value to one positive integer.
	 *
	 * @param array<string, int|string>|int|string|null $value WordPress existing-term result.
	 * @return int|null Exact term ID, if supplied.
	 */
	private function term_id( array|int|string|null $value ): ?int {
		if ( is_array( $value ) && isset( $value['term_id'] ) && is_int( $value['term_id'] ) && 0 < $value['term_id'] ) {
			return $value['term_id'];
		}
		if ( is_int( $value ) && 0 < $value ) {
			return $value;
		}
		if ( is_string( $value ) && ctype_digit( $value ) && 0 < (int) $value ) {
			return (int) $value;
		}

		return null;
	}

	/**
	 * Returns a fixed category/tag record without descriptions or slugs.
	 *
	 * @param WP_Term $term Standard taxonomy term.
	 * @return array<string, int|string> Fixed term record.
	 */
	private function term_record( WP_Term $term ): array {
		return array(
			'id'        => $term->term_id,
			'name'      => $term->name,
			'taxonomy'  => $term->taxonomy,
			'parent_id' => $term->parent,
			'count'     => $term->count,
		);
	}

	/**
	 * Wraps a fixed discovery result and records an allowed receipt.
	 *
	 * @param string                                     $ability     Ability identifier.
	 * @param string                                     $capability  Redacted capability class.
	 * @param array<int, array<string, bool|int|string>> $items    Fixed records.
	 * @return array<string, array<int, array<string, bool|int|string>>> Fixed list result.
	 */
	private function allowed( string $ability, string $capability, array $items ): array {
		$this->audit_store->append( AuditReceipt::create( $ability, 'allowed', $capability, count( $items ), gmdate( 'c' ) ) );

		return array( 'items' => $items );
	}

	/**
	 * Appends a redacted denial receipt and returns a stable error.
	 *
	 * @param string $ability          Ability identifier.
	 * @param string $capability_class Redacted reason class.
	 * @param string $error_code       Stable public code.
	 * @param string $message          Fixed public message.
	 * @return WP_Error Denial error.
	 */
	private function deny( string $ability, string $capability_class, string $error_code, string $message ): WP_Error {
		$this->audit_store->append( AuditReceipt::create( $ability, 'denied', $capability_class, 0, gmdate( 'c' ) ) );

		return new WP_Error( $error_code, $message );
	}

	/**
	 * Checks an exact empty object input.
	 *
	 * @param array<string, mixed> $input Ability input.
	 * @return bool Whether it is empty.
	 */
	private function is_empty_input( array $input ): bool {
		return array() === $input;
	}

	/**
	 * Returns an empty closed object schema.
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
	 * Returns bounded page input schema.
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
					'maximum' => PagedStructureRequest::MAX_ITEMS,
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
	 * Returns category seed input schema.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function category_seed_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'name'      => self::name_schema(),
				'parent_id' => array(
					'type'    => 'integer',
					'minimum' => 0,
				),
			),
			'required'             => array( 'name', 'parent_id' ),
		);
	}

	/**
	 * Returns tag seed input schema.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function tag_seed_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'name' => self::name_schema(),
			),
			'required'             => array( 'name' ),
		);
	}

	/**
	 * Returns bounded plain-text name schema.
	 *
	 * @return array<string, int|string> JSON Schema.
	 */
	private static function name_schema(): array {
		return array(
			'type'      => 'string',
			'minLength' => 1,
			'maxLength' => TermSeedRequest::MAX_NAME_CHARACTERS,
		);
	}

	/**
	 * Returns post-type list output schema.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function post_type_output_schema(): array {
		return self::list_schema(
			array(
				'name'         => array( 'type' => 'string' ),
				'label'        => array( 'type' => 'string' ),
				'hierarchical' => array( 'type' => 'boolean' ),
			),
			array( 'name', 'label', 'hierarchical' )
		);
	}

	/**
	 * Returns taxonomy list output schema.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function taxonomy_output_schema(): array {
		return self::post_type_output_schema();
	}

	/**
	 * Returns navigation-menu list output schema.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function menu_output_schema(): array {
		return self::list_schema(
			array(
				'id'         => array( 'type' => 'integer' ),
				'name'       => array( 'type' => 'string' ),
				'item_count' => array( 'type' => 'integer' ),
			),
			array( 'id', 'name', 'item_count' )
		);
	}

	/**
	 * Returns category/tag list output schema.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function term_output_schema(): array {
		return self::list_schema( self::term_properties(), array( 'id', 'name', 'taxonomy', 'parent_id', 'count' ) );
	}

	/**
	 * Returns one category/tag output schema.
	 *
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function term_record_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => self::term_properties(),
			'required'             => array( 'id', 'name', 'taxonomy', 'parent_id', 'count' ),
		);
	}

	/**
	 * Returns the fixed term property map.
	 *
	 * @return array<string, mixed> JSON Schema properties.
	 */
	private static function term_properties(): array {
		return array(
			'id'        => array( 'type' => 'integer' ),
			'name'      => array( 'type' => 'string' ),
			'taxonomy'  => array( 'type' => 'string' ),
			'parent_id' => array( 'type' => 'integer' ),
			'count'     => array( 'type' => 'integer' ),
		);
	}

	/**
	 * Wraps fixed record properties in a bounded list schema.
	 *
	 * @param array<string, mixed> $properties Record properties.
	 * @param array<int, string>   $required   Required keys.
	 * @return array<string, mixed> JSON Schema.
	 */
	private static function list_schema( array $properties, array $required ): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'items' => array(
					'type'     => 'array',
					'maxItems' => PagedStructureRequest::MAX_ITEMS,
					'items'    => array(
						'type'                 => 'object',
						'additionalProperties' => false,
						'properties'           => $properties,
						'required'             => $required,
					),
				),
			),
			'required'             => array( 'items' ),
		);
	}
}
