<?php
/**
 * Local administrator issuer for exact theme effect approvals.
 *
 * @package KilnMcp
 */

declare(strict_types=1);

namespace KilnMcp\Infrastructure\WordPress;

use KilnMcp\Capability\ThemeManagement\ThemeAction;

/**
 * Keeps M13 approval issuance outside the MCP transport.
 */
final class ThemeApprovalPage {
	private const PAGE_SLUG    = 'kiln-mcp-theme-approvals';
	private const NONCE_ACTION = 'kiln_mcp_issue_theme_approval';

	/**
	 * Creates the local theme approval issuer.
	 *
	 * @param ThemeApprovalStore $approval_store Local hash-only approval store.
	 */
	public function __construct(
		private readonly ThemeApprovalStore $approval_store,
	) {
	}

	/**
	 * Registers a local administrator-only Tools page.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_management_page(
			__( 'Kiln theme approvals', 'kiln-mcp' ),
			__( 'Kiln theme approvals', 'kiln-mcp' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Renders the one-time local approval form.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to issue Kiln theme approvals.', 'kiln-mcp' ) );
		}

		$notice = $this->handle_issue_request();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Kiln theme approval', 'kiln-mcp' ); ?></h1>
			<p><?php esc_html_e( 'Issue a one-time, ten-minute approval for one exact theme effect. Copy the token now; it is never stored or shown again.', 'kiln-mcp' ); ?></p>
			<?php if ( null !== $notice ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?>">
					<p><?php echo esc_html( $notice['message'] ); ?></p>
				</div>
			<?php endif; ?>
			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="kiln-mcp-theme-stylesheet"><?php esc_html_e( 'Theme stylesheet', 'kiln-mcp' ); ?></label></th>
						<td><input id="kiln-mcp-theme-stylesheet" name="kiln_mcp_theme_stylesheet" required type="text" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Issue one-time activation approval', 'kiln-mcp' ), 'primary', 'kiln_mcp_issue_theme_approval' ); ?>
			</form>
			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<p><?php esc_html_e( 'Rollback approval restores only the immediate prior theme recorded by Kiln after a successful activation.', 'kiln-mcp' ); ?></p>
				<?php submit_button( __( 'Issue one-time rollback approval', 'kiln-mcp' ), 'secondary', 'kiln_mcp_issue_theme_rollback' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Issues a token after local nonce, authority, and installed-theme checks.
	 *
	 * @return array{type: string, message: string}|null Renderable page notice.
	 */
	private function handle_issue_request(): ?array {
		if ( isset( $_POST['kiln_mcp_issue_theme_rollback'] ) ) {
			return $this->issue_rollback_approval();
		}

		if ( ! isset( $_POST['kiln_mcp_issue_theme_approval'] ) ) {
			return null;
		}

		check_admin_referer( self::NONCE_ACTION );

		$raw_stylesheet = filter_input( INPUT_POST, 'kiln_mcp_theme_stylesheet', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$stylesheet     = is_string( $raw_stylesheet ) ? sanitize_key( $raw_stylesheet ) : '';
		$theme          = wp_get_theme( $stylesheet );

		if ( '' === $stylesheet || ! $theme->exists() ) {
			return array(
				'type'    => 'error',
				'message' => __( 'That installed theme is not available for approval.', 'kiln-mcp' ),
			);
		}

		$token = $this->approval_store->issue(
			ThemeAction::ACTIVATE,
			$stylesheet,
			hash( 'sha256', (string) $theme->get( 'Version' ) ),
			wp_get_theme()->get_stylesheet()
		);

		return array(
			'type'    => 'success',
			'message' => sprintf(
				/* translators: %s: one-time theme approval token. */
				__( 'One-time approval token (copy now): %s', 'kiln-mcp' ),
				$token
			),
		);
	}

	/**
	 * Issues a rollback approval only for the exact immediate prior-theme record.
	 *
	 * @return array{type: string, message: string} Renderable page notice.
	 */
	private function issue_rollback_approval(): array {
		check_admin_referer( self::NONCE_ACTION );

		$record = get_option( ThemeActivationRegistrar::ROLLBACK_OPTION, array() );
		if ( ! is_array( $record )
			|| array_keys( $record ) !== array( 'previous', 'active' )
			|| ! is_string( $record['previous'] )
			|| ! is_string( $record['active'] )
			|| $record['previous'] === $record['active']
			|| ! hash_equals( wp_get_theme()->get_stylesheet(), $record['active'] ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'No current Kiln rollback is available for approval.', 'kiln-mcp' ),
			);
		}

		$theme = wp_get_theme( $record['previous'] );
		if ( ! $theme->exists() ) {
			return array(
				'type'    => 'error',
				'message' => __( 'The recorded prior theme is no longer available.', 'kiln-mcp' ),
			);
		}

		$token = $this->approval_store->issue(
			ThemeAction::ROLLBACK,
			$record['previous'],
			hash( 'sha256', (string) $theme->get( 'Version' ) ),
			$record['active']
		);

		return array(
			'type'    => 'success',
			'message' => sprintf(
				/* translators: %s: one-time theme approval token. */
				__( 'One-time approval token (copy now): %s', 'kiln-mcp' ),
				$token
			),
		);
	}
}
