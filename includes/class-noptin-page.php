<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
		add_action( 'noptin_page_view_in_browser', array( $this, 'browser_preview' ) );

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
		$value     = $this->get_request_value();
		$recipient = $this->get_request_recipient();

		if ( ! empty( $recipient['sid'] ) ) {
			$subscriber = noptin_get_subscriber( $recipient['sid'] );

			if ( $subscriber->exists() ) {
				$_GET['noptin_key'] = $subscriber->get_confirm_key();
			}
		}

		ob_start();

		do_action( "noptin_page_$action", $value, $this->get_request_recipient() );

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
	 * @access public
	 * @since  1.2.2
	 * @return string
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

		// Remove trailing slash.
		$recipient = trim( $recipient, '/' );

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

			$subscriber_id = get_noptin_subscriber_id_by_email( $recipient );

			if ( $subscriber_id ) {
				$default['sid'] = $subscriber_id;
			}

			return $default;
		}

		// Old format (subscribers).
		// Fetch the subscriber.
		$subscriber_id = get_noptin_subscriber_id_by_confirm_key( $recipient );

		if ( $subscriber_id ) {
			$default['sid'] = $subscriber_id;
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

		if ( 'email_open' !== $this->get_request_action() ) {
			return $filter;
		}

		// Log the action.
		$this->_log_open();

		// Display 1x1 pixel transparent gif.
		nocache_headers();
		header( 'Content-type: image/gif' );
		header( 'Content-Length: 42' );
		echo esc_html( base64_decode( 'R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEA' ) );
		exit;

	}

	protected function _log_open() {

		// Fetch recipient.
		$recipient = $this->get_request_recipient();

		// Ensure we have a campaign.
		if ( ! empty( $recipient['cid'] ) && ! empty( $recipient['sid'] ) ) {
			log_noptin_subscriber_campaign_open( $recipient['sid'], $recipient['cid'] );
		}
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
		if ( 'email_click' !== $this->get_request_action() ) {
			return $filter;
		}

		// Fetch recipient.
		$recipient = $this->get_request_recipient();

		// Abort if no destination.
		if ( empty( $recipient['to'] ) ) {
			wp_safe_redirect( get_home_url() );
			exit;
		}

		$destination = str_replace( array( '#038;', '&#38;', '&amp;' ), '&', rawurldecode( $recipient['to'] ) );

		// Ensure we have a campaign.
		if ( ! empty( $recipient['cid'] ) && ! empty( $recipient['sid'] ) ) {
			log_noptin_subscriber_campaign_click( $recipient['sid'], $recipient['cid'], $destination );
		}

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

		return add_noptin_merge_tags( $content, get_noptin_subscriber_merge_fields( $recipient['sid'] ) );

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

		echo wp_kses_post( $this->merge( $msg ) );

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
		$recipient   = $this->get_request_recipient();
		$campaign_id = ! empty( $recipient['cid'] ) ? $recipient['cid'] : 0;

		// Process campaigns.
		if ( ! empty( $campaign_id ) ) {
			increment_noptin_campaign_stat( $campaign_id, '_noptin_unsubscribed' );
		}

		// Process subscribers.
		if ( ! empty( $recipient['sid'] ) ) {
			unsubscribe_noptin_subscriber( $recipient['sid'], $campaign_id );
		}

		// Process users.
		if ( ! empty( $recipient['uid'] ) ) {
			update_user_meta( $recipient['uid'], 'noptin_unsubscribed', 'unsubscribed' );
		}

		// If we are redirecting by page id, fetch the page's permalink.
		if ( is_numeric( $page ) ) {
			$page = get_permalink( $page );
		}

		// If we have a redirect, redirect.
		if ( ! empty( $page ) ) {
			wp_safe_redirect( $page );
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

		echo wp_kses_post( $this->merge( $msg ) );

	}

	/**
	 * Resubscribes a user
	 *
	 * @access      public
	 * @since       1.4.4
	 * @return      array
	 */
	public function pre_resubscribe_user( $page ) {

		// Fetch recipient.
		$recipient = $this->get_request_recipient();

		// Process subscribers.
		if ( ! empty( $recipient['sid'] ) ) {
			resubscribe_noptin_subscriber( $recipient['sid'] );
		}

		// Process users.
		if ( ! empty( $recipient['uid'] ) ) {
			delete_user_meta( $recipient['uid'], 'noptin_unsubscribed' );
		}

		// Process campaigns.
		if ( ! empty( $recipient['cid'] ) ) {
			decrease_noptin_campaign_stat( $recipient['cid'], '_noptin_unsubscribed' );
		}

		// If we have a redirect, redirect.
		if ( ! empty( $page ) ) {
			wp_safe_redirect( $page );
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

		echo wp_kses_post( $this->merge( $msg ) );

	}

	/**
	 * Confirms a user's subscription to the newsletter.
	 *
	 * @access      public
	 * @since       1.2.7
	 * @return      array
	 */
	public function pre_confirm_subscription() {

		// Fetch recipient.
		$recipient = $this->get_request_recipient();

		// Ensure there's a subscriber.
		if ( ! empty( $recipient['sid'] ) ) {
			confirm_noptin_subscriber_email( $recipient['sid'] );
		}
	}

	/**
	 * Previews an email.
	 *
	 * @access public
	 * @since  2.0.0
	 * @return array
	 */
	public function browser_preview() {
		$request = $this->get_request_recipient();

		// Ensure an email campaign is specified.
		if ( empty( $request ) || empty( $request['cid'] ) ) {
			$this->print_paragraph( __( 'Invalid or missing campaign id.', 'newsletter-optin-box' ) );
			return;
		}

		// Fetch campaign.
		$campaign = noptin_get_email_campaign_object( $request['cid'] );

		// Ensure this is a newsletter campaign.
		if ( empty( $campaign ) || ! $campaign->exists() ) {
			$this->print_paragraph( __( 'Invalid or missing campaign id.', 'newsletter-optin-box' ) );
			return;
		}

		// and that the current user is an administrator
		if ( ! current_user_can( get_noptin_capability() ) && empty( $request['email'] ) ) {
			$this->print_paragraph( __( 'Invalid or missing campaign id.', 'newsletter-optin-box' ) );
			return;
		}

		define( 'NOPTIN_PREVIEW_EMAIL', isset( $request['email'] ) ? $request['email'] : '' );

		echo $campaign->get_browser_preview_content( $campaign ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;

	}

	public function print_paragraph( $content, $class = 'noptin-padded' ) {
		printf(
			'<p class="%s">%s</p>',
			esc_attr( $class ),
			wp_kses_post( $content )
		);
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
			wp_safe_redirect( add_query_arg( 'noptin_action', 'failed', get_home_url() ) );
			exit;
		}

		/*
		 * Site admins are allowed to use custom pages
		 * to render the actions page.
		 */
		$custom_page = get_noptin_option( "pages_{$action}_page" );

		// Provide a way to filter the page.
		$custom_page = apply_filters( 'noptin_action_page_redirect', $custom_page, $action, $this );
		do_action( "noptin_pre_page_$action", $custom_page );

		// If we are redirecting by page id, fetch the page's permalink.
		if ( is_numeric( $custom_page ) ) {
			$custom_page = get_permalink( $custom_page );
		}

		// If we have a redirect, redirect.
		if ( ! empty( $custom_page ) ) {
			wp_safe_redirect( $custom_page );
			exit;
		}

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

		$options['pages_unsubscribe_page_message'] = array(
			'el'          => 'textarea',
			'section'	  => 'messages',
			'label'       => __( 'Unsubscription Message', 'newsletter-optin-box' ),
			'placeholder' => $this->default_unsubscription_confirmation_message(),
			'default'	  => $this->default_unsubscription_confirmation_message(),
			'description' => __( 'The message to show to subscribers after they unsubscribe. Only used if you do not provide a redirect url below.', 'newsletter-optin-box' ),
		);

		$options['pages_unsubscribe_page'] = array(
			'el'          => 'input',
			'section'	  => 'messages',
			'label'       => __( 'Unsubscription Redirect', 'newsletter-optin-box' ),
			'placeholder' => 'https://example.com/newsletter-unsubscribed',
			'description' => __( 'Where should we redirect subscribers after they unsubscribe?', 'newsletter-optin-box' ),
		);

		$options['pages_resubscribe_page_message'] = array(
			'el'          => 'textarea',
			'section'	  => 'messages',
			'label'       => __( 'Re-subscription Message', 'newsletter-optin-box' ),
			'placeholder' => $this->default_resubscription_confirmation_message(),
			'default'	  => $this->default_resubscription_confirmation_message(),
			'description' => __( 'The message to show to subscribers after they resubscribe. Only used if you do not provide a redirect url below.', 'newsletter-optin-box' ),
		);

		$options['pages_resubscribe_page'] = array(
			'el'          => 'input',
			'section'	  => 'messages',
			'label'       => __( 'Re-subscription Redirect', 'newsletter-optin-box' ),
			'placeholder' => 'https://example.com/newsletter-resubscribed',
			'description' => __( 'Where should we redirect subscribers after they resubscribe?', 'newsletter-optin-box' ),
		);

		$options['pages_confirm_page_message'] = array(
			'el'          => 'textarea',
			'section'	  => 'messages',
			'label'       => __( 'Confirmation Message', 'newsletter-optin-box' ),
			'placeholder' => $this->default_subscription_confirmation_message(),
			'default'	  => $this->default_subscription_confirmation_message(),
			'description' => __( 'The message to show to subscribers after they confirm their email address. Only used if you do not provide a redirect url below.', 'newsletter-optin-box' ),
		);

		$options['pages_confirm_page'] = array(
			'el'          => 'input',
			'section'     => 'messages',
			'label'       => __( 'Confirmation Redirect', 'newsletter-optin-box' ),
			'description' => __( 'Where should we redirect subscribers after they confirm their emails?', 'newsletter-optin-box' ),
			'placeholder' => 'https://example.com/newsletter-confirmed',
		);

		return apply_filters( 'noptin_page_settings', $options );

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
		$heading = esc_html__( 'Thank You, Again!', 'newsletter-optin-box' );
		$message = esc_html__( 'You have been resubscribed to our newsletter.', 'newsletter-optin-box' );
		return "<h1>$heading</h1>\n\n<p>$message</p>";
	}

	/**
	 * The default subscription confirmation message.
	 *
	 * @since 1.2.9
	 * @return string
	 */
	public function default_subscription_confirmation_message() {
		$heading = esc_html__( 'Thank You', 'newsletter-optin-box' );
		$message = esc_html__( 'You have successfully subscribed to this newsletter.', 'newsletter-optin-box' );
		return "<h1>$heading</h1>\n\n<p>$message</p>";
	}

}
