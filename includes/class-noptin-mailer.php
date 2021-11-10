<?php
/**
 * class Noptin_Mailer class.
 */

defined( 'ABSPATH' ) || exit;

class Noptin_Mailer {

	/**
	 * Whether or not we should inline CSS into the email.
	 */
	public $inline_css = true;

	/**
	 * Strips unavailable merge tags.
	 */
	public $strip_tags = true;

	/**
	 * For backwards compatibility;
	 */
	private static $initialized = false;

	/**
	 * The wp_mail() data.
	 */
	public $wp_mail_data = null;

	/**
	 * Whether or not we should disable template plugins.
	 */
	public $disable_template_plugins = true;

	/**
	 * The current emails mailer data.
	 */
	public $mailer_data = array();

	/**
	 * The class constructor.
	 */
	public function __construct() {

		// We only want to init the class once.
		if ( empty( self::$initialized ) ) {
			$this->init();
			self::$initialized = true;
		}

	}

	/**
	 * Initialize the class.
	 */
	private function init() {

		// Send any background emails.
		add_action( 'send_bg_noptin_email', array( $this, '_handle_background_send' ) );

	}

	/**
	 * Prepares an email for sending.
	 */
	public function prepare( $data = array() ) {

		// Ensure that we have merge tags.
		if ( empty( $data['merge_tags'] ) ) {
			$data['merge_tags'] = array();
		}

		// Ensure that a few variables are set.
		$data['email_subject']   = $this->get_subject( $data );
		$data['title']           = $data['email_subject'];
		$data['logo_url']        = $this->get_logo_url( $data );
		$data['tracker']         = $this->get_tracker( $data );
		$data['permission_text'] = ! isset( $data['permission_text'] ) ? $this->get_permission_text( $data ) : $data['permission_text'];
		$data['permission_text'] = wpautop( $this->merge( $data['permission_text'], $data['merge_tags'] ) );
		$data['footer_text']     = ! isset( $data['footer_text'] ) ? $this->get_footer_text( $data ) : $data['footer_text'];
		$data['footer_text']     = wpautop( $this->merge( $data['footer_text'], $data['merge_tags'] ) );
		$data['hero_text']       = empty( $data['hero_text'] ) ? '' : $data['hero_text'];
		$data['cta_url']         = empty( $data['cta_url'] ) ? '' : $data['cta_url'];
		$data['cta_text']        = empty( $data['cta_text'] ) ? '' : $data['cta_text'];
		$data['after_cta_text']  = empty( $data['after_cta_text'] ) ? '' : $data['after_cta_text'];
		$data['after_cta_text2'] = empty( $data['after_cta_text2'] ) ? '' : $data['after_cta_text2'];
		$data['email_body']      = $this->build_email( $data );
		$this->mailer_data       = $data;

		return $data;
	}

	/**
	 * Prepares an email then sends it.
	 */
	public function prepare_then_send( $data = array() ) {
		$data = $this->prepare( $data );
		return $this->send( $data['email'], $data['email_subject'], $data['email_body'] );
	}

	/**
	 * Prepares an email then sends it in the background.
	 */
	public function prepare_then_bg_send( $data = array() ) {
		$data = $this->prepare( $data );
		return $this->background_send( $data['email'], $data['email_subject'], $data['email_body'] );
	}

	/**
	 * Returns the email subject with merge tags replaced.
	 */
	public function get_subject( $data = array() ) {

		if ( empty( $data['email_subject'] ) ) {
			return '';
		}

		$subject = trim( $data['email_subject'] );

		if ( empty( $data['merge_tags'] ) ) {
			$data['merge_tags'] = array();
		}

		$subject = $this->merge( $subject, $data['merge_tags'] );

		return $subject;

	}

	/**
	 * Returns the email body with the template compiled and merge tags replaced.
	 *
	 */
	public function get_email( $data = array() ) {
		$data = $this->prepare( $data );
		return $data['email_body'];
	}

	/**
	 * Retrieves the logo URL.
	 */
	public function get_logo_url( $data ) {
		return apply_filters( 'noptin_email_logo_url', get_noptin_option( 'logo_url', '' ), $data, $this );
	}

	/**
	 * Returns the code used to track email opens.
	 */
	public function get_tracker( $data = array() ) {

		$track_campaign_stats = get_noptin_option( 'track_campaign_stats', true );
		if ( empty( $track_campaign_stats ) || empty( $data['campaign_id'] ) ) {
			return '';
		}

		if ( empty( $data['subscriber_id'] ) && empty( $data['user_id'] ) ) {
			return '';
		}

		$url = get_noptin_action_url( 'email_open' );

		$url = add_query_arg(
			array(
				'uid'         => isset( $data['user_id'] ) ? intval( $data['user_id'] ) : false,
				'sid'         => isset( $data['subscriber_id'] ) ? intval( $data['subscriber_id'] ) : false,
				'cid' => intval( $data['campaign_id'] ),
			),
			$url
		);

		$url = esc_url( $url );

		return "<img src='$url' style='border:0;width:1px;height:1px;' />";

	}

	/**
	 * Retrieves the default merge tags
	 */
	public function get_default_merge_tags() {

		$default_merge_tags = array(
			'date'             => date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ),
			'year'             => date( 'Y', current_time( 'timestamp' ) ),
			'blog_name'        => get_bloginfo( 'name' ),
			'blog_description' => get_bloginfo( 'description' ),
			'home_url'         => get_home_url(),
			'noptin'   		   => sprintf(
				'<a target="_blank" href="https://noptin.com/?utm_medium=powered-by&utm_campaign=email-campaign&utm_source=%s">Noptin</a>',
				urlencode( esc_url( get_home_url() ) )
			),
			'noptin_company'   => get_noptin_option( 'company', '' ),
		);

		return apply_filters( 'noptin_mailer_default_merge_tags', $default_merge_tags, $this );

	}

	/**
	 * Retrieves the default footer text.
	 */
	public function default_footer_text() {

		$country = get_noptin_option( 'country', 'United States' );
		$company = get_noptin_option( 'company', get_option( 'blogname' ) );
		$address = get_noptin_option( 'address', '31 North San Juan Ave.' );
		$city    = get_noptin_option( 'city', 'Santa Clara' );
		$state   = get_noptin_option( 'state', 'San Francisco' );
		$powered = sprintf(
			__( 'Newsletter powered by %s', 'newsletter-optin-box' ),
			'[[noptin]]'
		);
		return trim( "$address \n\n$city, $state, $country \n\n$company" );

	}

	/**
	 * Returns the footer text.
	 */
	public function get_footer_text( $data ) {
		$footer_text = get_noptin_option( 'footer_text', $this->default_footer_text() );
		return apply_filters( 'noptin_mailer_email_footer_text', $footer_text, $data, $this );
	}

	/**
	 * Returns the default permission text.
	 */
	public function default_permission_text() {

		$permission_text  = __(
			'You received this email because you are subscribed to our email newsletter.',
			'newsletter-optin-box'
		);

		$permission_text2 = sprintf(
			/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
			__( 'To stop receiving these emails, you can %1$sunsubscribe%2$s at any time.', 'newsletter-optin-box' ),
			'<a href="[[unsubscribe_url]]" rel="nofollow" target="_blank">',
			'</a>'
		);

		return $permission_text . ' ' . $permission_text2;

	}

	/**
	 * Returns the permission text.
	 */
	public function get_permission_text( $data = array() ) {
		$permission_text = get_noptin_option( 'permission_text', $this->default_permission_text() );
		return apply_filters( 'noptin_mailer_email_permission_text', $permission_text, $data, $this );
	}

	/**
	 * Merges a string with the specified merge tags
	 */
	public function merge( $content, $tags = array() ) {

		$tags = wp_parse_args( $this->get_default_merge_tags(), $tags );

		if ( ! empty( $tags['email'] ) ) {
			$tags['avatar_url'] = get_avatar_url( $tags['email'] );
		}

		// Replace all available tags with their values.
		return add_noptin_merge_tags( $content, $tags, false, $this->strip_tags );
	}

	/**
	 * Makes campaign links trackable.
	 *
	 * @param string $content The email content.
	 * @param array  $data The new campaign data.
	 */
	public function make_links_trackable( $content, $data ) {

		$track_campaign_stats = get_noptin_option( 'track_campaign_stats', true );
		if ( empty( $track_campaign_stats ) || empty( $data['campaign_id'] ) ) {
			return $content;
		}

		if ( empty( $data['subscriber_id'] ) && empty( $data['user_id'] ) ) {
			return $content;
		}

		$url = get_noptin_action_url( 'email_click' );
		$url = add_query_arg(
			array(
				'uid'         => isset( $data['user_id'] ) ? intval( $data['user_id'] ) : false,
				'sid'         => isset( $data['subscriber_id'] ) ? intval( $data['subscriber_id'] ) : false,
				'cid'         => intval( $data['campaign_id'] ),
				//'noptin_hide' => 'true'
			),
			$url
		);

		$_content = preg_replace_callback(
			'/<a(.*?)href=["\'](.*?)["\'](.*?)>/mi',
			function ( $matches ) use ( $url ) {
				$_url = add_query_arg(
					'to',
					urlencode( $matches[2] ),
					$url
				);
				$pre  = $matches[1];
				$post = $matches[3];
				return "<a $pre href='$_url' $post >";
			},
			$content
		);

		if ( empty( $_content ) ) {
			return $content;
		}
		return $_content;

	}

	/**
	 * Inlines CSS into the email to make it compatible with more clients.
	 *
	 * @param string $content The email content.
	 */
	public function inline_css( $content ) {

		// Check if this is PHP 5.6
		if ( version_compare( phpversion(), '5.6', '<' ) ) {
			return $content;
		}

		// Maybe abort early;
		if ( ! class_exists( 'Pelago\Emogrifier\CssInliner' ) || ! $this->inline_css ) {
			return $content;
		}

		try {

			$emogrifier = Pelago\Emogrifier\CssInliner::fromHtml( $content );
			return $emogrifier->inlineCss()->render();

		} catch ( Exception $e ) {

			log_noptin_message( $e->getMessage() );
			return $content;

		}

	}

	/**
	 * Post processes an email message.
	 */
	public function post_process( $content, $data ) {

		// Parse merge tags.
		$content = $this->merge( $content, $data['merge_tags'] );

		// Make links clickable.
		$content = make_clickable( $content );

		// Ensure that shortcodes are not wrapped in paragraphs.
		$content = shortcode_unautop( $content );

		// Execute shortcodes.
		$content = do_shortcode( $content );

		// Balance tags.
		$content = force_balance_tags( $content );

		// Make links trackable.
		$content = $this->make_links_trackable( $content, $data );

		if ( 'empty' === $data['template'] ) {
			return $content;
		}

		// Finally, inline the CSS.
		$content = $this->inline_css( $content );

		// inline css adds a body tag, which doesn't play nice if we are using a template plugin.
		if ( ! $this->disable_template_plugins ) {
			$matches = array();
			preg_match( "/<body[^>]*>(.*?)<\/body>/is", $content, $matches );

			if ( isset( $matches[1] ) ) {
				$content = trim( $matches[1] );
			}

		}

		// Filters a post processed email.
		return apply_filters( 'noptin_post_processed_mailer_email_content', $content, $data, $this );
	}

	/**
	 * Given merge tags, this method builds an email.
	 *
	 * @since 1.2.8
	 *
	 * @param array $data The email data.
	 *
	 * @return string
	 */
	public function build_email( $data ) {

		// Filters email data before the email is generated.
		$data = apply_filters( 'noptin_mailer_email_data', $data, $this );

		// If no template is provided, use the user set template.
		if ( empty( $data['template'] ) ) {
			$data['template'] = $this->get_template( $data );
		}

		$template = $data['template'];

		// Whether or not we should disable template plugins.
		$this->disable_template_plugins = 'empty' !== $template && 'default' !== $template;

		// If we are using an empty template, return the content as is.
		if ( 'empty' === $template ) {
			return $this->post_process( $data['email_body'], $data );
		}

		ob_start();

		// If this is a full path to the template...
		if ( file_exists( $template ) ) {
			include $template;
			return $this->post_process( ob_get_clean(), $data );
		}

		// We are using a template stored in the templates directory.
		$sections = array(
			'header',
			'preview-text',
			'logo',
			'body',
			'footer'
		);

		foreach ( apply_filters( 'noptin_mailer_email_sections', $sections, $data, $template, $this ) as $section ) {

			$section = sanitize_key( $section );

			// Fires before the section is printed.
			do_action( "noptin_mailer_before_{$section}_section", $data, $this );

			// Load the section.
			get_noptin_template( "email-templates/$template/$section.php", $data );

			// Fires after the section is printed.
			do_action( "noptin_mailer_after_{$section}_section", $data, $this );

		}

		$email_content = ob_get_clean();

		// Filters email content before it is pre-processed.
		$email_content = apply_filters( 'noptin_mailer_pre_processed_email_content', $email_content, $data, $this );

		if ( empty( $email_content ) ) {
			$email_content = $data['email_body'];
		}

		return $this->post_process( $email_content, $data );
	}

	/**
	 * The email template that we'll use to send our emails.
	 *
	 * @since 1.2.8
	 *
	 */
	public function get_template( $data ) {
		$template = get_noptin_option( 'email_template',  'plain' );
		$template = apply_filters( 'noptin_mailer_email_template', $template, $data, $this );

		if ( empty( $template ) ) {
			$template = 'plain';
		}

		return $template;
	}

	/**
	 * Retrieves email headers.
	 */
	public function get_headers() {

		$name       = $this->get_from_name();
		$reply_to   = $this->get_reply_to();
		$content    = $this->get_content_type();
		$headers    = array();

		if ( ! empty( $reply_to ) && ! empty( $name ) ) {
			$headers = array( "Reply-To:$name <$reply_to>" );
		}

		$headers[]  = "Content-Type:$content";

		if ( ! empty( $this->mailer_data['merge_tags']['unsubscribe_url'] ) ) {
			$url       = esc_url( $this->mailer_data['merge_tags']['unsubscribe_url'] );
			$headers[] = "List-Unsubscribe:<$url>";
		}

		$headers = implode( "\r\n", $headers );
		return apply_filters( 'noptin_mailer_email_headers',  $headers, $this );

	}

	/**
	 * The default emails from address.
	 * 
	 * Defaults to noptin@$sitename
	 * Some hosts will block outgoing mail from this address if it doesn't exist,
	 * but there's no easy alternative. Defaulting to admin_email might appear to be
	 * another option, but some hosts may refuse to relay mail from an unknown domain.
	 *
	 * @since 1.2.8
	 */
	public function default_from_address() {

		// Get the site domain and get rid of www.
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		$from_email = 'noptin@' . $sitename;

		return apply_filters( 'noptin_mailer_default_from_address', $from_email, $this );

	}

	/**
	 * Get the email reply-to.
	 *
	 * @since 1.2.8
	 *
	 * @return string The email reply-to address.
	 */
	public function get_reply_to() {

		$reply_to = get_noptin_option( 'reply_to',  get_option( 'admin_email' ) );

		if ( ! is_email( $reply_to ) && ! empty( $reply_to ) ) {
			$reply_to =  get_option( 'admin_email' );
		}

		return apply_filters( 'noptin_mailer_email_reply_to', $reply_to, $this );
	}

	/**
	 * Get the email from address.
	 *
	 * @since 1.2.8
	 *
	 * @return string The email from address address.
	 */
	public function get_from_address( $email = '' ) {

		$from_address = get_noptin_option( 'from_email' );

		if ( is_email( $from_address ) ) {
			$email =  $from_address;
		}

		return apply_filters( 'noptin_mailer_email_from_address', $email, $this );

	}

	/**
	 * Get the email from name.
	 *
	 * @since 1.2.8
	 *
	 * @return string The email from name
	 */
	public function get_from_name() {
		$from_name = get_noptin_option( 'from_name',  get_option( 'blogname' ) );

		if ( empty( $from_name ) ) {
			$from_name =  get_bloginfo( 'name' );
		}

		return apply_filters( 'noptin_mailer_email_from_name', esc_html( $from_name ), $this );
	}

	/**
	 * Get the email content type.
	 *
	 * @since 1.2.8
	 *
	 * @return string The email content type.
	 */
	public function get_content_type() {
		return apply_filters( 'noptin_mailer_email_content_type', 'text/html', $this );
	}

	/**
	 * Ensures that our email messages are not messed up by template plugins.
	 *
	 * @since 1.3.0
	 *
	 * @return array wp_mail_data.
	 */
	public function ensure_email_content( $args ) {

		if ( $this->disable_template_plugins ) {
			$args['message'] = $this->wp_mail_data['email'];
		}

		return $args;
	}

	/**
	 * Add filters/actions before the email is sent.
	 *
	 * @since 1.2.8
	 */
	public function before_sending() {

		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ), 1000 );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ), 1000 );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ), 1000 );
		add_filter( 'wp_mail', array( $this, 'ensure_email_content' ), 1000000 );

	}

	/**
	 * Remove filters/actions after the email is sent.
	 *
	 * @since 1.2.8
	 */
	public function after_sending() {

		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ), 1000 );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ), 1000 );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ), 1000 );
		remove_filter( 'wp_mail', array( $this, 'ensure_email_content' ), 1000000 );

	}

	/**
	 * Sends an email immeadiately
	 */
	public function send( $to, $subject, $email ) {

		// Don't send if email address is invalid.
		if ( ! is_email( $to ) ) {
			return false;
		}

		// Hooks before an email is sent.
		do_action( 'before_noptin_sends_email', $to, $subject, $email, $this );

		/*
		 * Allow to filter data on per-email basis.
		 */
		$data = apply_filters(
			'noptin_mailer_email_data',
			array(
				'to'          => $to,
				'subject'     => $subject,
				'email'       => $email,
				'headers'     => $this->get_headers(),
				'attachments' => array(),
			),
			$this,
			$this->mailer_data
		);

		$data               = wp_unslash( $data );
		$this->wp_mail_data = $data;

		// Attach our own hooks.
		$this->before_sending();

		// Prepare the sending function.
		$sending_function = apply_filters( 'noptin_mailer_email_sending_function', 'wp_mail', $this );

		// Send the actual email.
		$result = call_user_func(
			$sending_function,
			$data['to'],
			html_entity_decode( $data['subject'], ENT_QUOTES, get_bloginfo( 'charset' ) ),
			$data['email'],
			$data['headers'],
			$data['attachments']
		);

		// If the email was not sent, log the error.
		if ( empty( $result ) ) {
			log_noptin_message(
				sprintf(
					/* Translators: %1$s Email address, %2$s Email subject. */
					__( 'Failed sending an email to %1$s with the subject %2$s', 'newsletter-optin-box' ),
					sanitize_email( $data['to'] ),
					wp_specialchars_decode ( $data['subject'] )
				)
			);
		}

		// Remove our hooks.
		$this->after_sending();

		// Hooks after an email is sent.
		do_action( 'after_noptin_sends_email', $to, $subject, $email, $this, $result );

		$this->wp_mail_data = null;

		return $result;
	}

	/**
	 * Schedules an email to send in the background.
	 */
	public function background_send( $to, $subject, $email ) {
		$data = compact( 'to', 'subject', 'email' );
		$key  = 'noptin_' . md5( uniqid() . time() . wp_rand( 0, 1000 ) );
		set_transient( $key, $data );
		do_noptin_background_action( 'send_bg_noptin_email', $key );
	}

	/**
	 * Sends a background email.
	 */
	public function _handle_background_send( $key ) {
		$data = get_transient( $key );
		delete_transient( $key );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return;
		}

		$this->send( $data['to'], $data['subject'], $data['email'] );
	}

}
