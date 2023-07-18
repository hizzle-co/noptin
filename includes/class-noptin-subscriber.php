<?php
/**
 * Subscriber API: Noptin_Subscriber class
 *
 * @since 1.2.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main class used to implement a single subscriber's object.
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $status
 * @property bool   $confirmed
 * @property string $confirm_key
 * @property string $date_created
 * @see get_noptin_subscriber
 * @since 1.2.7
 */
class Noptin_Subscriber {

	/**
	 * The subscriber's id.
	 *
	 * @since 1.2.7
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Whether or not the subscriber is virtual.
	 *
	 * Use with caution.
	 * @since 1.2.7
	 * @var bool
	 */
	public $is_virtual = false;

	/**
	 * Subscriber data container.
	 *
	 * @since 1.2.7
	 * @var object
	 */
	protected $data;

	/**
	 * The Noptin_Subscriber class Constructor.
	 *
	 * Fetches the subscriber's data and passes it to Noptin_Subscriber::init().
	 *
	 * @deprecated 2.0.0
	 * @since 1.2.7
	 * @see get_noptin_subscriber
	 * @param int|string|array|stdClass|Noptin_Subscriber $subscriber The subscribers's ID, email, confirm key, a Noptin_Subscriber object,
	 *                                                                or a subscriber object from the DB.
	 */
	public function __construct( $subscriber = 0 ) {

		// Show deprecated class notice.
		_deprecated_function( __CLASS__, '2.0.0', 'noptin_get_subscriber' );

		// $subscriber can be...
		// ... an instance of this class...
		if ( $subscriber instanceof Noptin_Subscriber ) {
			$this->init( $subscriber->to_array() );
			$this->is_virtual = $subscriber->is_virtual;
			return;
		}

		// ... a row from the database...
		if ( is_object( $subscriber ) || is_array( $subscriber ) ) {
			$this->init( $subscriber );
			return;
		}

		if ( empty( $subscriber ) ) {
			$this->data = new stdClass();
			return;
		}

		// ... the subscriber's id...
		if ( is_numeric( $subscriber ) ) {

			$data = self::get_data_by( 'id', $subscriber );

			// ... the subscriber's email...
		} elseif ( is_email( $subscriber ) ) {

			$data = self::get_data_by( 'email', $subscriber );

			// ... or the subscriber's confirm key.
		} elseif ( is_string( $subscriber ) ) {

			$data = self::get_data_by( 'confirm_key', $subscriber );

		}

		if ( empty( $data ) ) {
			$this->data = new stdClass();
		} else {
			$this->init( $data );
		}

	}

	/**
	 * Sets up subscriber properties.
	 *
	 * @since  1.2.7
	 *
	 * @param object $data    Subscriber DB row object.
	 */
	public function init( $data ) {
		$this->data = (object) $data;
		$this->id   = isset( $this->data->id ) ? (int) $this->data->id : 0;
	}

	/**
	 * Returns only the main subscriber fields
	 *
	 * @since 1.2.7
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $field The field to query against: 'id', 'email' or 'confirm_key'.
	 * @param string|int $value The field value
	 * @return object|false Raw subscriber object
	 */
	public static function get_data_by( $field, $value ) {
		global $wpdb;

		$field = strtolower( trim( $field ) );

		if ( 'id' === $field ) {

			// Make sure the value is numeric to avoid casting objects, for example, to int 1.
			if ( ! is_numeric( $value ) ) {
				return false;
			}

			$value = intval( $value );
			if ( $value < 1 ) {
				return false;
			}
		} else {
			$value = trim( $value );
		}

		if ( ! $value ) {
			return false;
		}

		switch ( $field ) {
			case 'id':
				$subscriber_id = $value;
				$db_field      = 'id';
				break;
			case 'email':
				$subscriber_id = wp_cache_get( $value, 'noptin_subscriber_emails' );
				$db_field      = 'email';
				break;
			case 'confirm_key':
				$subscriber_id = wp_cache_get( $value, 'noptin_subscriber_keys' );
				$db_field      = 'confirm_key';
				break;
			default:
				return false;
		}

		// Try retrieving the subscriber from cache.
		if ( false !== $subscriber_id ) {
			$subscriber = wp_cache_get( $subscriber_id, 'noptin_subscribers' );
			if ( $subscriber ) {
				return $subscriber;
			}
		}

		// If that fails, retrieve from the db...
		$subscriber = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}noptin_subscribers WHERE $db_field = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$value
			)
		);

		if ( ! $subscriber ) {
			return false;
		}

		// ... then cache the results.
		wp_cache_set( $subscriber->id, $subscriber, 'noptin_subscribers' );
		wp_cache_set( $subscriber->confirm_key, $subscriber->id, 'noptin_subscriber_keys' );
		wp_cache_set( $subscriber->email, $subscriber->id, 'noptin_subscriber_emails' );

		return $subscriber;
	}

	/**
	 * Removes the subscriber from the cache.
	 *
	 * @since 1.2.8
	 */
	public function clear_cache() {
		wp_cache_delete( $this->email, 'noptin_subscriber_emails' );
		wp_cache_delete( $this->confirm_key, 'noptin_subscriber_keys' );
		wp_cache_delete( $this->id, 'noptin_subscribers' );
	}

	/**
	 * Magic method for checking the existence of a subscriber field.
	 *
	 * @since 1.2.7
	 *
	 * @param string $key Subscriber meta key to check if set.
	 * @return bool Whether the given subscriber meta key is set.
	 */
	public function __isset( $key ) {

		if ( isset( $this->data->$key ) ) {
			return true;
		}

		return ! $this->is_virtual && metadata_exists( 'noptin_subscriber', $this->id, $key );
	}

	/**
	 * Magic method for retrieving subscriber fields.
	 *
	 * @since 1.2.7
	 *
	 * @param string $key Subscriber field to retrieve.
	 * @return mixed Value of the given subscriber field (if set).
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * Magic method for setting custom subscriber fields.
	 *
	 * This method does not update custom fields in the database. It only stores
	 * the value on the Noptin_Subscriber instance.
	 *
	 * @since 1.2.7
	 *
	 * @param string $key   subscriber meta key.
	 * @param mixed  $value subscriber meta value.
	 */
	public function __set( $key, $value ) {
		if ( 'id' === strtolower( $key ) ) {
			$this->id       = $value;
			$this->data->id = $value;
			return;
		}

		$this->data->$key = $value;
	}

	/**
	 * Magic method for unsetting a certain custom field.
	 *
	 * @since 1.2.7
	 *
	 * @param string $key Subscriber meta key to unset.
	 */
	public function __unset( $key ) {

		if ( isset( $this->data->$key ) ) {
			unset( $this->data->$key );
		}

	}

	/**
	 * Determine whether the subscriber exists in the database.
	 *
	 * @since 1.2.7
	 *
	 * @return bool True if subscriber exists in the database, false if not.
	 */
	public function exists() {
		return $this->is_virtual || ! empty( $this->id );
	}

	/**
	 * Retrieve the value of a property or meta key.
	 *
	 * @since 1.2.7
	 *
	 * @param string $key Property
	 * @return mixed
	 */
	public function get( $key, $single = true ) {

		// Abort early if the subscriber does not exist.
		if ( ! $this->exists() ) {
			return null;
		}

		$value = null;

		if ( strtolower( $key ) === 'id' ) {
			$key = 'id';
		}

		if ( strtolower( $key ) === 'second_name' ) {
			$key = 'last_name';
		}

		if ( strtolower( $key ) === 'name' ) {
			return trim( $this->first_name . ' ' . $this->last_name );
		}

		if ( isset( $this->data->$key ) ) {
			$value = $this->data->$key;
		} elseif ( ! $this->is_virtual ) {
			$value = get_noptin_subscriber_meta( $this->id, $key, $single );
		}

		return apply_filters( "get_noptin_subscriber_field_$key", $value, $this, $single );

	}

	/**
	 * Determine whether a property or meta key is set
	 *
	 * @since 1.2.7
	 *
	 * @param string $key Property
	 * @return bool
	 */
	public function has_prop( $key ) {
		return $this->__isset( $key );
	}

	/**
	 * Return an array representation of the subscriber.
	 *
	 * @since 1.2.7
	 *
	 * @return array Array representation.
	 */
	public function to_array() {
		return get_object_vars( $this->data );
	}

	/**
	 * Sends a confirmation email to the subscriber.
	 *
	 * @since 1.2.7
	 *
	 */
	public function send_confirmation_email() {
		return send_new_noptin_subscriber_double_optin_email( $this->id );
	}

	/**
	 * Returns subscriber meta.
	 *
	 * @since 1.2.7
	 *
	 */
	public function get_meta() {
		return get_noptin_subscriber_meta( $this->id );
	}

	/**
	 * Checks if the current subscriber is active.
	 *
	 * @since 1.2.7
	 * @return bool.
	 */
	public function is_active() {
		return $this->is_virtual || 'subscribed' === $this->status;
	}

	/**
	 * Checks if the current subscriber is a WordPress user.
	 *
	 * @since 1.2.7
	 * @return bool.
	 */
	public function is_wp_user() {
		return false !== email_exists( $this->email );
	}

	/**
	 * Returns the associated WordPress user id.
	 *
	 * @since 1.2.7
	 * @return int|false The user's ID on success, and false on failure.
	 */
	public function get_wp_user() {
		return email_exists( $this->email );
	}

}
