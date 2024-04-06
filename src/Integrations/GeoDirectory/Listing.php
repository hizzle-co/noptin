<?php

namespace Hizzle\Noptin\Integrations\GeoDirectory;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a listing.
 *
 * @since 2.2.0
 */
class Listing extends \Hizzle\Noptin\Objects\Generic_Post {

	/**
	 * @var \stdClass The external object.
	 */
	public $gd_post;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {
		if ( is_numeric( $external ) ) {
			$external = get_post( $external );
		}

		$this->external = $external;
		$this->gd_post  = parent::exists() ? geodir_get_post_info( $this->external->ID ) : null;
	}

	/**
	 * Checks if the post exists.
	 * @return bool
	 */
	public function exists() {
		return ! empty( $this->external );
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

		if ( 'featured_image' !== strtolower( $field ) && ! property_exists( $this->external, $field ) && isset( $this->gd_post->$field ) ) {
			return $this->gd_post->$field;
		}

		if ( 'package_name' === $field ) {
			return geodir_pricing_package_name( $this->gd_post->package_id );
		}

		return parent::get( $field, $args );
	}
}
