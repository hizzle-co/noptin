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

		// User resubscribe.
		add_action( 'noptin_page_resubscribe', array( $this, 'resubscribe_user' ) );
		add_action( 'noptin_pre_page_resubscribe', array( $this, 'pre_resubscribe_user' ) );

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
		add_action( 'parse_request', array( $this, 'listen' ), 0 );

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

		// New format. example.com?noptin_ns=$action
		if ( ! empty( $_GET['noptin_ns'] ) ) {
			return sanitize_title_with_dashes( trim( urldecode( $_GET['noptin_ns'] ) ) );
		}

		// Backwards compatibility. example.com/noptin_newsletter/$action
		$matched_var = get_query_var( 'noptin_newsletter' );

		if ( ! empty( $matched_var ) ) {
			return sanitize_title_with_dashes( trim( urldecode( $matched_var ) ) );
		}

		// More backwards compatibility.
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

		if ( ! empty( $_GET['cid'] ) && is_numeric( $_GET['cid'] ) ) {
			$campaign_id   = intval( $_GET['cid'] );

			if ( ! empty( $_GET['sid'] ) ) {
				$subscriber_id = intval( $_GET['sid'] );
				log_noptin_subscriber_campaign_open( $subscriber_id, $campaign_id );
			}

			if ( ! empty( $_GET['uid'] ) ) {
				$user_id          = intval( $_GET['user_id'] );
				$opened_campaigns = wp_parse_id_list( get_user_meta( $user_id, '_opened_noptin_campaigns', true ) );

				if ( ! in_array( (int) $campaign_id, $opened_campaigns, true ) ) {
					$opened_campaigns[] = $campaign_id;
					update_user_meta( $user_id, '_opened_noptin_campaigns', $opened_campaigns );
					update_user_meta( $user_id, "_noptin_campaign_{$campaign_id}_opened", 1 );

					// We want to log this once.
					if ( empty( $_GET['sid'] ) ) {
						$open_counts = (int) get_post_meta( $campaign_id, '_noptin_opens', true );
						update_post_meta( $campaign_id, '_noptin_opens', $open_counts + 1 );
					}

					do_action( 'log_noptin_user_campaign_open', $user_id, $campaign_id );

				}

			}

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

		if ( ! empty( $_GET['cid'] ) && is_numeric( $_GET['cid'] ) ) {
			$campaign_id   = intval( $_GET['cid'] );

			if ( ! empty( $_GET['sid'] ) ) {
				$subscriber_id = intval( $_GET['sid'] );
				log_noptin_subscriber_campaign_click( $subscriber_id, $campaign_id, $to );
			}

			if ( ! empty( $_GET['uid'] ) ) {
				$user_id           = intval( $_GET['user_id'] );
				$clicked_campaigns = noptin_parse_list( get_user_meta( $user_id, '_clicked_noptin_campaigns', true ) );

				// We want to log this once.
				if ( empty( $_GET['sid'] ) ) {
					$open_counts = (int) get_post_meta( $campaign_id, '_noptin_opens', true );
					update_post_meta( $campaign_id, '_noptin_opens', $open_counts + 1 );
				}

				if ( ! isset( $clicked_campaigns[ $campaign_id ] ) ) {
					$clicked_campaigns[ $campaign_id ] = array();
				}

				if ( ! in_array( $to, $clicked_campaigns[ $campaign_id ], true ) ) {
					$clicked_campaigns[ $campaign_id ][] = noptin_clean( $to );
					update_user_meta( $user_id, '_clicked_noptin_campaigns', $clicked_campaigns );
					update_user_meta( $user_id, "_noptin_campaign_{$campaign_id}_clicked", 1 );

					$click_counts = (int) get_post_meta( $campaign_id, '_noptin_clicks', true );
					update_post_meta( $campaign_id, '_noptin_clicks', $click_counts + 1 );

					do_action( 'log_noptin_user_campaign_click', $user_id, $campaign_id, $to );
				}

			}

		}

		wp_redirect( $to );
		exit;

	}

	/**
	 * Merges Noptin content.
	 *
	 * @access      public
	 * @since       1.0.6
	 * @return      array
	 */
	public function merge( $content ) {

		$subscriber = get_current_noptin_subscriber_id();

		if ( empty( $subscriber ) ) {
			return '';
		}

		return add_noptin_merge_tags(  $content, get_noptin_subscriber_merge_fields( $subscriber ) );

	}

	/**
	 * Notifies the user that they have successfuly unsubscribed.
	 *
	 * @access      public
	 * @since       1.0.6
	 * @return      array
	 */
	public function unsubscribe_user( $key ) {
		$msg = get_noptin_option( 'pages_unsubscribe_page_message' );

		if ( empty( $msg ) ) {
			$msg = $this->default_unsubscription_confirmation_message();
		}

		$msg = str_ireplace( '[[resubscribe_url]]', get_noptin_action_url( 'resubscribe', $key ), $msg );

		echo $this->merge( $msg );

	}

	/**
	 * Unsubscribes a user
	 *
	 * @access      public
	 * @since       1.2.7
	 * @return      array
	 */
	public function pre_unsubscribe_user( $page ) {

		// Make sure that the confirmation key exists.
		$value = $this->get_request_value();

		if ( empty( $value )  ) {
			return;
		}

		if ( is_email( $value ) ) {
			$user = get_user_by( 'email', $value );

			if ( $user ) {
				update_user_meta( $user->ID, 'noptin_unsubscribed', 'unsubscribed' );
			}
		} else {
			$_GET['noptin_key'] = sanitize_text_field( $value );
		}

		// Fetch the subscriber.
		$subscriber = Noptin_Subscriber::get_data_by( 'confirm_key', $value );

		// Unsubscribe them.
		unsubscribe_noptin_subscriber( $subscriber );

		// If we are redirecting by page id, fetch the page's permalink.
		if ( is_numeric( $page ) ) {
			$page = get_permalink( $page );
		}

		// If we have a redirect, redirect.
		if ( ! empty( $page ) ) {
			wp_redirect( $page );
			exit;
		}

	}

	/**
	 * Notifies the user that they have successfuly resubscribed.
	 *
	 * @access      public
	 * @since       1.4.4
	 * @return      array
	 */
	public function resubscribe_user( $key ) {
		$msg = get_noptin_option( 'pages_resubscribe_page_message' );

		if ( empty( $msg ) ) {
			$msg = $this->default_resubscription_confirmation_message();
		}

		$msg = str_ireplace( '[[unsubscribe_url]]', get_noptin_action_url( 'unsubscribe', $key ), $msg );

		echo $this->merge( $msg );

	}

	/**
	 * Resubscribes a user
	 *
	 * @access      public
	 * @since       1.4.4
	 * @return      array
	 */
	public function pre_resubscribe_user( $page ) {
		global $wpdb;

		// Make sure that the confirmation key exists.
		$value = $this->get_request_value();

		if ( empty( $value )  ) {
			return;
		}

		// Fetch the subscriber.
		$subscriber = Noptin_Subscriber::get_data_by( 'confirm_key', $value );

		// Resubscribe them.
		if ( ! empty( $subscriber ) && ! empty( $subscriber->id ) ) {

			$wpdb->update(
				get_noptin_subscribers_table_name(),
				array( 'active' => 0 ),
				array( 'id' => $subscriber->id ),
				'%d',
				'%d'
			);

			$_GET['noptin_key'] = sanitize_text_field( $value );
		}

		// If we have a redirect, redirect.
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
	public function confirm_subscription() {

		$msg = get_noptin_option( 'pages_confirm_page_message' );

		if ( empty( $msg ) ) {
			$msg = $this->default_subscription_confirmation_message();
		}

		echo $this->merge( $msg );

	}

	/**
	 * Confirms a user's subscription to the newsletter.
	 *
	 * @access      public
	 * @since       1.2.7
	 * @return      array
	 */
	public function pre_confirm_subscription( $page ) {
		$value = $this->get_request_value();

		if ( empty( $value ) ) {
			return;
		}

		// Fetch the subscriber.
		$subscriber = Noptin_Subscriber::get_data_by( 'confirm_key', $value );

		// And de-activate them.
		confirm_noptin_subscriber_email( $subscriber );

		// If we are redirecting by page id, fetch the page's permalink.
		if ( is_numeric( $page ) ) {
			$page = get_permalink( $page );
		}

		// If we have a redirect, redirect.
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
		if ( ! current_user_can( get_noptin_capability() ) ) {
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
		$sender     = get_noptin_email_sender( $campaign->ID );
		$data       = array(
			'campaign_id'   => $campaign->ID,
			'email_subject' => $campaign->post_title,
			'email_body'    => $campaign->post_content,
			'preview_text'  => get_post_meta( $campaign->ID, 'preview_text', true ),
			'email'         => $user->user_email,
			'merge_tags'    => array(
				'email'       => $user->user_email,
				'first_name'  => $user->user_firstname,
				'second_name' => $user->user_lastname,
				'last_name'   => $user->user_lastname,
			),
		);

		// If the current user is a subscriber, use their subscriber data as merge tags.
		if ( $subscriber->exists() ) {
			$data['subscriber_id'] = $subscriber->id;
			$data['merge_tags']    = array_merge( $data['merge_tags'], get_noptin_subscriber_merge_fields( $subscriber ) );
		}

		$data['merge_tags']    = array_merge( $data['merge_tags'], apply_filters( "noptin_{$sender}_dummy_merge_tags", array() ) );

		$custom_merge_tags = get_post_meta( $campaign->ID, 'custom_merge_tags', true );
		if ( is_array( $custom_merge_tags ) ) {
			$data['merge_tags'] = array_merge( $data['merge_tags'], $custom_merge_tags );
		}

		// Generate and display the email.
		$data = noptin()->mailer->prepare( $data );
		echo apply_filters( 'noptin_generate_preview_email', $data['email_body'], $data );
		exit;

	}

	public function print_paragraph( $content, $class = 'noptin-padded' ) {
		echo "<p class='$class'>$content</p>";
	}

	public function listen() {

		if ( empty( $_GET['noptin_ns'] ) && empty( $GLOBALS['wp']->query_vars['noptin_newsletter'] ) ) {
			return;
		}

		define( 'IS_NOPTIN_ACTIONS_PAGE', 1 );
		show_admin_bar( false );
		add_filter( 'pre_handle_404', '__return_true' );
		remove_all_actions( 'template_redirect' );
		add_action( 'template_redirect', array( $this, 'load_actions_page' ), 1 );
	}

	public function load_actions_page() {

		// No action specified, redirect back home.
		$action = $this->get_request_action();
		if ( empty( $action ) ) {
			wp_redirect( add_query_arg( 'noptin_action', 'failed', get_home_url() ) );
			exit;
		}

		/*
		 * Site admins are allowed to use custom pages
		 * to render the actions page.
		 */
		$custom_page = get_noptin_option( "pages_{$action}_page" );

		// They can also set the page as a URL param.
		if ( isset( $_GET['rdt'] ) ) {
			$custom_page = esc_url( urldecode( $_GET['rdt'] ) );
		}

		// Provide a way to filter the page.
		$custom_page = apply_filters( 'noptin_action_page_redirect', $custom_page, $action, $this );
		do_action( "noptin_pre_page_$action", $custom_page );

		$template = locate_noptin_template( 'actions-page.php' );
		if ( isset( $_GET['nte'] ) ) {
			$template = locate_noptin_template( 'actions-page-empty.php' );
		}

		include apply_filters( 'noptin_actions_page_template', $template, $action );
		exit;

	}

	/**
	 * Registers confirmation pages options.
	 *
	 * @since 1.2.6
	 * @param array $options Current Noptin settings.
	 * @return array
	 */
	public function add_options( $options ) {

		$options["pages_unsubscribe_page_message"] = array(
			'el'              => 'textarea',
			'section'		  => 'messages',
			'label'           => __( 'Unsubscription Message', 'newsletter-optin-box' ),
			'placeholder'     => $this->default_unsubscription_confirmation_message(),
			'default'		  => $this->default_unsubscription_confirmation_message(),
			'description'     => __( 'The message to show to subscribers after they unsubscribe. Only used if you do not provide a redirect url below.', 'newsletter-optin-box' ),
		);

		$options["pages_unsubscribe_page"] = array(
			'el'              => 'input',
			'section'		  => 'messages',
			'label'           => __( 'Unsubscription Redirect', 'newsletter-optin-box' ),
			'placeholder'     => 'https://example.com/newsletter-unsubscribed',
			'description'     => __( 'Where should we redirect subscribers after they unsubscribe?', 'newsletter-optin-box' ),
		);

		$options["pages_resubscribe_page_message"] = array(
			'el'              => 'textarea',
			'section'		  => 'messages',
			'label'           => __( 'Re-subscription Message', 'newsletter-optin-box' ),
			'placeholder'     => $this->default_resubscription_confirmation_message(),
			'default'		  => $this->default_resubscription_confirmation_message(),
			'description'     => __( 'The message to show to subscribers after they resubscribe. Only used if you do not provide a redirect url below.', 'newsletter-optin-box' ),
		);

		$options["pages_resubscribe_page"] = array(
			'el'              => 'input',
			'section'		  => 'messages',
			'label'           => __( 'Re-subscription Redirect', 'newsletter-optin-box' ),
			'placeholder'     => 'https://example.com/newsletter-resubscribed',
			'description'     => __( 'Where should we redirect subscribers after they resubscribe?', 'newsletter-optin-box' ),
		);

		$options["pages_confirm_page_message"] = array(
			'el'              => 'textarea',
			'section'		  => 'messages',
			'label'           => __( 'Confirmation Message', 'newsletter-optin-box' ),
			'placeholder'     => $this->default_subscription_confirmation_message(),
			'default'		  => $this->default_subscription_confirmation_message(),
			'description'     => __( 'The message to show to subscribers after they confirm their email address. Only used if you do not provide a redirect url below.', 'newsletter-optin-box' ),
		);

		$options["pages_confirm_page"] = array(
			'el'              => 'input',
			'section'		  => 'messages',
			'label'           => __( 'Confirmation Redirect', 'newsletter-optin-box' ),
			'description'     => __( 'Where should we redirect subscribers after they confirm their emails?', 'newsletter-optin-box' ),
			'placeholder'     => 'https://example.com/newsletter-confirmed',
		);

		return apply_filters( "noptin_page_settings", $options );

	}

	/**
	 * The default unsubscription confirmation message.
	 *
	 * @since 1.2.9
	 * @return string
	 */
	public function default_unsubscription_confirmation_message() {
		$heading = __( 'Thank You', 'newsletter-optin-box' );
		$message = __( "You have been unsubscribed from this mailing list and won't receive any emails from us.", 'newsletter-optin-box' );
		return "<h1>$heading</h1>\n\n<p>$message</p>";
	}

	/**
	 * The default resubscription confirmation message.
	 *
	 * @since 1.4.4
	 * @return string
	 */
	public function default_resubscription_confirmation_message() {
		$heading = __( 'Thank You, Again!', 'newsletter-optin-box' );
		$message = __( "You have been resubscribed to our newsletter.", 'newsletter-optin-box' );
		return "<h1>$heading</h1>\n\n<p>$message</p>";
	}

	/**
	 * The default subscription confirmation message.
	 *
	 * @since 1.2.9
	 * @return string
	 */
	public function default_subscription_confirmation_message() {
		$heading = __( 'Thank You', 'newsletter-optin-box' );
		$message = __( 'You have successfully subscribed to this newsletter.', 'newsletter-optin-box' );
		return "<h1>$heading</h1>\n\n<p>$message</p>";
	}

}
