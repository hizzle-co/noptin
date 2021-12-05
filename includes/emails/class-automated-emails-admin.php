<?php
/**
 * Emails API: Automated Emails Admin.
 *
 * Contains the main admin class for Noptin automated emails
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin automated emails.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Automated_Emails_Admin {

	/**
	 * Add hooks
	 *
	 */
	public function add_hooks() {

		add_action( 'noptin_email_campaigns_tab_automations_main', array( $this, 'render_main_admin_page' ) );
		add_action( 'noptin_email_campaigns_tab_automations_edit_campaign', array( $this, 'render_edit_form' ) );
		add_action( 'noptin_email_campaigns_tab_automations_new_campaign', array( $this, 'render_new_campaign_form' ) );
		add_action( 'add_meta_boxes_noptin_automations', array( $this, 'register_metaboxes' ), 1, 3 );
		add_action( 'noptin_save_edited_automation', array( $this, 'maybe_save_automation' ) );

		// Backwards compat.
		add_action( 'noptin_email_campaigns_tab_automations_view_campaigns', array( $this, 'render_main_admin_page' ) );
	}

	/**
	 * Render the main newsletters admin page.
	 *
	 * @param array An array of supported tabs.
	 */
	public function render_main_admin_page( $tabs ) {
		include plugin_dir_path( __FILE__ ) . 'class-automated-emails-table.php';

		$triggers = $this->get_automation_triggers();
		$table    = new Noptin_Email_Automations_Table();
		$table->prepare_items();

		include plugin_dir_path( __FILE__ ) . 'views/automations/view-automations.php';

	}

	/**
	 * Displays the edit campaign form.
	 * 
	 * @param array An array of supported tabs.
	 */
	public function render_edit_form( $tabs ) {

		if ( empty( $_GET['id'] ) ) {
			include plugin_dir_path( __FILE__ ) . 'views/404.php';
			return;
		}

		$campaign_id = trim( $_GET['id'] );

		// Prepare the campaign being edited.
		$campaign_id = absint( $campaign_id );
		$campaign    = get_post( $campaign_id );

		if ( is_noptin_campaign( $campaign, 'automation' ) ) {

			$automation_type   = esc_html( stripslashes_deep( get_post_meta( $campaign_id, 'automation_type', true ) ) );
			$automations_types = $this->get_automation_triggers();
			do_action( 'add_meta_boxes_noptin_automations', $campaign, $automation_type, $automations_types );
			do_action( "add_meta_boxes_noptin_automations_$automation_type", $campaign, $automations_types );
			include plugin_dir_path( __FILE__ ) . 'views/automations/edit-automation.php';

		} else {
			include plugin_dir_path( __FILE__ ) . 'views/404.php';
		}

	}

	/**
	 * Displays the new campaign form.
	 *
	 * @param array An array of supported tabs.
	 */
	public function render_new_campaign_form( $tabs ) {
		// TODO:
	}

	/**
	 * Registers newsletter metaboxes.
	 *
	 */
	public function register_metaboxes( $campaign, $automation_type, $automations ) {

		add_meta_box(
			'noptin_automation_body',
			__('Email Content','newsletter-optin-box'),
			array( $this, 'render_metabox' ),
			'noptin_page_noptin-automation',
			'normal',
			'default',
			'body'
		);

		add_meta_box(
			'noptin_automation_save',
			__('Save','newsletter-optin-box'),
			array( $this, 'render_metabox' ),
			'noptin_page_noptin-automation',
			'side',
			'high',
			'save'
		);

		if ( 'post_notifications' == $automation_type && ! empty( $automations[ $automation_type ]['setup_cb'] ) ) {

			add_meta_box(
				'noptin_automation_setup_cb',
				__('Options','newsletter-optin-box'),
				array( $this, 'render_metabox' ),
				'noptin_page_noptin-automation',
				'advanced',
				'default',
				$automations[ $automation_type ]['setup_cb']
			);

		}

		add_meta_box(
			'noptin_automation_preview_text',
			__('Preview Text (Optional)','newsletter-optin-box'),
			array( $this, 'render_metabox' ),
			'noptin_page_noptin-automation',
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
		include plugin_dir_path( __FILE__ ) . "views/automations/{$metabox['args']}.php";
	}

	/**
	 *  Returns a list of all automations
	 */
	public function get_automation_triggers() {

		$triggers = array(
			'post_notifications' => array(
				'setup_title'    => __( 'new post notification', 'newsletter-optin-box' ),
				'title'          => __( 'Post Notifications', 'newsletter-optin-box' ),
				'description'    => __( 'Notify your subscribers everytime you publish new content.', 'newsletter-optin-box' ),
				'support_delay'  => __( 'After new content is published', 'newsletter-optin-box' ),
				'support_filter' => true,
			),
			'welcome_email'      => array(
				'setup_title'    => __( 'welcome email', 'newsletter-optin-box' ),
				'title'          => __( 'Welcome Email', 'newsletter-optin-box' ),
				'description'    => __( 'Introduce yourself to new subscribers or set up a series of welcome emails to act as an email course.', 'newsletter-optin-box' ),
				'support_delay'  => __( 'After someone subscribes', 'newsletter-optin-box' ),
				'support_filter' => __( 'All new subscribers', 'newsletter-optin-box' ),
			),
			'subscriber_tag'     => array(
				'setup_title'    => __( 'subscriber tag automation', 'newsletter-optin-box' ),
				'title'          => __( 'Subscriber Tag', 'newsletter-optin-box' ),
				'description'    => __( 'Send an email to a subscriber when you tag them.', 'newsletter-optin-box' ),
				'support_delay'  => __( 'After a subscriber is tagged', 'newsletter-optin-box' ),
			),
			'subscriber_list'    => array(
				'setup_title'    => __( 'subscriber list automation', 'newsletter-optin-box' ),
				'title'          => __( 'Subscriber List', 'newsletter-optin-box' ),
				'description'    => __( 'Send an email to a subscriber when they join a given email list.', 'newsletter-optin-box' ),
				'support_delay'  => __( 'After a subscriber is added to a list', 'newsletter-optin-box' ),
			),
		);

		return apply_filters( 'noptin_email_automation_triggers', $triggers, $this );

	}

	/**
	 * Saves an automated email
	 */
	public function maybe_save_automation() {

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
		$data = wp_unslash( $_POST );
		$id   = (int) $data['id'];

		unset( $data['noptin-edit-newsletter-nonce'] );
		unset( $data['noptin_admin_action'] );
		unset( $data['id'] );
		unset( $data['_wp_http_referer'] );
		unset( $data['meta-box-order-nonce'] );
		unset( $data['closedpostboxesnonce'] );
		unset( $data['save'] );

		// Prepare post status.
		$status = $data['status'];
		unset( $data['status'] );

		// Prepare post args.
		$post = array(
			'ID'           => $id,
			'post_status'  => $status,
			'post_type'    => 'noptin-campaign',
			'post_content' => $data['email_body'],
		);

		unset( $data['email_body'] );
		$post['meta_input'] = $data;

		foreach ( noptin_get_newsletter_meta() as $meta_key ) {
			$post['meta_input'][ $meta_key ] = empty( $data[ $meta_key ] ) ? '' : noptin_clean( $data[ $meta_key ] );
		}

		$post = apply_filters( 'noptin_save_automation_campaign_details', $post, $data );

		$post = wp_update_post( $post, true );

		if ( is_wp_error( $post ) ) {
			noptin()->admin->show_error( $post->get_error_message() );
		} else {
			noptin()->admin->show_success( __( 'Your changes were saved successfully', 'newsletter-optin-box' ) );
		}

	}

}
