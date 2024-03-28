<?php

namespace Hizzle\Noptin\Integrations\EDD;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for an EDD download.
 *
 * @since 2.2.0
 */
class Download extends \Hizzle\Noptin\Objects\Generic_Post {

	/**
	 * @var \EDD_Download The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {
		if ( is_numeric( $external ) ) {
			$external = edd_get_download( $external );
		}

		$this->external = $external;
	}

	/**
	 * Checks if the download exists.
	 * @return bool
	 */
	public function exists() {
		if ( ! is_a( $this->external, 'EDD_Download' ) ) {
			return false;
		}

		return ! empty( $this->external->ID );
	}

	/**
	 * Retrieves a given field's value.
	 *
	 * @param string $field The field.
	 * @param array  $args  The arguments.
	 * @return mixed $value The value.
	 */
	public function get( $field_key, $args = array() ) {

		if ( ! $this->exists() ) {
			return null;
		}

		if ( 'id' === strtolower( $field_key ) ) {
			return $this->external->ID;
		}

		if ( 'url' === strtolower( $field_key ) ) {
			return get_permalink( $this->external->ID );
		}

		// categories.
		if ( 'categories' === strtolower( $field_key ) ) {
			return $this->prepare_terms( $this->external->ID, 'download_category', ! empty( $args['link'] ) );
		}

		// tags.
		if ( 'tags' === strtolower( $field_key ) ) {
			return $this->prepare_terms( $this->external->ID, 'download_tag', ! empty( $args['link'] ) );
		}

		// image.
		if ( 'image' === strtolower( $field_key ) ) {
			$image_size = ! empty( $args['size'] ) ? $args['size'] : 'large';
			$url        = get_the_post_thumbnail_url( $this->external, $image_size );
			return $url ? $url : '';
		}

		// Meta.
		if ( 'meta' === $field_key ) {
			return isset( $args['key'] ) ? get_post_meta( $this->external->ID, $args['key'], true ) : null;
		}

		// Short description.
		if ( 'short_description' === $field_key ) {
			return get_the_excerpt( $this->external->ID );
		}

		if ( is_callable( array( $this->external, $field_key ) ) ) {
			return $this->external->$field_key();
		}

		// Get field.
		$method = 'get_' . $field_key;

		if ( is_callable( array( $this->external, $method ) ) ) {
			return $this->external->$method();
		}

		return '';
	}
}
