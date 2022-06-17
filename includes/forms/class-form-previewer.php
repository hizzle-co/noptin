<?php
/**
 * Forms API: Forms Previewer.
 *
 * Contains main class for previewing Noptin forms
 *
 * @since             1.6.2
 * @package           Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Forms previewing class.
 *
 * @since 1.6.2
 * @internal
 * @ignore
 */
class Noptin_Form_Previewer {

	public function add_hooks() {
		add_action( 'parse_request', array( $this, 'listen' ) );
	}

	public function listen() {
		if ( empty( $_GET['noptin_preview_form'] ) || ! current_user_can( get_noptin_capability() ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( 'new' !== $_GET['noptin_preview_form'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$form = noptin_get_optin_form( absint( $_GET['noptin_preview_form'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! $form->exists() ) {
				return;
			}
		}

		define( 'IS_NOPTIN_PREVIEW', 1 );
		show_admin_bar( false );
		add_filter( 'pre_handle_404', '__return_true' );
		remove_all_actions( 'template_redirect' );
		add_action( 'template_redirect', array( $this, 'load_preview' ), 1 );
	}

	public function load_preview() {
		// clear output, some plugin or hooked code might have thrown errors by now.
		if ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		$form_id = 'new' === $_GET['noptin_preview_form'] ? array() : (int) $_GET['noptin_preview_form']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		status_header( 200 );

		require plugin_dir_path( __FILE__ ) . 'views/preview.php';
		exit;
	}

}
