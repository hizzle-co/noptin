<?php

namespace Hizzle\Noptin\Objects;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a WC product.
 *
 * @since 2.2.0
 */
class Product extends Record {

	/**
	 * @var \WC_Product The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {
		$this->external = wc_get_product( $external );
	}

	/**
	 * Checks if the post exists.
	 * @return bool
	 */
	public function exists() {
		if ( ! is_a( $this->external, 'WC_Product' ) ) {
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

		if ( ! $this->exists() ) {
			return null;
		}

		// ID.
		if ( 'id' === strtolower( $field ) ) {
			return $this->external->get_id();
		}

		// dimensions.
		if ( 'dimensions' === strtolower( $field ) ) {
			return apply_filters( 'woocommerce_product_dimensions', wc_format_dimensions( $this->external->get_dimensions( false ) ), $this->external );
		}

		// categories.
		if ( 'categories' === strtolower( $field ) ) {
			$categories = $this->external->get_category_ids();

			// If we have categories, return category names.
			if ( is_array( $categories ) ) {
				$categories = array_map( 'get_term_by', array_fill( 0, count( $categories ), 'id' ), $categories, array_fill( 0, count( $categories ), 'product_cat' ) );
				$categories = array_map( 'wp_list_pluck', array_fill( 0, count( $categories ), 'name' ), $categories );
				$categories = array_map( 'reset', $categories );
			}

			return is_array( $categories ) ? implode( ', ', array_unique( $categories ) ) : '';
		}

		// tags.
		if ( 'tags' === strtolower( $field ) ) {
			$tags = $this->external->get_tag_ids();

			// If we have tags, return tag names.
			if ( is_array( $tags ) ) {
				$tags = array_map( 'get_term_by', array_fill( 0, count( $tags ), 'id' ), $tags, array_fill( 0, count( $tags ), 'product_tag' ) );
				$tags = array_map( 'wp_list_pluck', array_fill( 0, count( $tags ), 'name' ), $tags );
				$tags = array_map( 'reset', $tags );
			}

			return is_array( $tags ) ? implode( ', ', array_unique( $tags ) ) : '';
		}

		// URL.
		if ( 'url' === strtolower( $field ) ) {
			return get_permalink( $this->external );
		}

		// Image url.
		if ( 'image_url' === strtolower( $field ) ) {
			if ( $this->external->get_image_id() ) {
				return wp_get_attachment_url( $this->external->get_image_id() );
			}

			if ( $this->external->get_parent_id() ) {
				$parent_product = wc_get_product( $this->external->get_parent_id() );
				if ( $parent_product && $parent_product->get_image_id() ) {
					return wp_get_attachment_url( $parent_product->get_image_id() );
				}
			}

			return wc_placeholder_img_src();
		}

		// Check if we have a method get_$field.
		$method = 'get_' . $field;
		if ( method_exists( $this->external, $method ) ) {
			$value = $this->external->{$method}();

			if ( is_a( $value, 'WC_DateTime' ) ) {
				return wc_format_datetime( $value );
			}

			return $value;
		}

		if ( method_exists( $this->external, $field ) ) {
			return $this->external->{$field}();
		}

		// Meta.
		if ( 0 === strpos( $field, 'meta.' ) ) {
			$field = substr( $field, 5 );
		}

		return get_post_meta( $this->external->ID, $field, true );
	}
}
