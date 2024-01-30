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
	 * @var \Record[] Cached records.
	 */
	private static $related = array();

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

		// Related collections.
		if ( strpos( $field, '.' ) ) {
			$collection     = strtok( $field, '.' );
			$without_prefix = str_replace( $collection . '.', '', $field );

			if ( empty( self::$related[ $collection ] ) ) {
				self::$related[ $collection ] = array();
			} elseif ( ! empty( self::$related[ $collection ][ $this->external->ID ] ) ) {
				return self::$related[ $collection ][ $this->external->ID ]->get( $without_prefix );
			} elseif ( ! empty( self::$related[ $collection ][ $this->external->user_email ] ) ) {
				return self::$related[ $collection ][ $this->external->user_email ]->get( $without_prefix );
			}

			/** @var People $collection */
			$collection = Store::get( $collection );

			if ( $collection && 'person' === $collection->object_type ) {
				$record = $collection->get_from_user( $this->external );

				if ( $record->exists() ) {
					self::$related[ $collection->object_type ][ $this->external->ID ]         = $record;
					self::$related[ $collection->object_type ][ $this->external->user_email ] = $record;

					return $record->get( $without_prefix );
				}

				$record = $collection->get_from_email( $this->external->user_email );

				if ( $record->exists() ) {
					self::$related[ $collection->object_type ][ $this->external->ID ]         = $record;
					self::$related[ $collection->object_type ][ $this->external->user_email ] = $record;

					return $record->get( $without_prefix );
				}

				return null;
			}
		}

		// Try with user_ prefix.
		if ( $this->external->has_prop( 'user_' . $field ) ) {
			return $this->external->get( 'user_' . $field );
		}

		return $this->external->get( $field );
	}
}
