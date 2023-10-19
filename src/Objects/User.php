<?php

namespace Hizzle\Noptin\Objects;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a WordPress user.
 *
 * @since 2.2.0
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
	public function get( $field ) {

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

		// Name.
		if ( 'name' === strtolower( $field ) ) {
			return $this->external->display_name;
		}

		// BIO.
		if ( 'bio' === strtolower( $field ) ) {
			return $this->external->description;
		}

		// Locale.
		if ( 'locale' === strtolower( $field ) ) {
			return get_user_locale( $this->external );
		}

		// Role.
		if ( 'role' === strtolower( $field ) ) {
			return current( $this->external->roles );
		}

		// Meta.
		if ( 0 === strpos( $field, 'meta.' ) ) {
			$field = substr( $field, 5 );
		}

		// Try with user_ prefix.
		if ( $this->external->has_prop( 'user_' . $field ) ) {
			return $this->external->get( 'user_' . $field );
		}

		return $this->external->get( $field );

	}
}
