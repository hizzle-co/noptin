<?php
/**
 * Emails API: Newsletter Emails Admin.
 *
 * Contains the main admin class for Noptin newsletter emails
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin newsletter emails.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Newsletter_Emails_Admin {

	/**
	 * Add hooks
	 *
	 */
	public function add_hooks() {

		add_action( 'noptin_email_campaigns_tab_newsletters_edit_campaign', array( $this, 'render_edit_form' ) );
		add_action( 'add_meta_boxes_noptin_newsletters', array( $this, 'register_metaboxes' ) );
		add_action( 'noptin_save_edited_newsletter', array( $this, 'maybe_save_campaign' ) );

	}

	/**
	 * Displays the edit campaign form.
	 *
	 * @param array An array of supported tabs.
	 */
	public function render_edit_form( $tabs ) {

		// Prepare campaign id.
		$id = empty( $_GET['campaign'] ) ? 0 : $_GET['campaign'];

		// Check if we're sending a new email.
		if ( is_numeric( $id ) ) {
			$campaign = new Noptin_Newsletter_Email( intval( $id ) );
		} else {
			$campaign = new Noptin_Newsletter_Email( 0 );
			$id       = 0;

			$campaign->options['email_sender'] = sanitize_key( $_GET['campaign'] );
		}

		if ( $campaign->exists() || empty( $id ) ) {

			do_action( 'add_meta_boxes_noptin_newsletters', $campaign );
			include plugin_dir_path( __FILE__ ) . 'views/newsletters/view-edit-newsletter.php';

		} else {
			include plugin_dir_path( __FILE__ ) . 'views/404.php';
		}

	}

	/**
	 * Registers newsletter metaboxes.
	 *
	 */
	public function register_metaboxes() {

		add_meta_box(
			'noptin_newsletter_send',
			__( 'Send', 'newsletter-optin-box' ) . '<a class="noptin-send-test-email" style="cursor: pointer;color: red;">' . __( 'Send a test email', 'newsletter-optin-box' ) . '</a>',
			array( $this, 'render_metabox' ),
			get_current_screen()->id,
			'side',
			'low',
			'send'
		);

	}

	/**
	 * Displays a metabox.
	 *
	 */
	public function render_metabox( $campaign, $metabox ) {
		include plugin_dir_path( __FILE__ ) . "views/newsletters/metabox-{$metabox['args']}.php";
	}

	/**
	 * Saves a newsletter campaign
	 */
	public function maybe_save_campaign() {

		// Ensure that this is not an ajax request.
		if ( wp_doing_ajax() ) {
			return;
		}

		// And that the current user can save a campaign.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_POST['noptin-edit-newsletter-nonce'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['noptin-edit-newsletter-nonce'], 'noptin-edit-newsletter' ) ) {
			return;
		}

		// Save newsletter.
		$newsletter = new Noptin_Newsletter_Email( wp_unslash( $_POST['noptin_email'] ) );

		// Check if a "Send" or "Save draft button was clicked". Otherwise maintain the initial status.
		if ( ! empty( $_POST['publish'] ) ) {
			$newsletter->status = 'publish';
		} else if ( ! empty( $_POST['draft'] ) ) {
			$newsletter->status = 'draft';
		}

		$result = $newsletter->save();

		if ( is_wp_error( $result ) ) {
			noptin()->admin->show_error( $result );
		} else if ( false === $result ) {
			noptin()->admin->show_error( __( 'Could not save your changes.', 'newsletter-optin-box' ) );
		} else {

			if ( 'draft' === $newsletter->status ) {
				noptin()->admin->show_success( __( 'Your changes were saved successfully', 'newsletter-optin-box' ) );
				wp_safe_redirect( get_noptin_newsletter_campaign_url( $newsletter->id ) );
				exit;
			}

			if ( 'future' === $newsletter->status ) {

				$post = get_post( $newsletter->id );
				noptin()->admin->show_success(
					sprintf(
						__( 'Your email has been scheduled to send on: %s', 'newsletter-optin-box' ),
						"<strong>" . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $post->post_date ) ) . "</strong>"
					)
				);

			} else {
				noptin()->admin->show_success( __( 'Your email has been added to the sending qeue and will be sent soon.', 'newsletter-optin-box' ) );
			}

			wp_safe_redirect( admin_url( 'admin.php?page=noptin-email-campaigns' ) );
			exit;
		}

	}

}
