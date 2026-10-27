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
	 * @param ThemeArtifactStore $artifact_store Local scanned artifact store.
	 */
	public function __construct(
		private readonly ThemeApprovalStore $approval_store,
		private readonly ThemeArtifactStore $artifact_store,
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
						<th scope="row"><label for="kiln-mcp-wordpress-org-slug"><?php esc_html_e( 'WordPress.org theme slug', 'kiln-mcp' ); ?></label></th>
						<td><input id="kiln-mcp-wordpress-org-slug" name="kiln_mcp_wordpress_org_slug" required type="text" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Issue one-time WordPress.org installation approval', 'kiln-mcp' ), 'secondary', 'kiln_mcp_issue_wordpress_org_install_approval' ); ?>
			</form>
			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="kiln-mcp-theme-update-stylesheet"><?php esc_html_e( 'Installed theme stylesheet', 'kiln-mcp' ); ?></label></th>
						<td><input id="kiln-mcp-theme-update-stylesheet" name="kiln_mcp_theme_update_stylesheet" required type="text" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Issue one-time theme update approval', 'kiln-mcp' ), 'secondary', 'kiln_mcp_issue_theme_update_approval' ); ?>
			</form>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<table class="form-table"
					<tr>
						<th scope="row"><label for="kiln-mcp-theme-archive"><?php esc_html_e( 'Theme ZIP', 'kiln-mcp' ); ?></label></th>
						<td><input accept=".zip,application/zip" id="kiln-mcp-theme-archive" name="kiln_mcp_theme_archive" type="file" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Scan and retain ZIP', 'kiln-mcp' ), 'secondary', 'kiln_mcp_retain_theme_archive' ); ?>
			</form>
			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="kiln-mcp-install-stylesheet"><?php esc_html_e( 'Expected stylesheet', 'kiln-mcp' ); ?></label></th>
						<td><input id="kiln-mcp-install-stylesheet" name="kiln_mcp_install_stylesheet" required type="text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="kiln-mcp-artifact-id"><?php esc_html_e( 'Artifact ID', 'kiln-mcp' ); ?></label></th>
						<td><input id="kiln-mcp-artifact-id" name="kiln_mcp_artifact_id" required type="text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="kiln-mcp-artifact-digest"><?php esc_html_e( 'Artifact digest', 'kiln-mcp' ); ?></label></th>
						<td><input id="kiln-mcp-artifact-digest" name="kiln_mcp_artifact_digest" required type="text" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Issue one-time ZIP installation approval', 'kiln-mcp' ), 'secondary', 'kiln_mcp_issue_theme_install_approval' ); ?>
			</form>
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
		if ( isset( $_POST['kiln_mcp_issue_theme_update_approval'] ) ) {
			return $this->issue_theme_update_approval();
		}

		if ( isset( $_POST['kiln_mcp_issue_wordpress_org_install_approval'] ) ) {
			return $this->issue_wordpress_org_install_approval();
		}

		if ( isset( $_POST['kiln_mcp_issue_theme_install_approval'] ) ) {
			return $this->issue_uploaded_install_approval();
		}

		if ( isset( $_POST['kiln_mcp_retain_theme_archive'] ) ) {
			return $this->retain_uploaded_archive();
		}

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
	 * Issues an exact installed-theme update approval for its current release.
	 *
	 * @return array{type: string, message: string} Renderable page notice.
	 */
	private function issue_theme_update_approval(): array {
		check_admin_referer( self::NONCE_ACTION );

		$raw_stylesheet = filter_input( INPUT_POST, 'kiln_mcp_theme_update_stylesheet', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$stylesheet     = is_string( $raw_stylesheet ) ? sanitize_key( $raw_stylesheet ) : '';
		$theme          = '' === $stylesheet ? null : wp_get_theme( $stylesheet );
		$update         = null === $theme || ! $theme->exists() ? null : ThemeUpdateRegistrar::available_update( $stylesheet );
		if ( null === $update ) {
			return array(
				'type'    => 'error',
				'message' => __( 'That installed theme has no approved update available.', 'kiln-mcp' ),
			);
		}

		$token = $this->approval_store->issue( ThemeAction::UPDATE, $stylesheet, $update['identity'], wp_get_theme()->get_stylesheet() );

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
	 * Issues an exact WordPress.org installation approval for the current release.
	 *
	 * @return array{type: string, message: string} Renderable page notice.
	 */
	private function issue_wordpress_org_install_approval(): array {
		check_admin_referer( self::NONCE_ACTION );

		$raw_slug = filter_input( INPUT_POST, 'kiln_mcp_wordpress_org_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$slug     = is_string( $raw_slug ) ? sanitize_key( $raw_slug ) : '';
		$theme    = '' === $slug ? null : ThemeWordPressOrgRegistrar::wordpress_org_theme( $slug );
		if ( null === $theme ) {
			return array(
				'type'    => 'error',
				'message' => __( 'That WordPress.org theme is not available for approval.', 'kiln-mcp' ),
			);
		}

		$token = $this->approval_store->issue(
			ThemeAction::INSTALL_WORDPRESS_ORG,
			$slug,
			$theme['identity'],
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
	 * Issues an exact installation approval only for a currently retained artifact.
	 *
	 * @return array{type: string, message: string} Renderable page notice.
	 */
	private function issue_uploaded_install_approval(): array {
		check_admin_referer( self::NONCE_ACTION );

		$raw_target  = filter_input( INPUT_POST, 'kiln_mcp_install_stylesheet', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$raw_id      = filter_input( INPUT_POST, 'kiln_mcp_artifact_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$raw_digest  = filter_input( INPUT_POST, 'kiln_mcp_artifact_digest', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$target      = is_string( $raw_target ) ? sanitize_key( $raw_target ) : '';
		$artifact_id = is_string( $raw_id ) ? strtolower( $raw_id ) : '';
		$digest      = is_string( $raw_digest ) ? strtolower( $raw_digest ) : '';

		if ( '' === $target
			|| 1 !== preg_match( '/\\A[a-f0-9]{32}\\z/D', $artifact_id )
			|| 1 !== preg_match( '/\\A[a-f0-9]{64}\\z/D', $digest )
			|| null === $this->artifact_store->path_for( $artifact_id, $digest ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'That scanned theme ZIP is not available for approval.', 'kiln-mcp' ),
			);
		}

		$token = $this->approval_store->issue(
			ThemeAction::INSTALL_UPLOADED_ZIP,
			$target,
			$digest,
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
	 * Accepts one local administrator ZIP and retains it only after fail-closed scanning.
	 *
	 * @return array{type: string, message: string} Renderable page notice.
	 */
	private function retain_uploaded_archive(): array {
		check_admin_referer( self::NONCE_ACTION );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_handle_upload requires WordPress' original upload metadata; each admitted scalar is normalized below.
		$uploaded_file = isset( $_FILES['kiln_mcp_theme_archive'] ) ? wp_unslash( $_FILES['kiln_mcp_theme_archive'] ) : null;
		$name          = is_array( $uploaded_file ) && isset( $uploaded_file['name'] ) && is_string( $uploaded_file['name'] ) ? sanitize_file_name( $uploaded_file['name'] ) : '';
		$tmp_name      = is_array( $uploaded_file ) && isset( $uploaded_file['tmp_name'] ) && is_string( $uploaded_file['tmp_name'] ) ? sanitize_text_field( $uploaded_file['tmp_name'] ) : '';
		$size          = is_array( $uploaded_file ) && isset( $uploaded_file['size'] ) ? absint( $uploaded_file['size'] ) : 0;
		$error         = is_array( $uploaded_file ) && isset( $uploaded_file['error'] ) ? absint( $uploaded_file['error'] ) : 1;
		$file          = array(
			'name'     => $name,
			'type'     => 'application/zip',
			'tmp_name' => $tmp_name,
			'error'    => $error,
			'size'     => $size,
		);
		if ( '' === $name
			|| '' === $tmp_name
			|| 0 !== $error
			|| 0 >= $size
			|| 10485760 < $size
			|| 'zip' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'The local theme ZIP could not be accepted.', 'kiln-mcp' ),
			);
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			$wordpress_root = defined( 'ABSPATH' ) ? constant( 'ABSPATH' ) : null;
			if ( ! is_string( $wordpress_root ) || '' === $wordpress_root ) {
				return array(
					'type'    => 'error',
					'message' => __( 'The local theme ZIP could not be accepted.', 'kiln-mcp' ),
				);
			}

			require_once $wordpress_root . 'wp-admin/includes/file.php';
		}

		$uploaded = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'mimes'     => array( 'zip' => 'application/zip' ),
			)
		);
		if ( ! isset( $uploaded['file'] ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'The local theme ZIP could not be accepted.', 'kiln-mcp' ),
			);
		}

		$artifact = $this->artifact_store->retain( $uploaded['file'] );
		if ( null === $artifact ) {
			wp_delete_file( $uploaded['file'] );
			return array(
				'type'    => 'error',
				'message' => __( 'The local theme ZIP was refused by Kiln safety checks.', 'kiln-mcp' ),
			);
		}

		return array(
			'type'    => 'success',
			'message' => sprintf(
				/* translators: 1: opaque artifact ID, 2: artifact SHA-256 digest. */
				__( 'Scanned ZIP retained. Artifact ID: %1$s Digest: %2$s', 'kiln-mcp' ),
				$artifact['id'],
				$artifact['digest']
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
