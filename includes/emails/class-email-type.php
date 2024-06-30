<?php
/**
 * Emails API: Email Type.
 *
 * Container for a single email type.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Container for a single email type.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
abstract class Noptin_Email_Type {

	/**
	 * @var string
	 */
	public $type; // newsletter, woocommerce_new_order, etc.

	/**
	 * True when email is being sent.
	 *
	 * @var bool
	 */
	public $sending = false;

	/**
	 * @var string Current unsubscribe URL.
	 */
	public $unsubscribe_url = '';

	/**
	 * @var \Hizzle\Noptin\DB\Subscriber
	 */
	public $subscriber;

	/**
	 * @var WP_User
	 */
	public $user;

	/**
	 * @var array Current recipient.
	 */
	public $recipient = array(); // Array containing campaign id, user id and subscriber id.

	/**
	 * Custom mail configuration.
	 *
	 * @var array
	 */
	public $mail_config = array();

	/**
	 * Registers relevant hooks.
	 *
	 */
	public function add_hooks() {
		add_action( 'noptin_before_send_email', array( $this, 'before_send' ) );
		add_action( 'noptin_after_send_email', array( $this, 'after_send' ) );
		add_action( 'noptin_prepare_email_preview', array( $this, 'prepare_preview' ) );
		add_filter( 'noptin_get_email_prop', array( $this, 'maybe_set_default' ), 10, 3 );
		add_filter( 'noptin_get_default_email_props', array( $this, 'get_default_props' ), 10, 2 );
	}

	/**
	 * Returns default email properties.
	 *
	 * @param array $props
	 * @param \Hizzle\Noptin\Emails\Email $email
	 * @return array
	 */
	public function get_default_props( $props, $email ) {

		if ( $email->type !== $this->type && $email->get_sub_type() !== $this->type ) {
			return $props;
		}

		// Merge defaults set in mail_config.
		if ( ! empty( $this->mail_config['defaults'] ) ) {
			$props = array_merge( $props, $this->mail_config['defaults'] );
		}

		$methods = get_class_methods( $this );

		if ( empty( $methods ) ) {
			return $props;
		}

		// Find all methods that begin with default_.
		foreach ( $methods as $method ) {
			if ( 0 !== strpos( $method, 'default_' ) ) {
				continue;
			}

			$prop = str_replace( 'default_', '', $method );

			$props[ $prop ] = call_user_func( array( $this, $method ), $email );
		}

		return $props;
	}

	/**
	 * Returns the default content.
	 *
	 */
	public function default_content_normal() {

		$content_normal = '';
		if ( ! empty( $this->mail_config['defaults']['content_normal'] ) ) {
			$content_normal = $this->mail_config['defaults']['content_normal'];
		}

		/**
		 * Filters the default email body
		 *
		 * @param string $body The default email body
		 */
		return apply_filters( "noptin_default_{$this->type}_body", $content_normal );
	}

	/**
	 * Returns the default plain text content.
	 *
	 */
	public function default_content_plain_text() {
		return noptin_convert_html_to_text( $this->default_content_normal() );
	}

	/**
	 * Prepares the default blocks.
	 *
	 * @return string
	 */
	protected function prepare_default_blocks() {

		if ( ! empty( $this->mail_config['defaults']['blocks'] ) ) {
			return $this->mail_config['defaults']['blocks'];
		}

		$normal = $this->default_content_normal();

		if ( ! empty( $normal ) ) {
			$normal = wpautop( $normal );

			return sprintf(
				'<!-- wp:html -->%s<!-- /wp:html -->',
				$normal
			);
		}

		return '';
	}

	/**
	 * Returns the default content.
	 *
	 */
	public function default_content_visual() {

		$content = noptin_email_wrap_blocks( $this->prepare_default_blocks(), get_noptin_footer_text() );

		/**
		 * Filters the default email body
		 *
		 * @param string $body The default email body
		 */
		return apply_filters( "noptin_default_{$this->type}_body_visual", $content );
	}

	/**
	 * Sets the default value for a given email type's value.
	 *
	 * @param mixed $value
	 * @param string $prop
	 * @param \Hizzle\Noptin\Emails\Email $email
	 */
	public function maybe_set_default( $value, $prop, $email ) {

		// Abort if the email is saved or is not our type.
		if ( ! empty( $value ) || $email->exists() || $email->type !== $this->type ) {
			return $value;
		}

		// Is there a custom method to filter this prop?
		$method = sanitize_key( "default_$prop" );
		if ( is_callable( array( $this, $method ) ) ) {
			$value = $this->$method();
		}

		// Apply email type specific filter then return.
		return apply_filters( "noptin_{$this->type}_default_$prop", $value );
	}

	/**
	 * Retrieves an array of subscriber merge tags.
	 *
	 * @deprecated 3.1.0
	 * @return array
	 */
	public function get_subscriber_merge_tags() {
		_deprecated_function( __METHOD__, '3.1.0' );
		return array();
	}

	/**
	 * Retrieves an array of supported merge tags.
	 *
	 * @return array
	 */
	public function get_merge_tags() {
		return array();
	}

	/**
	 * Retrieves flattened merge tags.
	 *
	 * @return array
	 */
	public function get_flattened_merge_tags( $existing = array() ) {

		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$raw_tags   = $this->get_merge_tags();
		$merge_tags = array();
		foreach ( $raw_tags as $group => $_merge_tags ) {
			foreach ( $_merge_tags as $tag => $details ) {

				// Set missing categories.
				if ( empty( $details['group'] ) ) {
					$details['group'] = $group;
				}

				$merge_tags[ $tag ] = $details;
			}
		}

		return array_merge( $merge_tags, $existing );
	}

	/**
	 * Registers supported merge tags.
	 *
	 * @return array
	 */
	public function register_merge_tags() {

		// Register general merge tags.
		foreach ( $this->get_flattened_merge_tags() as $tag => $details ) {
			noptin()->emails->tags->add_tag( $tag, $details );
		}
	}

	/**
	 * Unregisters supported merge tags.
	 *
	 * @return array
	 */
	public function unregister_merge_tags() {

		// Unregister general merge tags.
		foreach ( $this->get_flattened_merge_tags() as $tag => $config ) {
			noptin()->emails->tags->remove_tag( $tag );
		}
	}

	/**
	 * Prepares an email preview.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $email The email being previewed.
	 */
	public function prepare_preview( $email ) {

		if ( $this->type !== $email->type && $this->type !== $email->get_sub_type() ) {
			return;
		}

		// Set-up test data for the preview.
		$this->prepare_test_data( $email );
	}

	/**
	 * Prepares the current recipient.
	 *
	 * This method exists for backward compatibility.
	 */
	private function prepare_current_recipient() {
		$this->recipient = \Hizzle\Noptin\Emails\Main::$current_email_recipient;

		// Set subscriber.
		$this->subscriber = null;
		if ( ! empty( $this->recipient['sid'] ) ) {
			$subscriber = noptin_get_subscriber( $this->recipient['sid'] );

			if ( $subscriber->exists() ) {
				$this->subscriber = $subscriber;
			}
		}

		// If we have an email.
		if ( ! empty( $this->recipient['email'] ) ) {
			$this->maybe_set_subscriber_and_user( $this->recipient['email'] );
		}
	}

	/**
	 * Fired before sending a campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 */
	public function before_send( $campaign ) {

		if ( $this->type !== $campaign->type && $this->type !== $campaign->get_sub_type() ) {
			return;
		}

		// Backward compatibility.
		$this->prepare_current_recipient();

		// Generate unsubscribe url.
		$this->unsubscribe_url = get_noptin_action_url( 'unsubscribe', noptin_encrypt( wp_json_encode( $this->recipient ) ) );

		// Register merge tags.
		$this->register_merge_tags();

		// Indicate that we're sending an email.
		$this->sending = true;
	}

	/**
	 * Sends a notification.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 * @param string $key
	 * @param array|string $recipients
	 */
	public function send( $campaign, $key, $recipients ) {
		$result = false;

		// Prepare environment.
		$this->before_send( $campaign );

		// Prepare recipients.
		if ( is_string( $recipients ) ) {
			$recipients = array( $recipients => true );
		}

		// Send to each recipient.
		foreach ( $recipients as $email => $track ) {

			$GLOBALS['current_noptin_email'] = $email;

			// Send the email.
			$result = noptin_send_email(
				array(
					'recipients'               => $email,
					'subject'                  => noptin_parse_email_subject_tags( $campaign->get_subject() ),
					'message'                  => noptin_generate_email_content( $campaign, $this->recipient, $track ),
					'campaign_id'              => ! empty( $campaign->id ) ? $campaign->id : 0,
					'campaign'                 => $campaign,
					'headers'                  => array(),
					'attachments'              => array(),
					'reply_to'                 => '',
					'from_email'               => '',
					'from_name'                => '',
					'content_type'             => $campaign->get_email_type() === 'plain_text' ? 'text' : 'html',
					'unsubscribe_url'          => $this->unsubscribe_url,
					'disable_template_plugins' => ! ( $campaign->get_email_type() === 'normal' && $campaign->get_template() === 'default' ),
				)
			);
		}

		// Clear environment.
		$this->after_send( $campaign );

		// Log.
		if ( 'test' !== $key && ! $campaign->is_mass_mail() && ! empty( $campaign->id ) ) {
			increment_noptin_campaign_stat( $campaign->id, '_noptin_sends' );
		}

		return $result;
	}

	/**
	 * Fired after sending a campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 */
	public function after_send( $campaign ) {

		if ( $this->type !== $campaign->type && $this->type !== $campaign->get_sub_type() ) {
			return;
		}

		// Revert recipient.
		$this->recipient = array();

		// Indicate that we're nolonger sending an email.
		$this->sending = false;

		// Uregister merge tags.
		$this->unregister_merge_tags();

		$this->subscriber      = null;
		$this->unsubscribe_url = '';
	}

	/**
	 * Prepares test data.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $email
	 */
	public function prepare_test_data( $email ) {

		// Set subscriber.
		if ( empty( $this->subscriber ) ) {
			$subscriber = get_current_noptin_subscriber_id();

			if ( $subscriber ) {
				$this->subscriber = noptin_get_subscriber( $subscriber );
			}
		}

		do_action( 'noptin_prepare_test_data', $this, $email );
	}

	/**
	 * Sets subscriber and user for the email.
	 *
	 * @param string $email
	 */
	protected function maybe_set_subscriber_and_user( $email ) {

		if ( ! is_string( $email ) ) {
			return;
		}

		$email = sanitize_email( $email );

		if ( empty( $email ) ) {
			return;
		}

		// Set subscriber.
		$subscriber = noptin_get_subscriber( $email );

		if ( empty( $this->subscriber ) && $subscriber->exists() ) {
			$this->subscriber = $subscriber;
		}
	}
}
