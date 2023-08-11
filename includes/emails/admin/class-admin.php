<?php
/**
 * Emails API: Emails Admin.
 *
 * Contains the main admin class for Noptin emails
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin emails.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Emails_Admin {

	/** @var Noptin_Newsletter_Emails_Admin */
	public $newsletters_admin;

	/** @var Noptin_Automated_Emails_Admin */
	public $automations_admin;

	/**
	 * Class constructor.
	 *
	 */
	public function __construct() {

		// Load files.
		include plugin_dir_path( __FILE__ ) . 'class-newsletter-emails-admin.php';
		include plugin_dir_path( __FILE__ ) . 'class-automated-emails-admin.php';

		// Init props.
		$this->newsletters_admin = new Noptin_Newsletter_Emails_Admin();
		$this->automations_admin = new Noptin_Automated_Emails_Admin();
	}

	/**
	 * Add hooks
	 *
	 */
	public function add_hooks() {

		add_action( 'noptin_repair_stuck_campaign', array( $this, 'repair_stuck_campaign' ) );
		add_action( 'noptin_force_send_campaign', array( $this, 'force_send_campaign' ) );
		add_action( 'noptin_duplicate_email_campaign', array( $this, 'duplicate_email_campaign' ) );
		add_action( 'noptin_delete_email_campaign', array( $this, 'delete_email_campaign' ) );
		add_filter( 'pre_get_users', array( $this, 'filter_users_by_campaign' ) );
		add_action( 'wp_ajax_noptin_send_test_email', array( $this, 'send_test_email' ) );
		add_action( 'add_meta_boxes_noptin_automations', array( $this, 'register_metaboxes' ) );
		add_action( 'add_meta_boxes_noptin_newsletters', array( $this, 'register_metaboxes' ) );

		$this->newsletters_admin->add_hooks();
		$this->automations_admin->add_hooks();
	}

	/**
	 * Repairs a stuck campaign.
	 *
	 * @since 1.11.5
	 */
	public function repair_stuck_campaign() {

		// Only admins should be able to force send campaigns.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['noptin_nonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks.
		if ( ! wp_verify_nonce( $_GET['noptin_nonce'], 'noptin_repair_stuck_campaign' ) ) {
			return;
		}

	}

	/**
	 * Manually sends a campaign.
	 *
	 * @since 1.11.2
	 */
	public function force_send_campaign() {

		// Only admins should be able to force send campaigns.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['noptin_nonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks.
		if ( ! wp_verify_nonce( $_GET['noptin_nonce'], 'noptin_force_send_campaign' ) ) {
			return;
		}

		define( 'NOPTIN_RESENDING_CAMPAIGN', true );

		// Retrieve campaign object.
		if ( empty( $_GET['section'] ) || 'newsletters' === $_GET['section'] ) {
			$campaign = new Noptin_Newsletter_Email( intval( $_GET['campaign'] ) );

			// Abort if already sent.
			if ( $campaign->is_published() ) {
				return;
			}

			// Send.
			$campaign->status = 'publish';
			$campaign->save();
		} else {
			$campaign = new Noptin_Automated_Email( intval( $_GET['campaign'] ) );

			// Abort if cannot be sent.
			if ( ! $campaign->is_published() || 'post_digest' !== $campaign->type ) {
				return;
			}

			// Send.
			do_action( 'noptin_send_post_digest', $campaign->id );
		}

		// Check if the campaign exists.
		noptin()->admin->show_info( __( 'Your email has been added to the sending queue and will be sent soon.', 'newsletter-optin-box' ) );

		// Redirect.
		wp_safe_redirect( remove_query_arg( array( 'noptin_admin_action', 'noptin_nonce', 'campaign', 'sub_section' ) ) );
		exit;
	}

	/**
	 * Duplicates an email campaign.
	 *
	 * @since 1.7.0
	 */
	public function duplicate_email_campaign() {

		// Only admins should be able to duplicate campaigns.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['noptin_nonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks.
		if ( ! wp_verify_nonce( $_GET['noptin_nonce'], 'noptin_duplicate_campaign' ) ) {
			return;
		}

		// Retrieve campaign object.
		$campaign = noptin_get_email_campaign_object( intval( $_GET['campaign'] ) );

		// Check if the campaign exists.
		if ( ! empty( $campaign ) && $campaign->exists() ) {
			$campaign->id     = null;
			$campaign->status = 'draft';
			$result           = $campaign->save();

			if ( $campaign->exists() ) {
				noptin()->admin->show_info( __( 'The campaign has been duplicated.', 'newsletter-optin-box' ) );
				wp_safe_redirect( $campaign->get_edit_url() );
				exit;
			} elseif ( is_wp_error( $result ) ) {
				noptin()->admin->show_error( $result->get_error_message() );
			} else {
				noptin()->admin->show_error( __( 'Unable to duplicate the campaign.', 'newsletter-optin-box' ) );
			}
		} else {
			noptin()->admin->show_error( __( 'Campaign not found.', 'newsletter-optin-box' ) );
		}

		// Redirect.
		wp_safe_redirect( remove_query_arg( array( 'noptin_admin_action', 'noptin_nonce', 'campaign' ) ) );
		exit;

	}

	/**
	 * Deletes an email campaign.
	 *
	 * @since 1.7.0
	 */
	public function delete_email_campaign() {

		// Only admins should be able to delete campaigns.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['noptin_nonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks.
		if ( ! wp_verify_nonce( $_GET['noptin_nonce'], 'noptin_delete_campaign' ) ) {
			return;
		}

		// Retrieve campaign object.
		$campaign = noptin_get_email_campaign_object( intval( $_GET['campaign'] ) );

		if ( empty( $campaign ) || ! $campaign->exists() ) {
			return;
		}

		do_action( 'noptin_' . $campaign->type . '_campaign_before_delete', $campaign );

		// Delete the campaign.
		wp_delete_post( $campaign->id, true );

		// Show success info.
		noptin()->admin->show_info( __( 'The campaign has been deleted.', 'newsletter-optin-box' ) );

		// Redirect to success page.
		wp_safe_redirect( remove_query_arg( array( 'noptin_admin_action', 'noptin_nonce', 'campaign' ) ) );
		exit;

	}

	/**
	 * Renders the admin page
	 *
	 * @return void
	 */
	public function render_admin_page() {

		// Only admins can access this page.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		// Prepare vars.
		$tabs    = $this->get_admin_page_tabs();
		$tab     = $this->get_current_tab();
		$section = $this->get_current_section();

		require_once plugin_dir_path( __FILE__ ) . 'views/main-page.php';
	}

	/**
	 * Returns a list of admin page tabs.
	 *
	 * @return array
	 */
	public function get_admin_page_tabs() {

		return apply_filters(
			'noptin_email_campaign_tabs',
			array(
				'newsletters' => __( 'Newsletters', 'newsletter-optin-box' ),
				'automations' => __( 'Automated Emails', 'newsletter-optin-box' ),
			)
		);

	}

	/**
	 * Returns the current admin page tab.
	 *
	 * @return array
	 */
	public function get_current_tab() {

		// Prepare vars.
		$tabs = $this->get_admin_page_tabs();
		$tab  = ! empty( $_GET['section'] ) ? sanitize_key( $_GET['section'] ) : 'newsletters';  // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Ensure the tab is supported.
		if ( ! isset( $tabs[ $tab ] ) ) {
			return 'newsletters';
		}

		return $tab;
	}

	/**
	 * Returns the current admin page section.
	 *
	 * @return array
	 */
	public function get_current_section() {
		return ! empty( $_GET['sub_section'] ) ? sanitize_key( $_GET['sub_section'] ) : 'main';  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Returns the current admin page title.
	 *
	 * @return array
	 */
	public function get_current_admin_page_title() {

		$page_title = esc_html__( 'Email Campaigns', 'newsletter-optin-box' );
		$section    = $this->get_current_section();

		if ( 'new_campaign' === $section ) {
			$page_title = esc_html__( 'New Campaign', 'newsletter-optin-box' );
		}

		if ( 'edit_campaign' === $section ) {
			$page_title = esc_html__( 'Edit Campaign', 'newsletter-optin-box' );
		}

		switch ( $this->get_current_tab() ) {

			case 'newsletters':
				switch ( $section ) {

					case 'main':
						$page_title = esc_html__( 'Newsletters', 'newsletter-optin-box' );
						break;

				}
				break;

			case 'automations':
				switch ( $section ) {

					case 'main':
						$page_title = esc_html__( 'Automated Emails', 'newsletter-optin-box' );
						break;

				}
				break;
		}

		return apply_filters( 'noptin_emails_admin_page_title', $page_title );
	}

	/**
	 * Filters the users query.
	 *
	 * @param WP_User_Query $query
	 */
	public function filter_users_by_campaign( $query ) {
		global $pagenow;

		if ( is_admin() && 'users.php' === $pagenow && isset( $_GET['noptin_meta_key'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$meta_query   = $query->get( 'meta_query' );
			$meta_query   = empty( $meta_query ) ? array() : $meta_query;
			$meta_query[] = array(
				'key'   => sanitize_text_field( $_GET['noptin_meta_key'] ),  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'value' => (int) $_GET['noptin_meta_value'],  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
			$query->set( 'meta_query', $meta_query );

		}

	}

	/**
	 * Sends a test email
	 *
	 * @access      public
	 * @since       1.1.2
	 * @return      void
	 */
	public function send_test_email() {

		// Verify nonce.
		check_ajax_referer( 'noptin-admin-nonce', 'noptin-admin-nonce' );

		// Check capability.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 403 );
		}

		define( 'NOPTIN_SENDING_TEST_EMAIL', true );

		// Prepare data.
		$data = wp_unslash( $_POST );

		// Check if we have a recipient for the test email.
		if ( empty( $data['email'] ) || ! is_email( $data['email'] ) ) {
			wp_send_json_error( __( 'Please provide a valid email address', 'newsletter-optin-box' ) );
		}

		$GLOBALS['current_noptin_email'] = $data['email'];

		// Handle automated emails?
		if ( ! empty( $data['noptin_is_automation'] ) ) {
			noptin()->emails->automated_email_types->send_test_email( $data['noptin_email'], sanitize_email( $data['email'] ) );
		}

		// Handle newsletter email.
		$email = new Noptin_Newsletter_Email( $data['noptin_email'] );

		// Ensure we have a subject.
		$subject = $email->get_subject();
		if ( empty( $subject ) ) {
			wp_send_json_error( __( 'You need to provide a subject for your email.', 'newsletter-optin-box' ) );
		}

		// Ensure we have content.
		$content = $email->get_content( $email->get_email_type() );
		if ( empty( $content ) ) {
			wp_send_json_error( __( 'The email body cannot be empty.', 'newsletter-optin-box' ) );
		}

		// Try sending the test email.
		try {
			$result = noptin()->emails->newsletter->send_test( $email, sanitize_email( $data['email'] ) );
		} catch ( Exception $e ) {
			$result = new WP_Error( 'exception', $e->getMessage() );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Successfuly sent the email.
		if ( $result ) {
			wp_send_json_success( __( 'Your test email has been sent', 'newsletter-optin-box' ) );
		}

		// Failed sending the email.
		wp_send_json_error( __( 'Could not send the test email', 'newsletter-optin-box' ) );

	}

	/**
	 * Registers newsletter | email metaboxes.
	 *
	 * @param Noptin_Automated_Email|Noptin_Newsletter_Email $campaign
	 */
	public function register_metaboxes( $campaign ) {

		$screen_id = get_current_screen() ? get_current_screen()->id : 'noptin_page_noptin-automation';

		// Email recipients.
		add_meta_box(
			'noptin_email_recipients',
			__( 'Recipients', 'newsletter-optin-box' ),
			array( $this, 'render_metabox' ),
			$screen_id,
			'side',
			'high',
			'recipients'
		);

		// Email attachment.
		add_meta_box(
			'noptin_email_attachment',
			__( 'Email Attachments', 'newsletter-optin-box' ),
			array( $this, 'render_metabox' ),
			$screen_id,
			'side',
			'default',
			'attachments'
		);

		// Email content.
		add_meta_box(
			'noptin_email_content',
			__( 'Email Content', 'newsletter-optin-box' ),
			array( $this, 'render_metabox' ),
			$screen_id,
			'normal',
			'default',
			'content'
		);

		// Template Details.
		add_meta_box(
			'noptin_email_details',
			__( 'Template', 'newsletter-optin-box' ),
			array( $this, 'render_metabox' ),
			$screen_id,
			'normal',
			'low',
			'details'
		);

	}

	/**
	 * Displays a metabox.
	 *
	 */
	public function render_metabox( $campaign, $metabox ) {
		include plugin_dir_path( __FILE__ ) . "views/metabox-{$metabox['args']}.php";
	}

}
