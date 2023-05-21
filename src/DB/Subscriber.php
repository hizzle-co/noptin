<?php

namespace Hizzle\Noptin\DB;

/**
 * Container for a single subscriber.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Subscriber.
 */
class Subscriber extends \Hizzle\Store\Record {

	/**
	 * Magic method to get/set custom fields.
	 */
	public function __call( $name, $args ) {
		if ( 0 === strpos( $name, 'get_cf_' ) ) {
			$prop = substr( $name, 4 );
			return $this->get_prop( $prop );
		} elseif ( 0 === strpos( $name, 'set_cf_' ) ) {
			$prop  = substr( $name, 4 );
			$value = apply_filters( 'noptin_sanitize_subscriber_custom_field_value', $args[0], $prop, $this );
			$this->set_prop( $prop, $value );
		}

		throw new \Exception( 'Call to undefined method ' . __CLASS__ . '::' . $name . '()' );
	}

	/**
	 * Returns the deprecated subscriber object.
	 *
	 * @return \Noptin_Subscriber
	 */
	public function get_deprecated_subscriber() {
		return new \Noptin_Subscriber( $this->get_id() );
	}

	/**
	 * Returns the first name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_first_name( $context = 'view' ) {
		return $this->get_prop( 'first_name', $context );
	}

	/**
	 * Sets the first name.
	 *
	 * @param string $value First name.
	 */
	public function set_first_name( $value ) {
		$this->set_prop( 'first_name', noptin_limit_length( sanitize_text_field( $value ), 100 ) );
	}

	/**
	 * Returns the last name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_last_name( $context = 'view' ) {
		return $this->get_prop( 'last_name', $context );
	}

	/**
	 * Sets the last name.
	 *
	 * @param string $value Last name.
	 */
	public function set_last_name( $value ) {
		$this->set_prop( 'last_name', noptin_limit_length( sanitize_text_field( $value ), 100 ) );
	}

	/**
	 * Returns the email address.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_email( $context = 'view' ) {
		return $this->get_prop( 'email', $context );
	}

	/**
	 * Sets the email address.
	 *
	 * @param string $value Email address.
	 */
	public function set_email( $value ) {
		$this->set_prop( 'email', noptin_limit_length( sanitize_email( $value ), 255 ) );
	}

	/**
	 * Returns the status.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Sets the status.
	 *
	 * @param string $value Status.
	 */
	public function set_status( $value ) {
		if ( array_key_exists( $value, noptin_get_subscriber_statuses() ) ) {
			$this->set_prop( 'status', $value );
		}
	}

	/**
	 * Gets the subscriber source.
	 *
	 * @return string
	 */
	public function get_source( $context = 'view' ) {
		return $this->get_prop( 'source', $context );
	}

	/**
	 * Sets the subscriber source.
	 *
	 * @param string $value Source.
	 */
	public function set_source( $value ) {
		$source = is_null( $value ) ? null : noptin_limit_length( sanitize_text_field( $value ), 100 );
		$this->set_prop( 'source', $source );
	}

	/**
	 * Gets the subscriber ip address.
	 *
	 * @return string
	 */
	public function get_ip_address( $context = 'view' ) {
		return $this->get_prop( 'ip_address', $context );
	}

	/**
	 * Sets the subscriber ip address.
	 *
	 * @param string $value IP address.
	 */
	public function set_ip_address( $value ) {
		$ip_address = is_null( $value ) ? null : noptin_limit_length( sanitize_text_field( $value ), 46 );
		$this->set_prop( 'ip_address', $ip_address );
	}

	/**
	 * Gets the subscriber conversion page.
	 *
	 * @return string
	 */
	public function get_conversion_page( $context = 'view' ) {
		return $this->get_prop( 'conversion_page', $context );
	}

	/**
	 * Sets the subscriber conversion page.
	 *
	 * @param string $value Conversion page.
	 */
	public function set_conversion_page( $value ) {
		$conversion_page = is_null( $value ) ? null : noptin_limit_length( esc_url_raw( $value ), 255 );
		$this->set_prop( 'conversion_page', $conversion_page );
	}

	/**
	 * Gets the subscriber confirmed status.
	 *
	 * @return bool
	 */
	public function get_confirmed( $context = 'view' ) {
		return $this->get_prop( 'confirmed', $context );
	}

	/**
	 * Sets the subscriber confirmed status.
	 *
	 * @param bool $value Confirmed status.
	 */
	public function set_confirmed( $value ) {
		$this->set_prop( 'confirmed', (bool) $value );
	}

	/**
	 * Gets the subscriber's confirmation key.
	 *
	 * @return string
	 */
	public function get_confirm_key( $context = 'view' ) {
		$confirm_key = $this->get_prop( 'confirm_key', $context );

		if ( empty( $confirm_key ) ) {
			$confirm_key = md5( wp_generate_password( 32, false ) . uniqid() );
			$this->set_confirm_key( $confirm_key );
		}

		return $confirm_key;
	}

	/**
	 * Sets the subscriber's confirmation key.
	 *
	 * @param string $value Confirmation key.
	 */
	public function set_confirm_key( $value ) {
		$confirm_key = empty( $value ) ? md5( wp_generate_password( 32, false ) . uniqid() ) : noptin_limit_length( sanitize_text_field( $value ), 32 );
		$this->set_prop( 'confirm_key', $confirm_key );
	}

	/**
	 * Get the subscriber's creation date.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \Hizzle\Store\Date_Time|null
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Set the subscriber's creation date.
	 *
	 * @param \Hizzle\Store\Date_Time|string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Get the subscriber's modified date.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \Hizzle\Store\Date_Time|null
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Set the subscriber's modified date.
	 *
	 * @param \Hizzle\Store\Date_Time|string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}
}
