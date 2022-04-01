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
		add_action( 'noptin_page_preview_newsletter', array( $this, 'preview_email' ) );
		add_action( 'noptin_page_preview_automation', array( $this, 'preview_automated_email' ) );

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
		return isset( $_GET['nv'] ) ? sanitize_text_field( urldecode( $_REQUEST['nv'] ) ) : '';
	}

	/**
	 * Retrieves the request recipient
	 *
	 * @access      public
	 * @since       1.7.0
	 * @return      array
	 */
	public function get_request_recipient() {

		// Prepare default recipient.
		$default = array_filter(
			array(
				'sid' => get_current_noptin_subscriber_id(),
				'uid' => get_current_user_id(),
			)
		);

		// Fetch recipient.
		$recipient = $this->get_request_value();

		// Fallback to current user / subscriber.
		if ( empty( $recipient ) ) {
			return $default;
		}

		// Try to decode the recipient.
		// New recipient format.
		$decoded = json_decode( noptin_decrypt( $recipient ), true );

		if ( ! empty( $decoded ) && is_array( $decoded ) ) {
			return $decoded;
		}

		// Old format (Users).
		if ( is_email( $recipient ) ) {
			$user = get_user_by( 'email', $recipient );

			if ( $user ) {
				$default['uid'] = $user->ID;
			}

			return $default;
		}

		// Old format (subscribers).
		// Fetch the subscriber.
		$subscriber = Noptin_Subscriber::get_data_by( 'confirm_key', $recipient );

		if ( $subscriber ) {
			$default['sid'] = $subscriber->id;
		}

		return $default;
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

		// Log the action.
		$this->_log_open();

		// Display 1x1 pixel transparent gif.
		nocache_headers();
		header( 'Content-type: image/gif' );
		header( 'Content-Length: 42' );
		echo base64_decode( 'R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEA' );
		exit;

	}

	protected function _log_open() {

		// Fetch recipient.
		$recipient = $this->get_request_recipient();

		// Ensure we have a campaign.
		if ( ! empty( $recipient['cid'] ) ) {

			// Process subscribers.
			if ( ! empty( $recipient['sid'] ) ) {
				$subscriber_logged = log_noptin_subscriber_campaign_open( $recipient['sid'], $recipient['cid'] );
			}

			// Process users.
			if ( ! empty( $recipient['uid'] ) ) {
				$opened_campaigns = wp_parse_id_list( get_user_meta( $recipient['uid'], '_opened_noptin_campaigns', true ) );

				if ( ! in_array( (int) $recipient['cid'], $opened_campaigns, true ) ) {

					// Log the campaign open.
					$opened_campaigns[] = $recipient['cid'];
					update_user_meta( $recipient['uid'], '_opened_noptin_campaigns', $opened_campaigns );

					// Fire action.
					do_action( 'log_noptin_user_campaign_open', $recipient['uid'], $recipient['cid'] );

					$user_logged = true;
				}

			}

			// Increment stats.
			if ( ! empty( $subscriber_logged ) || ! empty( $user_logged ) ) {
				increment_noptin_campaign_stat( $recipient['cid'], '_noptin_opens' );
			}

		};

	}

	/**
	 * Logs email clicks
	 *
	 * @access      public
	 * @since       1.2.0
	 * @return      array
	 */
	public function email_click( $filter ) {

		// Ensure this is our action.
		if ( 'email_click' != $this->get_request_action() ) {
			return $filter;
		}

		// Log the open.
		$this->_log_open();

		// Fetch recipient.
		$recipient = $this->get_request_recipient();

		// Abort if no destination.
		if ( empty( $recipient['to'] ) ) {
			wp_redirect( get_home_url() );
			exit;
		}

		$destination = str_replace( '&amp;', '&', $recipient['to'] );

		// Ensure we have a campaign.
		if ( ! empty( $recipient['cid'] ) ) {

			// Process subscribers.
			if ( ! empty( $recipient['sid'] ) ) {
				$subscriber_logged = log_noptin_subscriber_campaign_click( $recipient['sid'], $recipient['cid'], $destination );
			}

			// Process users.
			if ( ! empty( $recipient['uid'] ) ) {

				$clicked_campaigns = noptin_parse_list( get_user_meta( $recipient['uid'], '_clicked_noptin_campaigns', true ) );

				if ( empty( $clicked_campaigns[ $recipient['cid'] ] ) ) {
					$clicked_campaigns[ $recipient['cid'] ] = array();
				}

				if ( ! in_array( $destination, $clicked_campaigns[ $recipient['cid'] ], true ) ) {

					// Log the campaign click.
					$clicked_campaigns[ $recipient['cid'] ][] = noptin_clean( $destination );
					update_user_meta( $recipient['uid'], '_clicked_noptin_campaigns', $clicked_campaigns );

					// Fire action.
					do_action( 'log_noptin_user_campaign_click', $recipient['uid'], $recipient['cid'], $destination );

					$user_logged = true;
				}

			}

			// Increment stats.
			if ( ! empty( $subscriber_logged ) || ! empty( $user_logged ) ) {
				increment_noptin_campaign_stat( $recipient['cid'], '_noptin_clicks' );
			}

		};

		wp_redirect( $destination );
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

		$recipient = $this->get_request_recipient();

		if ( empty( $recipient['sid'] ) ) {
			return $content;
		}

		return add_noptin_merge_tags(  $content, get_noptin_subscriber_merge_fields( $recipient['sid'] ) );

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

		// Fetch recipient.
		$recipient = $this->get_request_recipient();

		// Process subscribers.
		if ( ! empty( $recipient['sid'] ) ) {
			unsubscribe_noptin_subscriber( $recipient['sid'] );
		}

		// Process users.
		if ( ! empty( $recipient['uid'] ) ) {
			update_user_meta( $recipient['uid'], 'noptin_unsubscribed', 'unsubscribed' );
			do_action( 'noptin_unsubscribe_user', $recipient['uid'] );
		}

		// Process campaigns.
		if ( ! empty( $recipient['cid'] ) ) {
			increment_noptin_campaign_stat( $recipient['cid'], '_noptin_unsubscribed' );
		}

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

		// Fetch recipient.
		$recipient = $this->get_request_recipient();

		// Process subscribers.
		if ( ! empty( $recipient['sid'] ) ) {

			$wpdb->update(
				get_noptin_subscribers_table_name(),
				array( 'active' => 0 ),
				array( 'id' => $recipient['sid'] ),
				'%d',
				'%d'
			);

			do_action( 'noptin_resubscribe_subscriber', $recipient['uid'] );

		}

		// Process users.
		if ( ! empty( $recipient['uid'] ) ) {
			delete_user_meta( $recipient['uid'], 'noptin_unsubscribed' );
			do_action( 'noptin_resubscribe_user', $recipient['uid'] );
		}

		// Process campaigns.
		if ( ! empty( $recipient['cid'] ) ) {
			decrease_noptin_campaign_stat( $recipient['cid'], '_noptin_unsubscribed' );
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

		// Fetch recipient.
		$recipient = $this->get_request_recipient();

		// Abort if no subscriber.
		if ( empty( $recipient['sid'] ) ) {
			return;
		}

		$ip_address = noptin_get_user_ip();
		if ( ! empty( $ip_address ) && '::1' !== $ip_address ) {
			update_noptin_subscriber_meta( $recipient['sid'], 'ip_address', sanitize_text_field( $ip_address ) );
		}

		// Confirm them.
		confirm_noptin_subscriber_email( $recipient['sid'] );

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
	 * Previews an email.
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

		$campaign = new Noptin_Newsletter_Email( $campaign_id );

		// Ensure this is a newsletter campaign.
		if ( ! $campaign->exists() ) {
			$this->print_paragraph( __( 'Cannot preview this email.', 'newsletter-optin-box' ) );
			return;
		}

		echo noptin()->emails->newsletter->generate_preview( $campaign );
		exit;

	}

	/**
	 * Previews an automated email email.
	 *
	 * @access      public
	 * @since       1.7.0
	 * @return      array
	 */
	public function preview_automated_email( $campaign_id ) {

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

		$campaign = new Noptin_Automated_Email( $campaign_id );

		// Ensure this is a newsletter campaign.
		if ( ! $campaign->exists() ) {
			$this->print_paragraph( __( 'Cannot preview this campaign type.', 'newsletter-optin-box' ) );
			return;
		}

		echo noptin()->emails->automated_email_types->generate_preview( $campaign );
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
