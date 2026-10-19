<?php

namespace Hizzle\Noptin\Integrations\PMPro;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a PMPro membership level.
 *
 * @since 3.0.0
 */
class Membership_Level extends \Hizzle\Noptin\Objects\Record {

	/**
	 * @var \stdClass The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {

		if ( is_scalar( $external ) ) {
			$external = pmpro_getLevel( $external );
		}

		$this->external = $external;
	}

	/**
	 * Checks if the post exists.
	 * @return bool
	 */
	public function exists() {
		return ! is_object( $this->external ) || empty( $this->external->id ) ? false : true;
	}

	/**
	 * Retrieves a given field's value.
	 *
	 * @param string $field The field.
	 * @param array  $args  The arguments.
	 * @return mixed $value The value.
	 */
	public function get( $field, $args = array() ) {

		if ( ! $this->exists() ) {
			return null;
		}

		// Meta.
		if ( 'meta' === $field ) {
			if ( ! empty( $args['key'] ) ) {
				return get_pmpro_membership_level_meta( $this->external->id, $args['key'], true );
			}

			return '';
		}

		return $this->external->{$field};
	}

	/**
	 * Formats a given field's value.
	 *
	 * @param mixed $raw_value The raw value.
	 * @param array $args The args.
	 * @return mixed $value The formatted value.
	 */
	public function format( $raw_value, $args ) {

		// Format prices.
		if ( 'price' === $args['format'] ) {
			return pmpro_formatPrice( $raw_value );
		}

		return parent::format( $raw_value, $args );
	}
}
