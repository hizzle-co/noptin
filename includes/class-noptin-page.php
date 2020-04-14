<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

	/**
	 * Prints the noptin page
	 *
	 * @since       1.0.6
	 */

class Noptin_Page {

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Register shortcode.
		add_shortcode( 'noptin_action_page', array( $this, 'do_shortcode' ) );

		// User unsubscribe.
		add_action( 'noptin_page_unsubscribe', array( $this, 'unsubscribe_user' ) );
		add_action( 'noptin_pre_page_unsubscribe', array( $this, 'pre_unsubscribe_user' ) );

		// Email confirmation.
		add_action( 'noptin_page_confirm', array( $this, 'confirm_subscription' ) );
		add_action( 'noptin_pre_page_confirm', array( $this, 'pre_confirm_subscription' ) );

		// Email open.
		add_filter( 'noptin_actions_page_template', array( $this, 'email_open' ) );

		// Email click.
		add_filter( 'noptin_actions_page_template', array( $this, 'email_click' ) );

		// Preview email.
		add_action( 'noptin_page_preview_email', array( $this, 'preview_email' ) );

		// Filter template.
		add_filter( 'page_template', array( $this, 'filter_page_template' ) );

		// Admin bar.
		add_filter( 'show_admin_bar', array( $this, 'maybe_hide_admin_bar' ) );

		// Exclude from sitemap.
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( $this, 'hide_from_yoast_sitemap' ) );

		// Pages settings.
		add_filter( 'noptin_get_settings', array( $this, 'add_options' ), 100 );

	}

	/**
	 * Converts shortcode to html
	 *
	 * @access      public
	 * @since       1.0.6
	 * @return      array
	 */
	public function do_shortcode( $atts ) {

		// Abort early if no action is specified.
		$action = $this->get_request_action();
		if ( empty( $action ) ) {
			return '';
		}

		// Retrieve the optional value.
		$value = $this->get_request_value();

		ob_start();

		do_action( "noptin_page_$action", $value );

		return ob_get_clean();

	}

	/**
	 * Retrieves the request action
	 *
	 * @access      public
	 * @since       1.2.2
	 * @return      string
	 */
	public function get_request_action() {

		// Abort early if no action is specified.
		if ( empty( $_REQUEST['noptin_action'] ) && empty( $_REQUEST['na'] ) ) {
			return '';
		}

		// Prepare the action to execute...
		$action = empty( $_REQUEST['noptin_action'] ) ? trim( $_REQUEST['na'] ) : trim( $_REQUEST['noptin_action'] );
		return sanitize_title_with_dashes( urldecode( $action ) );

	}

	/**
	 * Retrieves the request value
	 *
	 * @access      public
	 * @since       1.2.2
	 * @return      string
	 */
	public function get_request_value() {

		$value = '';

		if ( isset( $_REQUEST['noptin_value'] ) ) {
			$value = sanitize_title_with_dashes( urldecode( $_REQUEST['noptin_value'] ) );
		}

		if ( isset( $_REQUEST['nv'] ) ) {
			$value = sanitize_title_with_dashes( urldecode( $_REQUEST['nv'] ) );
		}

		return $value;

	}


	/**
	 * Logs email opens
	 *
	 * @access      public
	 * @since       1.2.0
	 * @return      array
	 */
	public function email_open( $filter ) {

		if ( 'email_open' != $this->get_request_action() ) {
			return $filter;
		}

		if ( isset( $_GET['sid'] ) && isset( $_GET['cid'] ) ) {
			$subscriber_id = intval( $_GET['sid'] );
			$campaign_id   = intval( $_GET['cid'] );
			log_noptin_subscriber_campaign_open( $subscriber_id, $campaign_id );
		}

		// Display 1x1 pixel transparent gif.
		nocache_headers();
		header( 'Content-type: image/gif' );
		header( 'Content-Length: 42' );
		echo base64_decode( 'R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEA' );
		exit;

	}

	/**
	 * Logs email clicks
	 *
	 * @access      public
	 * @since       1.2.0
	 * @return      array
	 */
	public function email_click( $filter ) {

		if ( 'email_click' != $this->get_request_action() ) {
			return $filter;
		}

		$to = get_home_url();

		if ( isset( $_GET['to'] ) ) {
			$to = urldecode( $_GET['to'] );
		}

		if ( isset( $_GET['sid'] ) && isset( $_GET['cid'] ) ) {
			$subscriber_id = intval( $_GET['sid'] );
			$campaign_id   = intval( $_GET['cid'] );
			log_noptin_subscriber_campaign_click( $subscriber_id, $campaign_id, $to );
		}

		wp_redirect( $to );
		exit;

	}

	/**
	 * Notifies the user that they have successfuly unsubscribed.
	 *
	 * @access      public
	 * @since       1.0.6
	 * @return      array
	 */
	public function unsubscribe_user( $key ) {

		// Ensure a user key is specified.
		if ( empty( $key ) ) {
			$this->print_paragraph( __( 'Unable to subscribe you at this time.', 'newsletter-optin-box' ) );
			return;
		}

		$this->print_paragraph( __( 'You have successfully been unsubscribed from this mailing list.', 'newsletter-optin-box' ) );

	}

	/**
	 * Unsubscribes a user
	 *
	 * @access      public
	 * @since       1.2.7
	 * @return      array
	 */
	public function pre_unsubscribe_user( $page ) {
		global $wpdb;

		$value = $this->get_request_value();

		if ( empty( $value ) ) {
			return;
		}

		$table   = get_noptin_subscribers_table_name();
		$wpdb->update(
			$table,
			array( 
				'active'    => 1,
				'confirmed' => 1,
			),
			array( 'confirm_key' => $value ),
			'%d',
			'%s'
		);

		clear_noptin_subscriber_cache( $value );

		if ( is_numeric( $page ) ) {
			$page = get_permalink( $page );
		}

		if ( ! empty( $page ) ) {
			wp_redirect( $page );
			exit;
		}

	}

	/**
	 * Notifies the user that they have successfully subscribed.
	 *
	 * @access      public
	 * @since       1.2.5
	 * @return      array
	 */
	public function confirm_subscription( $key ) {

		if ( empty( $key ) ) {
			$this->print_paragraph( __( 'Unable to confirm your subscription to this newsletter.', 'newsletter-optin-box' ) );
			return;
		}

		$this->print_paragraph( __( 'You have successfully subscribed to this newsletter.', 'newsletter-optin-box' ) );
	}

	/**
	 * Confirms a user's subscription to the newsletter.
	 *
	 * @access      public
	 * @since       1.2.7
	 * @return      array
	 */
	public function pre_confirm_subscription( $page ) {
		global $wpdb;

		$value = $this->get_request_value();

		if ( empty( $value ) ) {
			return;
		}

		$table   = get_noptin_subscribers_table_name();
		$wpdb->update(
			$table,
			array( 
				'active'    => 0,
				'confirmed' => 1,
			),
			array( 'confirm_key' => $value ),
			'%d',
			'%s'
		);

		clear_noptin_subscriber_cache( $value );

		if ( is_numeric( $page ) ) {
			$page = get_permalink( $page );
		}

		if ( ! empty( $page ) ) {
			wp_redirect( $page );
			exit;
		}

	}

	/**
	 * Unsubscribes a user
	 *
	 * @access      public
	 * @since       1.2.2
	 * @return      array
	 */
	public function preview_email( $campaign_id ) {

		// Ensure an email campaign is specified.
		if ( empty( $campaign_id ) ) {
			$this->print_paragraph( __( 'Invalid or missing campaign id.', 'newsletter-optin-box' ) );
			return;
		}

		// and that the current user is an administrator
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->print_paragraph( __( 'Only administrators can preview email campaigns.', 'newsletter-optin-box' ) );
			return;
		}

		$campaign = get_post( $campaign_id );

		// Ensure this is a newsletter campaign.
		if ( empty( $campaign ) || 'noptin-campaign' !== $campaign->post_type || 'newsletter' !== get_post_meta( $campaign->ID, 'campaign_type', true ) ) {
			$this->print_paragraph( __( 'Cannot preview this campaign type.', 'newsletter-optin-box' ) );
			return;
		}

		// Fetch current user to use their details as merge tags.
		$user       = wp_get_current_user();
		$subscriber = get_noptin_subscriber_by_email( $user->user_email );
		$data       = array(
			'campaign_id'  => $campaign->ID,
			'template'     => locate_noptin_template( 'email-templates/paste.php' ),
			'email_body'   => $campaign->post_content,
			'preview_text' => get_post_meta( $campaign->ID, 'preview_text', true ),
			'email'        => $user->user_email,
			'merge_tags'   => array(
				'email'       => $user->user_email,
				'first_name'  => $user->user_firstname,
				'second_name' => $user->user_lastname,
			),
		);

		// If the current user is a subscriber, use their subscriber data as merge tags.
		if ( ! empty( $subscriber ) ) {

			$data['subscriber_id']                 = $subscriber->id;
			$data['merge_tags']                    = (array) $subscriber;
			$data['merge_tags']['unsubscribe_url'] = get_noptin_action_url( 'unsubscribe', $subscriber->confirm_key );

			$meta = get_noptin_subscriber_meta( $subscriber->id );
			foreach ( $meta as $key => $values ) {

				if ( isset( $values[0] ) && is_string( $values[0] ) ) {
					$data['merge_tags'][ $key ] = esc_html( $values[0] );
				}
			}
		}

		// Generate and display the email.
		$mailer           = new Noptin_Mailer();
		$mailer->emogrify = false;

		echo  $mailer->get_email( $data );

	}

	public function print_paragraph( $content, $class = 'noptin-padded' ) {
		echo "<p class='$class'>$content</p>";
	}

	public function filter_page_template( $template ) {

		if ( is_noptin_actions_page() ) {

			// No action specified, redirect back home.
			$action = $this->get_request_action();
			if ( empty( $action ) ) {
				wp_redirect( get_home_url() );
				exit;
			}

			$custom_page = get_noptin_option( "pages_{$action}_page" );
			do_action( "noptin_pre_page_$action", $custom_page );

			$template = locate_noptin_template( 'actions-page.php' );
			if ( isset( $_REQUEST['nte'] ) ) {
				$template = locate_noptin_template( 'actions-page-empty.php' );
			}

			$template = apply_filters( 'noptin_actions_page_template', $template );
		}
		return $template;

	}

	public function maybe_hide_admin_bar( $status ) {

		if ( is_noptin_actions_page() ) {
			return false;
		}
		return $status;

	}

	/**
	 * Removes our pages from Yoast sitemaps.
	 */
	public function hide_from_yoast_sitemap( $ids = array() ) {
		$ids[] = get_noptin_action_page();
		return $ids;
	}

	/**
	 * Registers integration options.
	 *
	 * @since 1.2.6
	 * @param array $options Current Noptin settings.
	 * @return array
	 */
	public function add_options( $options ) {

		// Pages help text.
		$options["pages_help_text"] = array(
			'el'              => 'paragraph',
			'section'		  => 'pages',
			'content'         => __( "These options are all optional. If you leave them blank, Noptin will use it's default page.", 'newsletter-optin-box' ), 
		);

		$options["pages_unsubscribe_page"] = array(
			'el'              => 'input',
			'section'		  => 'pages',
			'label'           => __( 'Unsubscribe Page', 'newsletter-optin-box' ),
			'description'     => __( 'Enter an id or url to the page shown to subscribers after they unsubscribe from your newsletter', 'newsletter-optin-box' ),
		);

		$options["pages_confirm_page"] = array(
			'el'              => 'input',
			'section'		  => 'pages',
			'label'           => __( 'Confirmation Page', 'newsletter-optin-box' ),
			'description'     => __( 'Enter an id or url to the page shown to subscribers after they confirm their email', 'newsletter-optin-box' ),
		);

		return apply_filters( "noptin_page_settings", $options );

	}


}
