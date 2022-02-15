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
	 * Inits the admin module.
	 *
	 */
	public function init() {

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
		$this->init();

		add_action( 'admin_init', array( $this, 'maybe_do_action' ) );
		add_action( 'noptin_after_register_menus', array( $this, 'register_menu' ), 5 );
		add_filter( 'pre_get_users', array( $this, 'filter_users_by_campaign' ) );
		add_action( 'wp_ajax_noptin_send_test_email', array( $this, 'send_test_email' ) );
		add_action( 'add_meta_boxes_noptin_automations', array( $this, 'register_metaboxes' ) );
		add_action( 'add_meta_boxes_noptin_newsletters', array( $this, 'register_metaboxes' ) );

		$this->newsletters_admin->add_hooks();
		$this->automations_admin->add_hooks();
	}

	/**
	 * Handles email related admin actions.
	 *
	 */
	public function maybe_do_action() {

		// Check capability.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		// Prepare vars.
		$admin = noptin()->admin;

		// Campaign actions.
		if ( isset( $_GET['page'] ) && 'noptin-email-campaigns' === $_GET['page'] ) {

			// Duplicate campaign.
			if ( ! empty( $_GET['duplicate_campaign'] ) && wp_verify_nonce( $_GET['noptin_nonce'], 'noptin_duplicate_campaign' ) ) {

				$campaign = get_post( $_GET['duplicate_campaign'] );

				if ( ! empty( $campaign ) ) {

					$post = array(
						'post_status'   => 'draft',
						'post_type'     => 'noptin-campaign',
						'post_date'     => current_time( 'mysql' ),
						'post_date_gmt' => current_time( 'mysql', true ),
						'edit_date'     => true,
						'post_title'    => trim( $campaign->post_title ),
						'post_content'  => $campaign->post_content,
						'meta_input'    => array(
							'campaign_type'           => 'newsletter',
							'preview_text'            => get_post_meta( $campaign->ID, 'preview_text', 'true' ),
							'email_sender'            => get_post_meta( $campaign->ID, 'email_sender', 'true' ),
						),
					);

					foreach ( noptin_get_newsletter_meta() as $meta_key ) {
						$post['meta_input'][ $meta_key ] = get_post_meta( $campaign->ID, $meta_key, 'true' );
					}
					$post['meta_input'] = array_filter( $post['meta_input'] );

					$new_campaign = wp_insert_post( $post, true );

					if ( is_wp_error( $new_campaign ) ) {
						$admin->show_error( $new_campaign->get_error_message() );
					} else {
						wp_redirect( get_noptin_newsletter_campaign_url( $new_campaign ) );
						exit;
					}

				}
				
			}

			// Delete multiple campaigns.
			if ( ! empty( $_GET['action'] ) && 'delete' === $_GET['action'] && wp_verify_nonce( $_GET['_wpnonce'], 'bulk-ids' ) ) {
				$ids = array();

				if ( isset( $_REQUEST['id'] ) && is_array( $_REQUEST['id'] ) ) {
					$ids = array_map( 'intval', $_REQUEST['id'] );
				}

				foreach ( $ids as $id ) {
					wp_delete_post( $id, true );
				}

				$admin->show_success( __( 'The selected campaigns have been deleted.', 'newsletter-optin-box' ) );
			}
		}

	}

	/**
	 * Register admin page
	 *
	 * @return void
	 */
	public function register_menu() {

		add_submenu_page(
			'noptin',
			esc_html__( 'Email Campaigns', 'newsletter-optin-box' ),
			esc_html__( 'Email Campaigns', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-email-campaigns',
			array( $this, 'render_admin_page' )
		);

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
	 * Renders the admin page
	 *
	 * @return void
	 */
	public function render_admin_page() {

		// Only admins can access this page.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		// Runs before displaying the email campaigns page.
		do_action( 'noptin_before_email_campaigns_page' );

		// Prepare vars.
		$tabs        = $this->get_admin_page_tabs();
		$tab         = ! empty( $_GET['section'] ) ? sanitize_key( $_GET['section'] ) : 'newsletters';
		$sub_section = ! empty( $_GET['sub_section'] ) ? sanitize_key( $_GET['sub_section'] ) : 'main';

		// Ensure the tab is supported.
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'newsletters';
		}

		// Runs when displaying a specific tab's content.
		do_action( "noptin_before_email_campaigns_tab_$tab", $tabs );

		// Runs when displaying a specific tab's sub-section content.
		do_action( "noptin_email_campaigns_tab_{$tab}_{$sub_section}", $tabs );

		// Runs after displaying a specific tab's content.
		do_action( "noptin_email_campaigns_tab_$tab", $tabs );

		// Runs after displaying the email campaigns page.
		do_action( 'noptin_after_email_campaigns_page' );
	}

	/**
	 * Filters the users query.
	 *
	 * @param WP_User_Query $query
	 */
	public function filter_users_by_campaign( $query ) {
		global $pagenow;

		if ( is_admin() && 'users.php' == $pagenow && isset( $_GET['noptin_meta_key'] ) ) {

			$meta_query   = $query->get( 'meta_query' );
			$meta_query   = empty( $meta_query ) ? array() : $meta_query;
			$meta_query[] = array(
				'key'   => sanitize_text_field( $_GET['noptin_meta_key'] ),
				'value' => (int) $_GET['noptin_meta_value']
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

		// Prepare data.
		$data = wp_unslash( $_POST );

		// Check if we have a recipient for the test email.
		if ( empty( $data['email'] ) || ! is_email( $data['email'] ) ) {
			wp_send_json_error( __( 'Please provide a valid email address', 'newsletter-optin-box' ) );
		}

		// Handle automated emails?
		if ( ! empty( $data['noptin_automation'] ) ) {
			noptin()->emails->automated_email_types->send_test_email( $data['noptin_automation'], sanitize_email( $data['email'] ) );
		}

		// TODO: // Handles normal newsletter emails.
		$data = $_POST;
 
		unset( $data['_wpnonce'] );
		unset( $data['_wp_http_referer'] );
		unset( $data['action'] );

		// Remove slashes.
		$data = stripslashes_deep( $data );

		$data['email'] = sanitize_email( $data['email'] );

		// Is this an automated email?
		if ( ! empty( $data['noptin_automation'] ) ) {
			noptin()->emails->automated_email_types->send_test_email( $data['noptin_automation'], $data['email'] );
			exit;
		}

		// Subject, body and preview text.
		if ( empty( $data['email_subject'] ) && empty( $data['subject'] ) ) {
			wp_send_json_error( __( 'You need to provide a subject for your email.', 'newsletter-optin-box' ) );
			exit;
		}

		if ( empty( $data['email_subject'] ) ) {
			$data['email_subject'] = $data['subject'];
		}

		$data['email_subject'] = '[TEST] ' . $data['email_subject'];

		if ( empty( $data['email_body'] ) ) {
			wp_send_json_error( __( 'The email body cannot be empty.', 'newsletter-optin-box' ) );
			exit;
		}

		/**
		 * Filters the newsletter test email data.
		 * 
		 * @param array $data The test email data.
		 */
		$data = apply_filters( 'noptin_test_email_data', $data );

		if ( noptin()->mailer->prepare_then_send( $data ) ) {
			wp_send_json_success( __( 'Your test email has been sent', 'newsletter-optin-box' ) );
		}

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
			__( 'Recipients','newsletter-optin-box' ),
			array( $this, 'render_metabox' ),
			$screen_id,
			'side',
			'high',
			'recipients'
		);

		// Email Details.
		add_meta_box(
			'noptin_email_details',
			__( 'Details','newsletter-optin-box' ),
			array( $this, 'render_metabox' ),
			$screen_id,
			'normal',
			'default',
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
