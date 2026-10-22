<?php
/**
 * Local administrator approval issuer.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use KilnMcp\Capability\ContentWorkflow\WorkflowAction;
use WP_Post;

/**
 * Renders the only interface that may issue an M5 approval token.
 */
final class WorkflowApprovalPage {
	private const PAGE_SLUG    = 'kiln-mcp-approvals';
	private const NONCE_ACTION = 'kiln_mcp_issue_workflow_approval';

	/**
	 * Creates the administrator-only approval issuer.
	 *
	 * @param WorkflowApprovalStore $approval_store Hash-only local approval store.
	 */
	public function __construct(
		private readonly WorkflowApprovalStore $approval_store,
	) {
	}

	/**
	 * Registers the local Tools-page entry.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_management_page(
			__( 'Kiln MCP approvals', 'kiln-mcp' ),
			__( 'Kiln MCP approvals', 'kiln-mcp' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Renders a form and, after a nonce-protected local POST, a one-time token.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to issue Kiln workflow approvals.', 'kiln-mcp' ) );
		}

		$notice = $this->handle_issue_request();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Kiln MCP workflow approval', 'kiln-mcp' ); ?></h1>
			<p><?php esc_html_e( 'Issue a one-time, ten-minute approval for one exact action and current post version. Copy the token now; it is never stored or shown again.', 'kiln-mcp' ); ?></p>
			<?php if ( null !== $notice ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?>">
					<p><?php echo esc_html( $notice['message'] ); ?></p>
				</div>
			<?php endif; ?>
			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="kiln-mcp-action"><?php esc_html_e( 'Action', 'kiln-mcp' ); ?></label></th>
						<td>
							<select id="kiln-mcp-action" name="kiln_mcp_action">
								<option value="<?php echo esc_attr( WorkflowAction::PUBLISH ); ?>"><?php esc_html_e( 'Publish draft', 'kiln-mcp' ); ?></option>
								<option value="<?php echo esc_attr( WorkflowAction::SCHEDULE ); ?>"><?php esc_html_e( 'Schedule draft', 'kiln-mcp' ); ?></option>
								<option value="<?php echo esc_attr( WorkflowAction::TRASH ); ?>"><?php esc_html_e( 'Trash draft or published post', 'kiln-mcp' ); ?></option>
								<option value="<?php echo esc_attr( WorkflowAction::RESTORE ); ?>"><?php esc_html_e( 'Restore trashed post', 'kiln-mcp' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="kiln-mcp-post-id"><?php esc_html_e( 'Post ID', 'kiln-mcp' ); ?></label></th>
						<td><input id="kiln-mcp-post-id" min="1" name="kiln_mcp_post_id" required type="number" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Issue one-time approval', 'kiln-mcp' ), 'primary', 'kiln_mcp_issue_approval' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Processes an administrator's local approval request.
	 *
	 * @return array{type: string, message: string}|null One renderable status message.
	 */
	private function handle_issue_request(): ?array {
		if ( ! isset( $_POST['kiln_mcp_issue_approval'] ) ) {
			return null;
		}

		check_admin_referer( self::NONCE_ACTION );

		$raw_action = isset( $_POST['kiln_mcp_action'] ) ? wp_unslash( $_POST['kiln_mcp_action'] ) : '';
		$action     = is_string( $raw_action ) ? sanitize_key( $raw_action ) : '';
		$post_id = isset( $_POST['kiln_mcp_post_id'] ) ? absint( wp_unslash( $_POST['kiln_mcp_post_id'] ) ) : 0;
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post || ! WorkflowAction::is_supported( $action ) || ! $this->is_admitted( $post, $action ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'That action is not available for the requested post.', 'kiln-mcp' ),
			);
		}

		$token = $this->approval_store->issue( $action, $post->ID, $this->token_for( $post ) );

		return array(
			'type'    => 'success',
			'message' => sprintf(
				/* translators: %s: one-time workflow approval token. */
				__( 'One-time approval token (copy now): %s', 'kiln-mcp' ),
				$token
			),
		);
	}

	/**
	 * Applies M5's closed source-state admission policy to a candidate post.
	 *
	 * @param WP_Post|null $post   Candidate target.
	 * @param string       $action Exact lifecycle action.
	 * @return bool Whether this action may be approved for the post.
	 */
	private function is_admitted( ?WP_Post $post, string $action ): bool {
		if ( ! $post instanceof WP_Post || 'post' !== $post->post_type || '' !== $post->post_password ) {
			return false;
		}

		if ( WorkflowAction::PUBLISH === $action || WorkflowAction::SCHEDULE === $action ) {
			return 'draft' === $post->post_status;
		}

		if ( WorkflowAction::TRASH === $action ) {
			return in_array( $post->post_status, array( 'draft', 'publish' ), true );
		}

		return WorkflowAction::RESTORE === $action && 'trash' === $post->post_status;
	}

	/**
	 * Builds the current opaque version token for a post.
	 *
	 * @param WP_Post $post Target post.
	 * @return string Current modification token.
	 */
	private function token_for( WP_Post $post ): string {
		return PostVersion::token(
			$post->ID,
			$post->post_modified_gmt,
			(string) get_post_meta( $post->ID, PostVersion::META_KEY, true )
		);
	}
}
