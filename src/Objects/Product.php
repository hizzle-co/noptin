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
				return $this->external->get_meta( $args['key'], true );
			}

			return '';
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
			return $this->prepare_terms( $this->external->get_category_ids(), 'product_cat', ! empty( $args['link'] ) );
		}

		// tags.
		if ( 'tags' === strtolower( $field ) ) {
			return $this->prepare_terms( $this->external->get_tag_ids(), 'product_tag', ! empty( $args['link'] ) );
		}

		// URL.
		if ( 'url' === strtolower( $field ) ) {
			return $this->external->get_permalink();
		}

		// Image url.
		if ( 'image' === strtolower( $field ) ) {
			$image_size = ! empty( $args['size'] ) ? $args['size'] : 'woocommerce_thumbnail';
			if ( $this->external->get_image_id() ) {
				return wp_get_attachment_image_url( $this->external->get_image_id(), $image_size );
			}

			if ( $this->external->get_parent_id() ) {
				$parent_product = wc_get_product( $this->external->get_parent_id() );
				if ( $parent_product && $parent_product->get_image_id() ) {
					return wp_get_attachment_image_url( $parent_product->get_image_id(), $image_size );
				}
			}

			return wc_placeholder_img_src( $image_size );
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

		return null;
	}

	private function prepare_terms( $term_ids, $taxonomy, $link ) {

		if ( empty( $term_ids ) || ! is_array( $term_ids ) ) {
			return '';
		}

		$terms    = array_map( 'get_term_by', array_fill( 0, count( $term_ids ), 'id' ), $term_ids, array_fill( 0, count( $term_ids ), $taxonomy ) );
		$prepared = array();

		/** @var \WP_Term $term */
		foreach ( $terms as $term ) {

			if ( empty( $term ) ) {
				continue;
			}

			if ( $link ) {
				$term_url = get_term_link( $term );

				if ( ! is_wp_error( $term_url ) ) {
					$prepared[] = sprintf( '<a href="%s">%s</a>', $term_url, esc_html( $term->name ) );
					continue;
				}
			}

			$prepared[] = sprintf( '<span>%s</span>', esc_html( $term->name ) );
		}

		return implode( ', ', $prepared );
	}
}
