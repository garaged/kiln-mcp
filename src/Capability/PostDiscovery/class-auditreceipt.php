<?php
/**
 * Redacted local audit receipt contract.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Capability\PostDiscovery;

/**
 * Creates a receipt without caller, request, or content data.
 */
final class AuditReceipt {
	public const LIST_POSTS_ABILITY_NAME                 = 'kiln/list-posts';
	public const READ_POST_ABILITY_NAME                  = 'kiln/read-post';
	public const CREATE_POST_DRAFT_ABILITY_NAME          = 'kiln/create-post-draft';
	public const UPDATE_POST_DRAFT_ABILITY_NAME          = 'kiln/update-post-draft';
	public const PUBLISH_POST_ABILITY_NAME               = 'kiln/publish-post';
	public const SCHEDULE_POST_PUBLICATION_ABILITY_NAME  = 'kiln/schedule-post-publication';
	public const TRASH_POST_ABILITY_NAME                 = 'kiln/trash-post';
	public const RESTORE_POST_ABILITY_NAME               = 'kiln/restore-post';
	public const LIST_SUPPORTED_POST_TYPES_ABILITY_NAME  = 'kiln/list-supported-post-types';
	public const LIST_SUPPORTED_TAXONOMIES_ABILITY_NAME  = 'kiln/list-supported-taxonomies';
	public const LIST_NAVIGATION_MENUS_ABILITY_NAME      = 'kiln/list-navigation-menus';
	public const LIST_CATEGORIES_ABILITY_NAME            = 'kiln/list-categories';
	public const LIST_TAGS_ABILITY_NAME                  = 'kiln/list-tags';
	public const CREATE_CATEGORY_ABILITY_NAME            = 'kiln/create-category';
	public const CREATE_TAG_ABILITY_NAME                 = 'kiln/create-tag';
	public const GET_ACTIVE_THEME_CONTEXT_ABILITY_NAME   = 'kiln/get-active-theme-context';
	public const LIST_BLOCK_TEMPLATES_ABILITY_NAME       = 'kiln/list-block-templates';
	public const LIST_BLOCK_TEMPLATE_PARTS_ABILITY_NAME  = 'kiln/list-block-template-parts';
	public const LIST_ACTIVE_THEME_PATTERNS_ABILITY_NAME = 'kiln/list-active-theme-patterns';

	/**
	 * Creates one fixed-shape redacted audit receipt.
	 *
	 * @param string $ability          Kiln ability identifier.
	 * @param string $outcome          Allowed or denied outcome.
	 * @param string $capability_class Capability class used for the decision.
	 * @param int    $result_count     Number of records returned.
	 * @param string $timestamp        ISO 8601 receipt timestamp.
	 * @return array<string, int|string>
	 */
	public static function create( string $ability, string $outcome, string $capability_class, int $result_count, string $timestamp ): array {
		return array(
			'schema_version'          => '1.0',
			'timestamp'               => $timestamp,
			'ability'                 => $ability,
			'outcome'                 => $outcome,
			'caller_capability_class' => $capability_class,
			'result_count'            => $result_count,
		);
	}
}
