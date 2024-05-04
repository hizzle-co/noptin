<?php

namespace Hizzle\Noptin\Subscribers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Container for a Noptin subscriber.
 *
 * @since 3.0.0
 */
class Record extends \Hizzle\Noptin\Objects\Person {

	/**
	 * @var \Hizzle\Noptin\DB\Subscriber The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {
		$this->external = noptin_get_subscriber( $external );
	}

	/**
	 * Checks if the subscriber exists.
	 * @return bool
	 */
	public function exists() {
		return $this->external->exists();
	}

	/**
	 * Retrieves a given field's value.
	 *
	 * @param string $field The field.
	 * @return mixed $value The value.
	 */
	public function get( $field, $args = array() ) {

		if ( ! $this->exists() ) {
			return null;
		}

		// ID.
		if ( 'id' === strtolower( $field ) ) {
			return $this->external->get_id();
		}

		// Meta.
		if ( 'meta' === $field ) {
			if ( empty( $args['key'] ) ) {
				return null;
			}

			return noptin()->db()->get_record_meta( $this->external->get_id(), trim( $args['key'] ), true );
		}

		// Related collections.
		if ( strpos( $field, '.' ) ) {
			return $this->get_provided( $field, $args );
		}

		return $this->external->get( $field );
	}
}
