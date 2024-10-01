<?php

/**
 * Container for a single email.
 *
 * @since   2.3.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Container for a single email.
 */
class Email {

	/** @var bool Whether this email is a legacy email. */
	public $is_legacy = false;

	/** @var bool The current admin screen. */
	public $admin_screen = 'email-editor';

	/** @var int Unique identifier for the email. */
	public $id = 0;

	/** @var int Unique identifier for the parent email. */
	public $parent_id = 0;

	/**
	 * @var string|null Schedule date for the email.
	 */
	public $created = null;

	/**
	 * @var string The campaign status.
	 */
	public $status = 'draft';

	/**
	 * @var string The campaign type.
	 */
	public $type = 'newsletter';

	/**
	 * @var string The campaign name.
	 */
	public $name = '';

	/**
	 * @var string The campaign subject.
	 */
	public $subject = '';

	/**
	 * @var string The campaign content.
	 */
	public $content = '';

	/**
	 * @var int The ID for the author of the email.
	 */
	public $author;

	/**
	 * @var int The menu order for the email.
	 */
	public $menu_order = 0;

	/**
	 * @var array Extra email meta.
	 */
	public $options = array();

	/**
	 * Class constructor.
	 *
	 * @param int|string|array $args
	 */
	public function __construct( $args ) {

		// Creating a new campaign.
		if ( empty( $args ) ) {
			return;
		}

		if ( $args instanceof Email ) {
			foreach ( get_object_vars( $args ) as $key => $value ) {
				if ( property_exists( $this, $key ) ) {
					$this->$key = $value;
				}
			}
		}

		// Loading a saved campaign.
		if ( is_numeric( $args ) || $args instanceof \WP_Post ) {
			$this->init( $args );
		}

		// Data array.
		if ( is_array( $args ) ) {
			$this->init_args( $args );
		}
	}

	/**
	 * Inits a given email by ID.
	 *
	 * @return bool
	 */
	private function init( $id ) {
		$post = get_post( $id );

		// Abort if the post does not exist.
		if ( empty( $post ) || ! in_array( $post->post_type, array( 'noptin-campaign' ), true ) ) {
			$this->id = 0;
			return false;
		}

		// Fetch campaign data. See: https://core.trac.wordpress.org/ticket/60314.
		$data        = get_post_meta( $post->ID, 'campaign_data' );
		$data        = wp_is_numeric_array( $data ) ? $data[0] : $data;
		$resave      = false;
		$is_revision = wp_is_post_revision( $post->ID );

		// If this is a revision and no data is found, try to fetch the parent data.
		if ( $is_revision && empty( $data ) ) {
			$parent = wp_get_post_parent_id( $post->ID );

			if ( $parent ) {
				$data = get_post_meta( $parent, 'campaign_data' );
				$data = wp_is_numeric_array( $data ) ? $data[0] : $data;
			}
		}

		// If data is stdClass, convert it to an array.
		if ( is_object( $data ) ) {
			$data = (array) $data;
		}

		// Check if we're dealing with a legacy campaign.
		if ( ! is_array( $data ) ) {
			$all_meta = get_post_meta( $post->ID );

			foreach ( $all_meta as $key => $value ) {
				if ( 'noptin_sends_after' === $key ) {
					$key = 'sends_after';
				}

				if ( 'noptin_sends_after_unit' === $key ) {
					$key = 'sends_after_unit';
				}

				if ( in_array( $key, array( 'campaign_type', '_edit_lock', 'automation_type', 'campaign_data' ), true ) ) {
					continue;
				}

				$this->options[ $key ] = $value[0];
			}

			$this->options['email_type']     = 'normal';
			$this->options['content_normal'] = $post->post_content;

			$resave = true;
		} else {
			$this->options = $data;
		}

		$this->id         = $post->ID;
		$this->parent_id  = $post->post_parent;
		$this->status     = $post->post_status;
		$this->name       = $post->post_title;
		$this->created    = $post->post_date;
		$this->content    = $post->post_content;
		$this->type       = get_post_meta( $post->ID, 'campaign_type', true );
		$this->author     = $post->post_author;
		$this->menu_order = $post->menu_order;

		// Backwards compatibility.
		// CSS.
		if ( ! isset( $this->options['custom_css'] ) ) {
			if ( 'normal' !== $this->get_email_type() ) {
				$this->options = array_merge(
					get_noptin_email_template_settings( 'noptin-visual', $this ),
					$this->options
				);
			} else {
				$this->options = array_merge(
					get_noptin_email_template_settings( $this->get_template(), $this ),
					$this->options
				);
			}

			$resave = true;
		}

		// Subject.
		$resave_title = false;
		if ( ! isset( $this->options['subject'] ) ) {
			if ( ! empty( $this->options['custom_title'] ) ) {
				$this->name   = $this->options['custom_title'];
				$resave_title = true;

				unset( $this->options['custom_title'] );
			}

			$this->options['subject'] = $post->post_title;
			$resave                   = true;
		}

		if ( $is_revision ) {
			$resave       = false;
			$resave_title = false;
		}

		// Ensure we have a sender.
		if ( ! isset( $this->options['email_sender'] ) ) {
			$this->options['email_sender'] = $this->get_sender();
			$resave                        = true;
		}

		if ( $resave && 'auto-draft' !== $post->post_status ) {
			// https://core.trac.wordpress.org/ticket/60314.
			update_post_meta( $post->ID, 'campaign_data', (object) $this->options );
		}

		$this->subject = $this->options['subject'];

		$has_kses = false !== has_filter( 'content_save_pre', 'wp_filter_post_kses' );

		if ( $has_kses ) {
			// Prevent KSES from corrupting blocks in post_content.
			kses_remove_filters();
		}

		// Check if content contains blocks.
		if ( $resave && ! has_blocks( $this->content ) ) {
			$this->content = noptin_email_wrap_blocks(
				empty( $this->content ) ? '' : sprintf(
					'<!-- wp:html -->%s<!-- /wp:html -->',
					wpautop( $this->content )
				),
				$this->get( 'footer_text' ),
				$this->get( 'heading' )
			);

			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_title'   => $this->name,
					'post_content' => $this->content,
				)
			);
		} elseif ( $resave_title ) {
			wp_update_post(
				array(
					'ID'         => $post->ID,
					'post_title' => $this->name,
				)
			);
		}

		if ( $has_kses ) {
			kses_init_filters();
		}

		// Add sub-type to options array.
		$key                   = $this->type . '_type';
		$this->options[ $key ] = get_post_meta( $post->ID, $key, true );

		// If CRON is not working properly, we need to check if the email is past the scheduled
		// date and update its status.
		if ( 'future' === $this->status && time() > strtotime( $post->post_date_gmt ) ) {
			$this->status = 'publish';
			wp_publish_post( $this->id );
		}

		// Fire action.
		do_action( 'noptin_init_email', $this, $post );

		return true;
	}

	/**
	 * Inits an email with the provided args.
	 *
	 * @return bool
	 */
	private function init_args( $args ) {

		// If we have an id, init the email.
		if ( ! empty( $args['id'] ) ) {
			$this->init( $args['id'] );
			unset( $args['id'] );
		}

		// Reset values.
		foreach ( $args as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
				unset( $args[ $key ] );
			}
		}

		// Prepare created date.
		if ( ! empty( $this->created ) ) {
			$this->created = gmdate( 'Y-m-d H:i:s', strtotime( $this->created ) );
		}

		// Merge the remaining args into the options array.
		$this->options = array_merge( $this->options, $args );
	}

	/**
	 * Fetches an emaail.
	 *
	 * @return Email
	 */
	public static function from( $id ) {

		if ( $id instanceof Email ) {
			return $id;
		}

		return new Email( $id );
	}

	/**
	 * Checks if the email exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return ! empty( $this->id );
	}

	/**
	 * Magic getter
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * Retrieves a given setting
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get( $key, $call_methods = false ) {

		if ( 'id' === strtolower( $key ) ) {
			return $this->id;
		}

		// Fetch value.
		if ( $call_methods && is_callable( array( $this, "get_$key" ) ) ) {
			return $this->{"get_$key"}();
		}

		if ( isset( $this->$key ) ) {
			$value = $this->$key;
		} else {
			$value = isset( $this->options[ $key ] ) ? $this->options[ $key ] : '';
		}

		// General filter.
		$value = apply_filters( 'noptin_get_email_prop', $value, $key, $this );

		// Prop specific filtter.
		return apply_filters( "noptin_get_email_prop_$key", $value, $this );
	}

	/**
	 * Returns the sub type for this email.
	 *
	 * @return string
	 */
	public function get_sub_type() {
		$sub_type = $this->get( $this->type . '_type' );

		if ( ! empty( $sub_type ) ) {
			$sub_type = apply_filters( 'noptin_' . $this->type . '_email_sub_type_' . $sub_type, $sub_type, $this );
		}

		return $sub_type;
	}

	/**
	 * Returns the sub types for this email type.
	 *
	 * @return string
	 */
	public function get_sub_types() {
		return get_noptin_campaign_sub_types( $this->type );
	}

	/**
	 * Checks if the email is published.
	 *
	 * @return bool
	 */
	public function is_published() {

		$is_published = 'publish' === $this->status;
		return apply_filters( 'noptin_email_is_published', $is_published, $this->status, $this );
	}

	/**
	 * Checks if the email can send.
	 *
	 * @return bool|\WP_Error
	 */
	public function can_send( $return_wp_error = false ) {
		$can_send = $this->check_can_send();

		if ( ! $return_wp_error && is_wp_error( $can_send ) ) {
			return false;
		}

		return $can_send;
	}

	/**
	 * Checks if the email can send.
	 *
	 * @return bool|\WP_Error
	 */
	private function check_can_send() {

		if ( ! $this->is_published() || ! $this->exists() ) {
			return new \WP_Error( 'noptin_email_cannot_send', 'The email cannot be sent since it is not published.' );
		}

		// Check if the campaign is already sent.
		if ( '' !== get_post_meta( $this->id, 'completed', true ) ) {
			return new \WP_Error( 'noptin_email_cannot_send', __( 'The email has already been sent.', 'newsletter-optin-box' ) );
		}

		// Check if the campaign is paused.
		if ( '' !== get_post_meta( $this->id, 'paused', true ) ) {
			return new \WP_Error( 'noptin_email_cannot_send', __( 'The email is paused and cannot be sent.', 'newsletter-optin-box' ) );
		}

		// Make sure the sender is valid.
		if ( $this->supports( 'supports_recipients' ) && $this->is_mass_mail() ) {
			$sender = $this->get_sender();

			if ( empty( $sender ) || ! array_key_exists( $sender, get_noptin_email_senders() ) ) {
				return new \WP_Error( 'noptin_email_invalid_sender', __( 'The sender is not supported.', 'newsletter-optin-box' ) );
			}
		}

		// Make sure we have an email body and subject.
		if ( empty( $this->subject ) ) {
			return new \WP_Error( 'noptin_email_no_subject', __( 'You need to provide a subject for your email.', 'newsletter-optin-box' ) );
		}

		$content = $this->get_content( $this->get_email_type() );

		if ( empty( $content ) ) {
			return new \WP_Error( 'missing_content', __( 'The email body cannot be empty.', 'newsletter-optin-box' ) );
		}

		return apply_filters( 'noptin_email_can_send', true, $this );
	}

	/**
	 * Sends the email.
	 *
	 * @return bool|\WP_Error
	 */
	public function send() {
		$GLOBALS['noptin_email_force_skip'] = null;

		// Abort if we can't send the email.
		$can_send = $this->can_send( true );

		if ( is_wp_error( $can_send ) ) {
			return $can_send;
		}

		if ( ! $can_send ) {
			return new \WP_Error( 'noptin_email_cannot_send', __( 'The email cannot be sent.', 'newsletter-optin-box' ) );
		}

		// If this is a mass mail and not a newsletter email,
		// ... create a newsletter email and send it.
		if ( $this->is_mass_mail() && 'newsletter' !== $this->type ) {
			do_action( 'noptin_before_send_email', $this, Main::$current_email_recipient );

			// Prepare campaign args.
			$type   = $this->get_email_type();
			$suffix = empty( $GLOBALS['noptin_current_title_tag'] ) ? date_i18n( get_option( 'date_format' ) ) : noptin_parse_email_subject_tags( $GLOBALS['noptin_current_title_tag'] );
			$args   = array_merge(
				$this->options,
				array(
					'parent_id'    => $this->id,
					'status'       => 'publish',
					'type'         => 'newsletter',
					'name'         => sprintf( '%1$s - %2$s', esc_html( $this->name ), esc_html( $suffix ) ),
					'subject'      => noptin_parse_email_subject_tags( $this->get_subject(), true ),
					'heading'      => noptin_parse_email_content_tags( $this->get( 'heading' ), true ),
					'content'      => 'visual' === $type ? noptin_parse_email_content_tags( \Noptin_Email_Generator::handle_item_lists_shortcode( $this->content ), true ) : '',
					'author'       => $this->author,
					'preview_text' => noptin_parse_email_content_tags( $this->get( 'preview_text' ), true ),
					'footer_text'  => noptin_parse_email_content_tags( $this->get( 'footer_text' ), true ),
				)
			);

			foreach ( array_keys( get_noptin_email_types() ) as $email_type ) {
				$key = 'content_' . $email_type;
				if ( $type !== $email_type || ! isset( $args[ $key ] ) ) {
					$args[ $key ] = '';
					continue;
				}

				$value = \Noptin_Email_Generator::handle_item_lists_shortcode( $args[ $key ] );

				// Parse paragraphs.
				if ( 'content_normal' === $type ) {
					$value = wpautop( trim( $value ) );
				}

				$args[ $key ] = trim( noptin_parse_email_content_tags( $value, true ) );

				// Strip HTML.
				if ( 'content_plain_text' === $key && ! empty( $args[ $key ] ) ) {
					$args[ $key ] = noptin_convert_html_to_text( $args[ $key ] );
				}
			}

			if ( ! empty( $GLOBALS['noptin_email_' . $this->id . '_extra_conditional_logic'] ) ) {
				$args['extra_conditional_logic'] = $GLOBALS['noptin_email_' . $this->id . '_extra_conditional_logic'];
			} else {
				unset( $args['extra_conditional_logic'] );
			}

			// Prepare the newsletter.
			$newsletter = new Email( $args );

			do_action( 'noptin_after_send_email', $newsletter, null );

			unset( $GLOBALS['current_noptin_email_suffix'] );

			// Check if the newsletter can be sent.
			if ( ! empty( $GLOBALS['noptin_email_force_skip'] ) ) {
				return new \WP_Error( 'noptin_email_skipped', $GLOBALS['noptin_email_force_skip']['message'] );
			}

			// Maybe skip sending the email.
			$should_send = apply_filters( 'noptin_email_should_send', true, $this );

			if ( true === $should_send ) {
				// Update the last send date.
				update_post_meta( $this->id, '_noptin_last_send', time() );

				$newsletter->save();
			}

			return $should_send;
		}

		// Send mass emails.
		if ( $this->is_mass_mail() ) {
			do_action( 'noptin_newsletter_campaign_published', $this );
			return true;
		}

		$recipients = noptin_prepare_email_recipients( $this->get_recipients() );

		// Abort if no recipients are found.
		if ( 1 > count( $recipients ) ) {
			return new \WP_Error( 'noptin_email_no_recipients', __( 'The email has no valid recipients.', 'newsletter-optin-box' ) );
		}

		// Send to each recipient.
		$result = true;
		foreach ( $recipients as $email => $track ) {
			$result = $this->send_to(
				array(
					'email' => $email,
					'track' => $track,
				),
				false
			);
		}

		return $result;
	}

	/**
	 * Sends the email to a single recipient.
	 *
	 * @param string|array $recipient
	 * @param bool $confirm_can_send
	 * @return bool|\WP_Error
	 */
	public function send_to( $recipient, $confirm_can_send = true ) {

		$result = $this->handle_send_to( $recipient, $confirm_can_send );

		if ( is_wp_error( $result ) && ! empty( Main::$current_email_recipient['email'] ) ) {
			log_noptin_message(
				sprintf(
					'Email not send to recipient "%s" for campaign "%s" because: Error: "%s"',
					Main::$current_email_recipient['email'],
					$this->name,
					$result->get_error_message()
				)
			);

			noptin_record_subscriber_activity(
				Main::$current_email_recipient['email'],
				sprintf(
					'Failed sending the campaign "%s". Error: "%s"',
					$this->name,
					$result->get_error_message()
				)
			);
		}

		return $result;
	}

	/**
	 * Sends the email to a single recipient.
	 *
	 * @param string|array $recipient
	 * @param bool $confirm_can_send
	 * @return bool|\WP_Error
	 */
	private function handle_send_to( $recipient, $confirm_can_send = true ) {

		Main::$current_email_recipient = array();

		$GLOBALS['noptin_email_force_skip'] = null;

		if ( $confirm_can_send ) {
			// Abort if we can't send the email.
			$can_send = $this->can_send( true );

			if ( is_wp_error( $can_send ) ) {
				return $can_send;
			}

			if ( ! $can_send ) {
				return new \WP_Error( 'noptin_email_cannot_send', __( 'The email cannot be sent.', 'newsletter-optin-box' ) );
			}
		}

		if ( is_string( $recipient ) ) {
			$recipient = array(
				'email' => $recipient,
				'track' => true,
			);
		}

		if ( ! is_array( $recipient ) || empty( $recipient['email'] ) ) {
			return new \WP_Error( 'noptin_email_invalid_recipient', __( 'Invalid recipient', 'newsletter-optin-box' ) );
		}

		if ( ! isset( $recipient['track'] ) ) {
			$recipient['track'] = true;
		}

		$recipient['cid'] = $this->id;

		// Prepare the email recipient.
		Main::init_current_email_recipient( $recipient, $this );

		do_action( 'noptin_before_send_email', $this, Main::$current_email_recipient );

		// Maybe parse recipient tags.
		if ( false !== strpos( Main::$current_email_recipient['email'], '[[' ) ) {
			Main::$current_email_recipient['email'] = noptin()->emails->tags->replace_in_text_field( Main::$current_email_recipient['email'] );
			$GLOBALS['current_noptin_email']        = Main::$current_email_recipient['email'];
		}

		// Check if the email is valid.
		if ( ! is_email( Main::$current_email_recipient['email'] ) ) {
			return new \WP_Error( 'noptin_email_invalid_recipient', __( 'Invalid recipient', 'newsletter-optin-box' ) );
		}

		// Check if the email is unsubscribed.
		$is_pending_email = 'noptin_subscriber_status_set_to_pending' === $this->get_trigger();
		if ( ! $is_pending_email && noptin_is_email_unsubscribed( Main::$current_email_recipient['email'] ) ) {
			return new \WP_Error( 'noptin_email_invalid_recipient', __( 'The email is unsubscribed', 'newsletter-optin-box' ) );
		}

		// Generate the subject and body.
		$subject = noptin_parse_email_subject_tags( $this->get_subject() );
		$message = noptin_generate_email_content( $this, Main::$current_email_recipient, ! empty( Main::$current_email_recipient['track'] ) );

		// Check if the newsletter can be sent.
		if ( ! empty( $GLOBALS['noptin_email_force_skip'] ) ) {
			return new \WP_Error( 'noptin_email_skipped', $GLOBALS['noptin_email_force_skip']['message'] );
		}

		// Maybe skip sending the email.
		$should_send = apply_filters( 'noptin_email_should_send', true, $this );
		if ( is_wp_error( $should_send ) || false === $should_send ) {
			if ( ! $should_send ) {
				return new \WP_Error( 'noptin_email_skipped', 'The email was skipped via a filter' );
			}

			return $should_send;
		}

		// Send the email.
		$result = noptin_send_email(
			array(
				'recipients'               => Main::$current_email_recipient['email'],
				'subject'                  => $subject,
				'message'                  => $message,
				'campaign_id'              => $this->id,
				'campaign'                 => $this,
				'headers'                  => array(),
				'attachments'              => $this->get_attachments(),
				'reply_to'                 => noptin_parse_email_subject_tags( $this->get( 'reply_to' ) ),
				'from_email'               => noptin_parse_email_subject_tags( $this->get( 'from_email' ) ),
				'from_name'                => noptin_parse_email_subject_tags( $this->get( 'from_name' ) ),
				'content_type'             => $this->get_email_type() === 'plain_text' ? 'text' : 'html',
				'disable_template_plugins' => ! ( $this->get_email_type() === 'normal' && $this->get_template() === 'default' ),
			)
		);

		do_action( 'noptin_after_send_email', $this, $result );
		Main::$current_email_recipient = array();

		return $result;
	}

	/**
	 * Checks if this is an automation rule email.
	 *
	 * @return bool
	 */
	public function is_automation_rule() {
		return 'automation' === $this->type && 0 === strpos( $this->get_sub_type(), 'automation_rule_' );
	}

	/**
	 * Returns the trigger.
	 *
	 * @return bool|string
	 */
	public function get_trigger() {
		return $this->is_automation_rule() ? substr( $this->get_sub_type(), 16 ) : false;
	}

	/**
	 * Returns the contexts for this email.
	 *
	 * @return string[]
	 */
	public function get_contexts() {
		$type = Main::get_email_type( $this->type );

		if ( empty( $type ) ) {
			return array();
		}

		if ( ! $type->supports_sub_types ) {
			return $type->contexts;
		}

		$sub_type = $type->get_sub_type( $this->get_sub_type() );

		if ( $sub_type && isset( $sub_type['contexts'] ) ) {
			return $sub_type['contexts'];
		}

		return $type->contexts;
	}

	/**
	 * Returns the attachments for this email.
	 *
	 * @return string[]
	 */
	public function get_attachments() {
		$attachments = $this->get( 'attachments' );
		$attachments = ! is_array( $attachments ) ? array() : array_filter( $attachments );

		if ( ! noptin_has_active_license_key() || empty( $attachments ) ) {
			return array();
		}

		$prepared = array();
		foreach ( $attachments as $attachment ) {
			$attachment = $this->parse_attachment_file_path( trim( $attachment ) );

			// Add if its not a remote file.
			if ( ! $attachment['remote_file'] ) {
				$prepared[] = $attachment['file_path'];
			}
		}

		return $prepared;
	}

	/**
	 * Parse file path/url and see if its remote or local.
	 *
	 * @param string $file_url
	 * @return array
	 */
	private function parse_attachment_file_path( $file_url ) {
		$wp_uploads     = wp_upload_dir();
		$wp_uploads_dir = $wp_uploads['basedir'];
		$wp_uploads_url = $wp_uploads['baseurl'];

		// Prevent path traversal.
		$file_url = str_replace( '../', '', $file_url );

		/**
		 * Replace uploads dir, site url etc with absolute counterparts if we can.
		 * Note the str_replace on site_url is on purpose, so if https is forced
		 * via filters we can still do the string replacement on a HTTP file.
		 */
		$replacements = array(
			$wp_uploads_url                  => $wp_uploads_dir,
			network_site_url( '/', 'https' ) => ABSPATH,
			str_replace( 'https:', 'http:', network_site_url( '/', 'http' ) ) => ABSPATH,
			site_url( '/', 'https' )         => ABSPATH,
			str_replace( 'https:', 'http:', site_url( '/', 'http' ) ) => ABSPATH,
		);

		$count            = 0;
		$file_path        = str_replace( array_keys( $replacements ), array_values( $replacements ), $file_url, $count );
		$parsed_file_path = wp_parse_url( $file_path );
		$remote_file      = null === $count || 0 === $count; // Remote file only if there were no replacements.

		// Paths that begin with '//' are always remote URLs.
		if ( '//' === substr( $file_path, 0, 2 ) ) {
			$file_path = ( is_ssl() ? 'https:' : 'http:' ) . $file_path;

			/**
			 * Filter the remote filepath for download.
			 *
			 * @since 1.0.0
			 * @param string $file_path File path.
			 */
			return array(
				'remote_file' => true,
				'file_path'   => $file_path,
			);
		}

		// See if path needs an abspath prepended to work.
		if ( file_exists( ABSPATH . $file_path ) ) {
			$remote_file = false;
			$file_path   = ABSPATH . $file_path;
		} elseif ( '/wp-content' === substr( $file_path, 0, 11 ) ) {
			$remote_file = false;
			$file_path   = realpath( WP_CONTENT_DIR . substr( $file_path, 11 ) );

			// Check if we have an absolute path.
		} elseif ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], array( 'http', 'https', 'ftp' ), true ) ) && isset( $parsed_file_path['path'] ) ) {
			$remote_file = false;
			$file_path   = $parsed_file_path['path'];
		}

		return array(
			'remote_file' => $remote_file,
			'file_path'   => $file_path,
		);
	}

	/**
	 * Checks if this email supports timing.
	 *
	 * @return bool
	 */
	public function supports_timing() {
		return apply_filters( 'noptin_email_supports_timing', $this->supports( 'supports_timing' ), $this );
	}

	/**
	 * Returns the recipient ids for mass mail that are manually sent to selected recipients.
	 *
	 * @return array
	 */
	public function get_manual_recipients_ids() {
		$ids = $this->get( 'manual_recipients_ids' );
		return empty( $ids ) ? array() : array_unique( noptin_parse_int_list( $ids ) );
	}

	/**
	 * Returns the recipients for this email.
	 *
	 * @return string
	 */
	public function get_recipients() {

		// Prepare recipient.
		$recipient = $this->get( 'recipients' );

		return empty( $recipient ) ? '' : $recipient;
	}

	/**
	 * Returns the placeholder for email recipients.
	 *
	 */
	public function get_placeholder_recipient() {
		$sub_type = $this->get_sub_type();
		$emails   = apply_filters( "noptin_default_{$this->type}_email_{$sub_type}_recipient", '', $this );
		$emails   = trim( $emails . ', ' . get_option( 'admin_email' ) . ' --notracking' );
		$emails   = trim( $emails, ',' );

		if ( empty( $emails ) ) {
			return '';
		}

		// translators: %s: Placeholder for email recipients.
		return sprintf( __( 'For example, %s', 'newsletter-optin-box' ), $emails );
	}

	/**
	 * Returns the sender for this email.
	 *
	 * @return bool
	 */
	public function get_sender() {

		$sender  = $this->get( 'email_sender' );
		$default = 'newsletter' === $this->type ? 'noptin' : 'manual_recipients';
		$sender  = ! empty( $sender ) ? $sender : $default;
		return apply_filters( 'noptin_email_sender', $sender, $this );
	}

	/**
	 * Returns the email type for this email.
	 *
	 * @return bool
	 */
	public function get_email_type() {
		$email_type  = $this->get( 'email_type' );
		$email_types = array_keys( get_noptin_email_types() );
		$email_type  = in_array( $email_type, $email_types, true ) ? $email_type : get_default_noptin_email_type();

		if ( 'visual' === $email_type && ! noptin_has_active_license_key() ) {
			$email_type = 'normal';
		}

		return $email_type;
	}

	/**
	 * Returns the email template for this email.
	 *
	 * @return bool
	 */
	public function get_template() {

		$template = $this->get( 'template' );

		// Read from settings.
		if ( empty( $template ) ) {
			$template = get_noptin_option( 'email_template', 'paste' );
		}

		// Default to the paste template.
		if ( empty( $template ) ) {
			$template = 'paste';
		}

		// Filter and return.
		return apply_filters( 'noptin_email_template', $template, $this );
	}

	/**
	 * Returns the subject for this email.
	 *
	 * @return string
	 */
	public function get_subject() {
		return $this->subject;
	}

	/**
	 * Returns the content for this email.
	 *
	 * @return string
	 */
	public function get_content( $email_type = 'normal' ) {

		if ( 'visual' === $email_type ) {
			return $this->content;
		}

		if ( isset( $this->options[ 'content_' . $email_type ] ) ) {
			return $this->options[ 'content_' . $email_type ];
		}

		return '';
	}

	/**
	 * Checks whether the campaign sends immediately.
	 *
	 * @return bool
	 */
	public function sends_immediately() {

		if ( ! $this->supports_timing() || 'immediately' === $this->get( 'when_to_run' ) ) {
			return true;
		}

		return 1 > $this->get_sends_after();
	}

	/**
	 * Returns the delay interval for this automated email.
	 *
	 * @return int
	 */
	public function get_sends_after() {
		return (int) $this->get( 'sends_after' );
	}

	/**
	 * Returns the delay unit for this automated email.
	 *
	 * @param bool $label
	 * @return string
	 */
	public function get_sends_after_unit( $label = false ) {

		$units = get_noptin_email_delay_units( $label && 1 === $this->get_sends_after() );
		$unit  = $this->get( 'sends_after_unit' );

		if ( empty( $unit ) || ! isset( $units[ $unit ] ) ) {
			$unit = 'hours';
		}

		return $label ? $units[ $unit ] : $unit;
	}

	/**
	 * Returns the js data for this email.
	 *
	 * return array
	 */
	public function get_js_data() {
		$manual_recipients = array();
		$email_sender      = $this->get_sender();

		foreach ( $this->get_manual_recipients_ids() as $recipient_id ) {
			$recipient = get_noptin_email_recipient( $recipient_id, $email_sender );

			if ( empty( $recipient ) ) {
				continue;
			}

			$recipient['id']     = $recipient_id;
			$recipient['avatar'] = get_avatar_url( $recipient['email'], array( 'size' => 32 ) );
			$manual_recipients[] = $recipient;
		}

		$data = array(
			'is_automation_rule'    => $this->is_automation_rule(),
			'trigger'               => $this->get_trigger(),
			'supports_timing'       => $this->supports_timing(),
			'supports_recipients'   => $this->supports( 'supports_recipients' ),
			'placeholder_recipient' => $this->get_placeholder_recipient(),
			'email_type'            => Main::get_email_type( $this->type ),
			'edit_url'              => $this->get_edit_url(),
			'manual_recipients'     => $manual_recipients,
			'extra_settings'        => (object) apply_filters(
				'noptin_email_extra_settings',
				apply_filters(
					"noptin_{$this->type}_email_extra_settings",
					apply_filters(
						"noptin_{$this->type}_{$this->get_sub_type()}_email_extra_settings",
						array(),
						$this
					),
					$this
				),
				$this
			),
			'merge_tags'            => (object) noptin_prepare_merge_tags_for_js( $this->get_merge_tags() ),
		);

		return apply_filters( 'noptin_email_js_data', $data, $this );
	}

	/**
	 * Returns the email's merge tags.
	 *
	 * @return array
	 */
	public function get_merge_tags() {
		static $tags = array();

		$key = $this->type . '_' . $this->get_sub_type();
		if ( isset( $tags[ $key ] ) ) {
			return $tags[ $key ];
		}

		$tags[ $key ] = apply_filters(
			'noptin_email_merge_tags',
			apply_filters(
				"noptin_{$this->type}_merge_tags",
				apply_filters(
					"noptin_{$this->type}_{$this->get_sub_type()}_merge_tags",
					noptin()->emails->tags->tags,
					$this
				),
				$this
			),
			$this
		);

		return $tags[ $key ];
	}

	/**
	 * Returns the properties as an array.
	 *
	 * @return array
	 */
	public function to_array() {
		$data = get_object_vars( $this );
		return array_merge(
			$data,
			array(
				'options' => array_merge(
					$this->options,
					array(
						'subject' => $this->subject,
					)
				),
			)
		);
	}

	/**
	 * Returns the campaign overview URL.
	 *
	 * @since 2.3.0
	 * @return string.
	 */
	public function get_base_url() {
		$type = Main::get_email_type( $this->type );
		$base = add_query_arg( 'page', 'noptin-email-campaigns', admin_url( '/admin.php' ) );

		if ( empty( $type ) ) {
			return $base;
		}

		// If the type has a parent, add it to the URL.
		if ( ! empty( $type->parent_type ) && ! empty( $this->parent_id ) ) {
			$base = add_query_arg( 'noptin_parent_id', $this->parent_id, $base );
		}

		return add_query_arg( 'noptin_email_type', $type->type, $base );
	}

	/**
	 * Returns a link to edit the campaign.
	 *
	 * @since 1.7.0
	 * @return string.
	 */
	public function get_edit_url() {

		$type = Main::get_email_type( $this->type );

		if ( $type && $type->child_type ) {
			return add_query_arg(
				array(
					'page'              => 'noptin-email-campaigns',
					'noptin_email_type' => rawurlencode( $type->child_type ),
					'noptin_parent_id'  => $this->id,
				),
				admin_url( '/admin.php' )
			);
		}

		$param = array(
			'noptin_campaign' => $this->id,
		);
		return add_query_arg( $param, $this->get_base_url() );
	}

	/**
	 * Returns a link to preview the campaign.
	 *
	 * @since 1.7.0
	 * @return string.
	 */
	public function get_preview_url() {
		$type = Main::get_email_type( $this->type );

		if ( $type && $type->child_type ) {
			return '';
		}

		return get_preview_post_link( $this->id );
	}

	/**
	 * Returns a link to view the campaign in browser.
	 *
	 * @since 1.7.0
	 * @return string.
	 */
	public function get_view_in_browser_url( $recipient = null ) {

		$recipient        = ! is_array( $recipient ) ? Main::$current_email_recipient : $recipient;
		$recipient['cid'] = $this->id;

		return get_noptin_action_url(
			'view_in_browser',
			noptin_encrypt(
				wp_json_encode(
					array_filter( $recipient )
				)
			),
			true
		);
	}

	/**
	 * Prepares the preview content if needed.
	 *
	 * @param string $mode Either 'browser' or 'preview'.
	 * @param array $recipient The recipient meta info.
	 * @return true|\WP_Error
	 */
	public function prepare_preview( $mode, $recipient ) {

		$recipient['cid']  = $this->id;
		$recipient['mode'] = $mode;

		Main::init_current_email_recipient( $recipient, $this );
		$recipient = Main::$current_email_recipient;

		try {
			do_action( 'noptin_before_send_email', $this, Main::$current_email_recipient );
			do_action( "noptin_prepare_email_{$mode}", $this );
			do_action( "noptin_prepare_{$this->type}_email_{$mode}", $this );
			do_action( "noptin_prepare_{$this->type}_{$this->get_sub_type()}_email_{$mode}", $this );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'exception', $e->getMessage() );
		}

		return true;
	}

	/**
	 * Returns a link to duplicate the campaign.
	 *
	 * @since 1.7.0
	 * @return string.
	 */
	public function get_duplication_url() {
		return $this->get_action_url( 'duplicate_campaign' );
	}

	/**
	 * Duplicates the campaign.
	 *
	 * @param array $override An array of properties to override.
	 * @return Email|\WP_Error|false The duplicated campaign or false on failure.
	 */
	public function duplicate( $override = array() ) {
		$args = $this->to_array();

		// Remove id.
		unset( $args['id'] );

		$duplicate = new Email( array_merge( $args, $override ) );

		if ( isset( $duplicate->options['automation_rule'] ) ) {
			unset( $duplicate->options['automation_rule'] );
		}

		$result = $duplicate->save();

		// Check if the duplicate exists.
		if ( ! $duplicate->exists() ) {
			return $result;
		}

		// Duplicate any children.
		if ( $this->supports( 'child_type' ) ) {
			foreach ( $this->get_children() as $child ) {
				$child->duplicate( array( 'parent_id' => $duplicate->id ) );
			}
		}

		return $duplicate;
	}

	/**
	 * Fetches the children of this email.
	 *
	 * @return Email[]
	 */
	public function get_children() {

		// Abort if id is not set.
		if ( ! $this->exists() ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'   => 'noptin-campaign',
				'post_parent' => $this->id,
				'numberposts' => -1,
			)
		);

		$emails = array();

		foreach ( $posts as $post ) {
			$emails[] = new Email( $post->ID );
		}

		return $emails;
	}

	/**
	 * Returns an action URL for this email.
	 *
	 * @param string $action The action to perform.
	 * @return string
	 */
	public function get_action_url( $action ) {
		$param = array(
			'noptin_email_action' => $action,
			'noptin_campaign'     => $this->id,
		);
		return wp_nonce_url( add_query_arg( $param, $this->get_base_url() ), 'noptin_email_action', 'noptin_email_action_nonce' );
	}

	/**
	 * Returns a link to delete the campaign.
	 *
	 * @since 1.7.0
	 * @return string.
	 */
	public function get_delete_url() {
		return $this->get_action_url( 'delete_campaign' );
	}

	/**
	 * Deletes the campaign.
	 *
	 * @return Email|\WP_Error|false The deleted campaign or false on failure.
	 */
	public function delete() {

		// Delete any children.
		foreach ( $this->get_children() as $child ) {
			$child->delete();
		}

		do_action( 'noptin_' . $this->type . '_campaign_before_delete', $this );

		// Fire another hook for the sub type.
		do_action( 'noptin_' . $this->get_sub_type() . '_campaign_before_delete', $this->id );

		// Delete the campaign.
		wp_delete_post( $this->id, true );
	}

	/**
	 * Trash the campaign.
	 *
	 * @return Email|null|false The trashed campaign or false on failure.
	 */
	public function trash() {

		$result = wp_trash_post( $this->id );

		if ( empty( $result ) ) {
			return false;
		}

		$this->status = 'trash';
		return $this;
	}

	/**
	 * Restores the campaign.
	 *
	 * @return Email|null|false The restored campaign or false on failure.
	 */
	public function restore() {

		$result = wp_untrash_post( $this->id );

		if ( empty( $result ) ) {
			return false;
		}

		$this->status = $result->post_status;
		return $this;
	}

	/**
	 * Saves the email.
	 */
	public function save() {

		// Prepare post args.
		$args = array(
			'post_type'    => 'noptin-campaign',
			'post_parent'  => $this->parent_id,
			'post_title'   => empty( $this->name ) ? $this->subject : $this->name,
			'post_status'  => $this->status,
			'post_author'  => $this->author,
			'post_content' => $this->content,
			'meta_input'   => array(
				'campaign_type' => $this->type,
				// https://core.trac.wordpress.org/ticket/60314.
				'campaign_data' => (object) array_merge(
					$this->options,
					array(
						'subject' => $this->subject,
					)
				),
			),
		);

		// Store subtype in a separate meta key.
		$args['meta_input'][ $this->type . '_type' ] = $this->get_sub_type();

		// Slash data.
		// WP expects all data to be slashed and will unslash it (fixes '\' character issues).
		$args = wp_slash( $args );

		// Only remove taggeted link rel if it was hooked.
		$has_filter = false !== has_filter( 'content_save_pre', 'wp_targeted_link_rel' );

		if ( $has_filter ) {
			wp_remove_targeted_link_rel_filters();
		}

		$has_kses = false !== has_filter( 'content_save_pre', 'wp_filter_post_kses' );

		if ( $has_kses ) {
			// Prevent KSES from corrupting blocks in post_content.
			kses_remove_filters();
		}

		// Create or update the email.
		if ( $this->exists() ) {
			$args['ID'] = $this->id;
			$result     = wp_update_post( $args, true );
		} else {
			$result = wp_insert_post( $args, true );
		}

		if ( $has_filter ) {
			wp_init_targeted_link_rel_filters();
		}

		if ( $has_kses ) {
			kses_init_filters();
		}

		// Schedule the email.
		if ( is_int( $result ) && $result ) {
			$this->init( $result );

			if ( 'future' === $this->status ) {
				wp_clear_scheduled_hook( 'publish_future_post', array( $this->id ) );
				wp_schedule_single_event( strtotime( get_gmt_from_date( $this->created ) . ' GMT' ), 'publish_future_post', array( $this->id ) );
			}
			return true;
		}

		return $result;
	}

	/**
	 * Checks if this is a mass mail.
	 *
	 * @return bool
	 */
	public function is_mass_mail() {
		return $this->supports( 'supports_recipients' ) && 'manual_recipients' !== $this->get_sender();
	}

	/**
	 * Checks if a certain feature is supported for this email.
	 *
	 * @return bool
	 */
	public function supports( $feature ) {

		$type = Main::get_email_type( $this->type );

		if ( ! $type ) {
			return false;
		}

		$sub_type = $type->get_sub_type( $this->get_sub_type() );

		if ( $sub_type && isset( $sub_type[ $feature ] ) ) {
			return $sub_type[ $feature ];
		}

		return isset( $type->{$feature} ) ? $type->{$feature} : false;
	}

	/**
	 * Checks if the current user can edit this email.
	 *
	 * @return bool
	 */
	public function current_user_can_edit() {

		// Return true if not yet saved.
		if ( ! $this->exists() ) {
			return Main::current_user_can_create_new_campaign();
		}

		return current_user_can( 'edit_post', $this->id );
	}

	/**
	 * Checks if the current user can delete this email.
	 *
	 * @return bool
	 */
	public function current_user_can_delete() {

		// Return true if not yet saved.
		if ( ! $this->exists() ) {
			return false;
		}

		return current_user_can( 'delete_post', $this->id );
	}

	/**
	 * Number of times the email has been sent.
	 *
	 * @return int
	 */
	public function get_send_count() {
		$total = (int) get_post_meta( $this->id, '_noptin_sends', true ) + (int) get_post_meta( $this->id, '_noptin_fails', true );
		return apply_filters( 'noptin_email_recipients', $total, $this );
	}

	/**
	 * Number of times the email has been opened.
	 *
	 * @return int
	 */
	public function get_open_count() {
		$count = (int) get_post_meta( $this->id, '_noptin_opens', true );
		return apply_filters( 'noptin_email_opens', $count, $this );
	}

	/**
	 * Number of times the email has been clicked.
	 *
	 * @return int
	 */
	public function get_click_count() {
		$count = (int) get_post_meta( $this->id, '_noptin_clicks', true );
		return apply_filters( 'noptin_email_clicks', $count, $this );
	}

	/**
	 * Returns the total number of unsubscribes.
	 *
	 * @return int
	 */
	public function get_unsubscribe_count() {
		$count = (int) get_post_meta( $this->id, '_noptin_unsubscribed', true );
		return apply_filters( 'noptin_email_unsubscribes', $count, $this );
	}
}
