<?php

namespace Hizzle\Noptin\Objects;

/**
 * Container for a single person.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Container for a single person.
 */
abstract class Person extends Record {

	/**
	 * @var Person[] Cached records.
	 */
	private static $related = array();

	/**
	 * Retrieves the person's email address.
	 *
	 */
	public function get_email() {
		return $this->get( 'email' );
	}

	/**
	 * Retrieves the person's name.
	 */
	public function get_name() {
		return $this->get( 'name' );
	}

	/**
	 * Retrieves the person's edit URL.
	 */
	public function get_edit_url() {
		return '';
	}

	/**
	 * Retrieves a related field's value.
	 *
	 * @param string $field The field.
	 * @param array $args The arguments.
	 * @return mixed $value The value.
	 */
	public function get_provided( $field, $args = array() ) {

		if ( ! $this->exists() ) {
			return null;
		}

		// Related collections.
		if ( strpos( $field, '.' ) ) {
			$collection     = strtok( $field, '.' );
			$without_prefix = str_replace( $collection . '.', '', $field );

			if ( empty( self::$related[ $collection ] ) ) {
				self::$related[ $collection ] = array();
			} elseif ( ! empty( self::$related[ $collection ][ $this->get_email() ] ) ) {
				return self::$related[ $collection ][ $this->get_email() ]->get( $without_prefix, $args );
			}

			/** @var People $collection */
			$collection = Store::get( $collection );

			if ( $collection && 'person' === $collection->object_type ) {
				$record = $collection->get_from_email( $this->get_email() );

				if ( $record->exists() ) {
					self::$related[ $collection->object_type ][ $this->get_email() ] = $record;

					return $record->get( $without_prefix, $args );
				}

				return '';
			}
		}

		return null;
	}
}
