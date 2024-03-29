<?php

namespace Hizzle\Noptin\Objects;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a WordPress user.
 *
 * @since 3.0.0
 */
class User extends Person {

	/**
	 * @var \WP_User The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {
		if ( is_numeric( $external ) ) {
			$external = get_userdata( $external );
		}

		$this->external = $external;
	}

	/**
	 * Checks if the customer exists.
	 * @return bool
	 */
	public function exists() {
		if ( ! is_a( $this->external, 'WP_User' ) ) {
			return false;
		}

		return $this->external->exists();
	}

	/**
	 * Retrieves a given field's value.
	 *
	 * @param string $field The field.
	 * @return mixed $value The value.
	 */
	public function get( $field, $args = array() ) {

		if ( 'logged_in' === $field ) {
			return $this->exists();
		}

		if ( ! $this->exists() ) {
			return null;
		}

		// ID.
		if ( 'id' === strtolower( $field ) ) {
			return $this->external->ID;
		}

		// Locale.
		if ( 'locale' === strtolower( $field ) ) {
			return get_user_locale( $this->external );
		}

		// Role.
		if ( 'role' === strtolower( $field ) ) {
			return current( $this->external->roles );
		}

		// Custom fields that start with user_cf_.
		if ( strpos( $field, 'user_cf_' ) === 0 ) {
			$meta_key = str_replace( 'user_cf_', '', $field );
			$value    = get_user_meta( $this->external->ID, $meta_key, true );
			return apply_filters( 'noptin_users_known_custom_field_value', $value, $meta_key, $this->external );
		}

		// Meta.
		if ( 'meta' === $field ) {
			$field = isset( $args['key'] ) ? $args['key'] : null;
		}

		// Abort if no field.
		if ( empty( $field ) ) {
			return null;
		}

		// Related collections.
		if ( strpos( $field, '.' ) ) {
			return $this->get_provided( $field, $args );
		}

		// Short circuit.
		$value = apply_filters( 'noptin_wp_user_field_value', null, $field, $this->external );

		if ( ! is_null( $value ) ) {
			return $value;
		}

		// Try with user_ prefix.
		if ( $this->external->has_prop( 'user_' . $field ) ) {
			return $this->external->get( 'user_' . $field );
		}

		return $this->external->get( $field );
	}
}
