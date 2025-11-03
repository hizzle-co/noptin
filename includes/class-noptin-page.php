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
	 * Confirm actions.
	 */
	private $confirmable_actions = array();

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Register shortcode.
		add_shortcode( 'noptin_action_page', array( $this, 'do_shortcode' ) );

		// Filter template.
		add_action( 'parse_request', array( $this, 'listen' ), 0 );

		// Pages settings.
		add_filter( 'noptin_get_settings', array( $this, 'add_options' ), 100 );
	}

	/**
	 * Adds a confirmable action.
	 *
	 * @param string $action
	 * @param string $label
	 * @param string $description
	 */
	public function add_confirmable_action( $action, $label, $description = null ) {
		$this->confirmable_actions[ $action ] = array(
			'label'       => $label,
			'description' => $description ?? __( 'Please click the button below to confirm this action.', 'newsletter-optin-box' ),
		);
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

		if ( ! has_action( "noptin_page_$action" ) ) {
			$msg = get_noptin_option( "pages_{$action}_page_message" );

			if ( empty( $msg ) ) {
				$options = $this->add_options( array() );
				if ( isset( $options[ 'pages_' . $action ]['settings'][ 'pages_' . $action . '_page_message' ]['default'] ) ) {
					$msg = $options[ 'pages_' . $action ]['settings'][ 'pages_' . $action . '_page_message' ]['default'];
				}
			}

			echo wp_kses_post( $this->merge( $msg ) );
		} else {
			do_action( "noptin_page_$action", $value, $this->get_request_recipient(), $this );
		}

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
				'subscriber' => get_current_noptin_subscriber_id(),
				'user'       => get_current_user_id(),
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

		// Backwards compatibility (Users).
		if ( is_email( $recipient ) ) {
			return array(
				'email' => $recipient,
			);
		}

		// Old format (subscribers).
		// Fetch the subscriber.
		$subscriber_id = get_noptin_subscriber_id_by_confirm_key( $recipient );

		if ( $subscriber_id ) {
			return array(
				'subscriber' => $subscriber_id,
			);
		}

		return $default;
	}

	/**
	 * Merges Noptin content.
	 *
	 * @access      public
	 * @since       1.0.6
	 * @return      array
	 */
	public function merge( $content ) {
		return noptin_parse_email_content_tags( $content );
	}

	public function maybe_autosubmit_form( $action, $action_info ) {
		if ( empty( $_GET['noptin-autosubmit'] ) ) {
			get_noptin_template(
				'actions-page-confirm.php',
				array(
					'action_name'        => $action,
					'action_label'       => $action_info['label'],
					'action_description' => $action_info['description'],
				)
			);
			exit;
		}
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

		// Set the current email recipient.
		do_action( 'noptin_pre_load_actions_page', $this->get_request_recipient(), $action, $this );

		// Prevent accidental actions.
		if ( isset( $this->confirmable_actions[ $action ] ) ) {
			$this->maybe_autosubmit_form( $action, $this->confirmable_actions[ $action ] );
		}

		/*
		 * Site admins are allowed to use custom pages
		 * to render the actions page.
		 */
		$custom_page = get_noptin_option( "pages_{$action}_page" );

		// Provide a way to filter the page.
		$custom_page = apply_filters( 'noptin_action_page_redirect', $custom_page, $action, $this );

		// Deprecated.
		do_action( "noptin_pre_page_$action", $custom_page, $this->get_request_recipient(), $this );

		// Handle the action.
		do_action( "noptin_actions_handle_$action", $this, $this->get_request_recipient() );

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

		$options['pages_confirm'] = array(
			'el'       => 'settings_group',
			'label'    => __( 'Subscription Confirmation', 'newsletter-optin-box' ),
			'section'  => 'messages',
			'settings' => array(
				'pages_confirm_page_message' => array(
					'el'          => 'textarea',
					'label'       => __( 'Confirmation Message', 'newsletter-optin-box' ),
					'placeholder' => $this->default_subscription_confirmation_message(),
					'default'     => $this->default_subscription_confirmation_message(),
					'tooltip'     => __( 'The message to show to subscribers after they confirm their email address. Only used if you do not provide a redirect url below.', 'newsletter-optin-box' ),
				),
				'pages_confirm_page'         => array(
					'el'          => 'input',
					'label'       => __( 'Confirmation Redirect', 'newsletter-optin-box' ),
					'placeholder' => 'https://example.com/newsletter-confirmed',
					'tooltip'     => __( 'Where should we redirect subscribers after they confirm their emails?', 'newsletter-optin-box' ),
				),
			),
		);

		$options['pages_unsubscribe'] = array(
			'el'       => 'settings_group',
			'label'    => __( 'Unsubscription', 'newsletter-optin-box' ),
			'section'  => 'messages',
			'settings' => array(
				'pages_unsubscribe_page_message' => array(
					'el'          => 'textarea',
					'label'       => __( 'Unsubscription Message', 'newsletter-optin-box' ),
					'placeholder' => $this->default_unsubscription_confirmation_message(),
					'default'     => $this->default_unsubscription_confirmation_message(),
					'tooltip'     => __( 'The message to show to subscribers after they unsubscribe. Only used if you do not provide a redirect url below.', 'newsletter-optin-box' ),
				),
				'pages_unsubscribe_page'         => array(
					'el'          => 'input',
					'label'       => __( 'Unsubscription Redirect', 'newsletter-optin-box' ),
					'placeholder' => 'https://example.com/newsletter-unsubscribed',
					'tooltip'     => __( 'Where should we redirect subscribers after they unsubscribe?', 'newsletter-optin-box' ),
				),
			),
		);

		$options['pages_resubscribe'] = array(
			'el'       => 'settings_group',
			'label'    => __( 'Re-subscription', 'newsletter-optin-box' ),
			'section'  => 'messages',
			'settings' => array(
				'pages_resubscribe_page_message' => array(
					'el'          => 'textarea',
					'label'       => __( 'Re-subscription Message', 'newsletter-optin-box' ),
					'placeholder' => $this->default_resubscription_confirmation_message(),
					'default'     => $this->default_resubscription_confirmation_message(),
					'tooltip'     => __( 'The message to show to subscribers after they resubscribe. Only used if you do not provide a redirect url below.', 'newsletter-optin-box' ),
				),
				'pages_resubscribe_page'         => array(
					'el'          => 'input',
					'label'       => __( 'Re-subscription Redirect', 'newsletter-optin-box' ),
					'placeholder' => 'https://example.com/newsletter-resubscribed',
					'tooltip'     => __( 'Where should we redirect subscribers after they resubscribe?', 'newsletter-optin-box' ),
				),
			),
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
