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

		add_action( 'noptin_email_campaigns_tab_newsletters_main', array( $this, 'render_main_admin_page' ) );
		add_action( 'noptin_email_campaigns_tab_newsletters_edit_campaign', array( $this, 'render_edit_form' ) );
		add_action( 'noptin_email_campaigns_tab_newsletters_new_campaign', array( $this, 'render_new_campaign_form' ) );
		add_action( 'add_meta_boxes_noptin_newsletters', array( $this, 'register_metaboxes' ) );
		add_action( 'noptin_edit_newsletter', array( $this, 'maybe_save_campaign' ) );

		// Backwards compat.
		add_action( 'noptin_email_campaigns_tab_newsletters_view_campaigns', array( $this, 'render_main_admin_page' ) );
	}

	/**
	 * Render the main newsletters admin page.
	 *
	 * @param array An array of supported tabs.
	 */
	public function render_main_admin_page( $tabs ) {
		include plugin_dir_path( __FILE__ ) . 'class-newsletters-table.php';
		include plugin_dir_path( __FILE__ ) . 'views/newsletters/view-newsletters.php';

	}

	/**
	 * Displays the edit campaign form.
	 * 
	 * @param array An array of supported tabs.
	 */
	public function render_edit_form( $tabs ) {

		$id       = empty( $_GET['id'] ) ? 0 : $_GET['id'];
		$campaign = false;

		if ( $id ) {
			$campaign = get_post( $id );
		}

		if ( ! empty( $campaign ) ) {

			do_action( 'add_meta_boxes_noptin_newsletters', $campaign );
			include plugin_dir_path( __FILE__ ) . 'views/edit-newsletter.php';

		} else {
			get_noptin_template( 'newsletters/404.php', array() );
		}

	}

	/**
	 * Displays the new campaign form.
	 *
	 * @param array An array of supported tabs.
	 */
	public function render_new_campaign_form( $tabs ) {

		$id       = 0;
		$campaign = false;

		do_action( 'add_meta_boxes_noptin_newsletters', $campaign );
		include plugin_dir_path( __FILE__ ) . 'views/add-newsletter.php';

	}

	/**
	 * Registers newsletter metaboxes.
	 *
	 */
	public function register_metaboxes() {

		add_meta_box(
			'noptin_newsletter_body',
			__('Email Content','newsletter-optin-box'),
			array( $this, 'render_metabox' ),
			'noptin_page_noptin-newsletter',
			'normal',
			'default',
			'body'
		);

		add_meta_box(
			'noptin_newsletter_send',
			__('Send','newsletter-optin-box'),
			array( $this, 'render_metabox' ),
			'noptin_page_noptin-newsletter',
			'side',
			'high',
			'send'
		);

		add_meta_box(
			'noptin_newsletter_preview_text',
			__('Preview Text (Optional)','newsletter-optin-box'),
			array( $this, 'render_metabox' ),
			'noptin_page_noptin-newsletter',
			'side',
			'low',
			'preview-text'
		);

	}

	/**
	 * Displays a metabox.
	 *
	 */
	public function render_metabox( $campaign, $metabox ) {
		include plugin_dir_path( __FILE__ ) . "views/{$metabox['args']}.php";
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

		// Prepare data.
		$data  = wp_kses_post_deep( wp_unslash( $_POST ) );

		// For new campaigns, default the post status to draft.
		$id     = false;
		$status = 'draft';

		// For existing campaigns, default to the saved status.
		if ( ! empty( $data['campaign_id'] ) ) {
			$id     = (int) $data['campaign_id'];
			$status = ( 'draft' === get_post_status( $id ) ) ? 'draft' : 'publish';
		}

		if ( ! empty( $data['draft'] ) ) {
			$status = 'draft';
		}

		if ( ! empty( $data['publish'] ) ) {
			$status = 'publish';
		}

		// Prepare post args.
		$post = array(
			'post_status'   => $status,
			'post_type'     => 'noptin-campaign',
			'post_date'     => current_time( 'mysql' ),
			'post_date_gmt' => current_time( 'mysql', true ),
			'edit_date'     => true,
			'post_title'    => trim( $data['email_subject'] ),
			'post_content'  => $data['email_body'],
			'meta_input'    => array(
				'email_sender'            => empty( $data['email_sender'] ) ? 'noptin' : sanitize_key( $data['email_sender'] ),
				'campaign_type'           => 'newsletter',
				'preview_text'            => empty( $data['preview_text'] ) ? '' : esc_html( $data['preview_text'] ),
			),
		);

		foreach ( noptin_get_newsletter_meta() as $meta_key ) {
			$post['meta_input'][ $meta_key ] = empty( $data[ $meta_key ] ) ? '' : noptin_clean( $data[ $meta_key ] );
		}

		// Are we scheduling the campaign?
		if ( 'publish' === $status && ! empty( $data['schedule-date'] ) ) {

			$datetime = date_create( $data['schedule-date'], wp_timezone() );

			if ( false !== $datetime ) {

				$post['post_status']   = 'future';
				$post['post_date']     = $datetime->format( 'Y-m-d H:i:s' );
				$post['post_date_gmt'] = get_gmt_from_date( $datetime->format( 'Y-m-d H:i:s' ) );

			}

		}

		$post = apply_filters( 'noptin_save_newsletter_campaign_details', $post, $data );

		if ( empty( $id ) ) {
			$post = wp_insert_post( $post, true );
		} else {
			$post['ID'] = $id;
			$post       = wp_update_post( $post, true );
		}

		if ( is_wp_error( $post ) ) {
			return noptin()->admin->show_error( $post->get_error_message() );
		}

		$post = get_post( $post );

		if ( 'draft' === $post->post_status ) {
			noptin()->admin->show_success( __( 'Your email has been saved.', 'newsletter-optin-box' ) );
			wp_safe_redirect( get_noptin_newsletter_campaign_url( $post->ID ) );
			exit;
		}

		if ( 'future' === $post->post_status ) {
			noptin()->admin->show_success(
				sprintf(
					__( 'Your email has been scheduled to send on: %s', 'newsletter-optin-box' ),
					"<strong>{$post->post_date}</strong>"
				)
			);
		} else {
			noptin()->admin->show_success( __( 'Your email has been added to the sending qeue and will be sent soon.', 'newsletter-optin-box' ) );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=noptin-email-campaigns' ) );
		exit;

	}

}
