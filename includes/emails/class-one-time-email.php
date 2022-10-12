<?php
/**
 * Email API: One Time Email.
 *
 * Contains the main one time email class
 *
 * @since   1.7.8
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Represents a single one-time email.
 *
 * @since 1.7.8
 * @internal
 * @ignore
 */
class Noptin_One_Time_Email {

	/** @var int */
	public $id = 0;

	/** @var int */
	public $parent_id = 0;

	/** @var string */
	public $status = 'publish';

	/** @var string */
	public $subject = '';

	/** @var array */
	public $options = array();

	/** @var string */
	public $type = 'one_time';

	/** @var string */
	public $recepient = '';

	/**
	 * Class constructor.
	 *
	 * @param int|string|array $args
	 */
	public function __construct( $args ) {

		$this->subject   = $args['subject'];
		$this->recepient = $args['recepient'];

		unset( $args['recepient'], $args['subject'] );
		$this->options = $args;

	}

	/**
	 * Checks if the email exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return true;
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
	public function get( $key ) {

		// Fetch value.
		if ( isset( $this->$key ) ) {
			$value = $this->$key;
		} else {
			$value = isset( $this->options[ $key ] ) ? $this->options[ $key ] : '';
		}

		// General filter.
		$value = apply_filters( 'noptin_get_email_prop', $value, $key, $this );

		// Prop specific filtter.
		return apply_filters( "noptin_get_email_$key", $value, $this );

	}

	/**
	 * Checks if the email is published.
	 *
	 * @return bool
	 */
	public function is_published() {
		return 'publish' === $this->status;
	}

	/**
	 * Checks if the email is can send.
	 *
	 * @return bool
	 */
	public function can_send() {
		return $this->is_published() && $this->exists() && is_email( $this->recepient );
	}

	/**
	 * Checks if this is a mass mail.
	 *
	 * @return bool
	 */
	public function is_mass_mail() {
		return false;
	}

	/**
	 * Returns the sender for this email.
	 *
	 * @return bool
	 */
	public function get_sender() {
		return 'none';
	}

	/**
	 * Returns the email type.
	 *
	 * @return bool
	 */
	public function get_email_type() {
		$email_type = $this->get( 'email_type' );
		return in_array( $email_type, array_keys( get_noptin_email_types() ), true ) ? $email_type : 'normal';
	}

	/**
	 * Returns the email template for this email.
	 *
	 * @return bool
	 */
	public function get_template() {

		// Read from campaign options.
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
	public function get_content( $email_type = null ) {

		if ( empty( $email_type ) ) {
			$email_type = $this->get_email_type();
		}

		return $this->get( 'content_' . $email_type );
	}

	/**
	 * Returns the WP_User object for this email.
	 *
	 * @return WP_User|false
	 */
	public function get_user() {
		return get_user_by( 'email', $this->recepient );
	}

	/**
	 * Returns the Noptin_Subscriber object for this email.
	 *
	 * @return Noptin_Subscriber|false
	 */
	public function get_subscriber() {
		$subscriber = get_noptin_subscriber_by_email( $this->recepient );
		return $subscriber->exists() ? $subscriber : false;
	}

	/**
	 * Retrieves the custom merge tags for this email.
	 *
	 * @return array
	 */
	public function get_merge_tags() {
		return isset( $this->options['merge_tags'] ) ? $this->options['merge_tags'] : array();
	}

	/**
	 * Retrieves a unique key for this email.
	 *
	 * @return string
	 */
	public function get_key() {
		return isset( $this->options['key'] ) ? $this->options['key'] : 'none';
	}

}
