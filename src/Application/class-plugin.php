<?php
/**
 * WordPress composition root.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Application;

use KilnMcp\Capability\SiteHealthSummary\SiteHealthSummary;
use KilnMcp\Domain\Policy\ToolPolicy;
use KilnMcp\Infrastructure\WordPress\AdapterDependency;
use KilnMcp\Infrastructure\WordPress\AuditReceiptStore;
use KilnMcp\Infrastructure\WordPress\ContentWorkflowRegistrar;
use KilnMcp\Infrastructure\WordPress\DraftAuthoringRegistrar;
use KilnMcp\Infrastructure\WordPress\ExtensionRegistrar;
use KilnMcp\Infrastructure\WordPress\KilnMcpServerRegistrar;
use KilnMcp\Infrastructure\WordPress\PostDiscoveryRegistrar;
use KilnMcp\Infrastructure\WordPress\PostReadRegistrar;
use KilnMcp\Infrastructure\WordPress\SiteHealthSummaryRegistrar;
use KilnMcp\Infrastructure\WordPress\SiteStructureRegistrar;
use KilnMcp\Infrastructure\WordPress\WorkflowApprovalPage;
use KilnMcp\Infrastructure\WordPress\WorkflowApprovalStore;

/**
 * Composes bounded capabilities after all required WordPress plugins load.
 */
final class Plugin {
	/**
	 * Creates the composition root for a specific Kiln version.
	 *
	 * @param string $version Kiln plugin version.
	 */
	public function __construct(
		private readonly string $version,
	) {
	}

	/**
	 * Registers the composition root with WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'bootstrap' ) );
	}

	/**
	 * Creates policy and conditionally wires the supported adapter integration.
	 *
	 * Failing the adapter check does not register any Kiln capability.
	 *
	 * @return void
	 */
	public function bootstrap(): void {
		ToolPolicy::deny_by_default();

		$adapter_version = defined( 'WP_MCP_VERSION' ) && is_string( WP_MCP_VERSION ) ? WP_MCP_VERSION : null;

		if ( ! AdapterDependency::is_available(
			class_exists( 'WP\\MCP\\Core\\McpAdapter' ),
			function_exists( 'wp_register_ability' ),
			$adapter_version,
		) ) {
			return;
		}

		$health_registrar          = new SiteHealthSummaryRegistrar( new SiteHealthSummary( $this->version ) );
		$audit_store               = new AuditReceiptStore();
		$approval_store            = new WorkflowApprovalStore();
		$post_registrar            = new PostDiscoveryRegistrar( $audit_store );
		$post_read_registrar       = new PostReadRegistrar( $audit_store );
		$draft_authoring_registrar = new DraftAuthoringRegistrar( $audit_store );
		$workflow_registrar        = new ContentWorkflowRegistrar( $audit_store, $approval_store );
		$approval_page             = new WorkflowApprovalPage( $approval_store );
		$structure_registrar       = new SiteStructureRegistrar( $audit_store );
		$extension_registrar       = new ExtensionRegistrar( $audit_store );
		$server_registrar          = new KilnMcpServerRegistrar();

		add_action( 'wp_abilities_api_categories_init', array( $health_registrar, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $health_registrar, 'register_ability' ) );
		add_action( 'wp_abilities_api_categories_init', array( $post_registrar, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $post_registrar, 'register_ability' ) );
		add_action( 'wp_abilities_api_init', array( $post_read_registrar, 'register_ability' ) );
		add_action( 'wp_abilities_api_categories_init', array( $draft_authoring_registrar, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $draft_authoring_registrar, 'register_abilities' ) );
		add_action( 'wp_abilities_api_categories_init', array( $workflow_registrar, 'register_category' ) );
		add_action( 'wp_abilities_api_categories_init', array( $structure_registrar, 'register_category' ) );
		add_action( 'wp_abilities_api_categories_init', array( $extension_registrar, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $workflow_registrar, 'register_abilities' ) );
		add_action( 'wp_abilities_api_init', array( $structure_registrar, 'register_abilities' ) );
		add_action( 'wp_abilities_api_init', array( $extension_registrar, 'register_abilities' ) );
		add_action( 'post_updated', array( $draft_authoring_registrar, 'record_post_update' ), 10, 3 );
		add_action( 'mcp_adapter_init', array( $server_registrar, 'register_server' ), 100 );
		add_action( 'admin_menu', array( $approval_page, 'register_page' ) );
	}
}
