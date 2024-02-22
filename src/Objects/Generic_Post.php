<?php

namespace Hizzle\Noptin\Objects;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a WordPress post.
 *
 * @since 3.0.0
 */
class Generic_Post extends Record {

	/**
	 * @var \WP_Post The external object.
	 */
	public $external;

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
	}

	/**
	 * Checks if the post exists.
	 * @return bool
	 */
	public function exists() {
		if ( ! is_a( $this->external, 'WP_Post' ) ) {
			return false;
		}

		return ! empty( $this->external->ID );
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

		// Check if string begins with tax_.
		if ( 0 === strpos( $field, 'tax_' ) ) {
			$taxonomy = substr( $field, 4 );
			return self::prepare_terms( $this->external->ID, $taxonomy, ! empty( $args['link'] ) );
		}

		// ID.
		if ( 'id' === strtolower( $field ) ) {
			return $this->external->ID;
		}

		// Prefix by post_.
		if ( in_array( $field, array( 'author', 'date', 'status', 'parent' ), true ) ) {
			return $this->external->{'post_' . $field};
		}

		// Read directly from the object.
		if ( in_array( $field, array( 'comment_status', 'ping_status', 'comment_count' ), true ) ) {
			return $this->external->{$field};
		}

		// Content.
		if ( 'content' === strtolower( $field ) ) {
			return $this->filter_content( $this->external->post_content );
		}

		// Title.
		if ( 'title' === strtolower( $field ) ) {
			return get_the_title( $this->external );
		}

		// Excerpt.
		if ( 'excerpt' === strtolower( $field ) ) {
			return apply_filters( 'the_excerpt', get_the_excerpt( $this->external ) );
		}

		// URL.
		if ( 'url' === strtolower( $field ) ) {
			return get_permalink( $this->external );
		}

		// slug.
		if ( 'slug' === strtolower( $field ) ) {
			return $this->external->post_name;
		}

		// Featured image URL.
		if ( 'featured_image' === strtolower( $field ) ) {
			$image_size = ! empty( $args['size'] ) ? $args['size'] : 'large';
			$url        = get_the_post_thumbnail_url( $this->external, $image_size );
			return $url ? $url : '';
		}

		// Meta.
		if ( 'meta' === $field ) {
			$field = isset( $args['key'] ) ? $args['key'] : null;
		}

		// Abort if no field.
		if ( empty( $field ) ) {
			return null;
		}

		return get_post_meta( $this->external->ID, $field, true );
	}

	/**
	 * Filter the content.
	 */
	protected function filter_content( $content ) {
		$callbacks = array(
			'do_blocks',
			'wptexturize',
			'wpautop',
			'shortcode_unautop',
			'wp_replace_insecure_home_url',
			'do_shortcode',
			'capital_P_dangit',
			'convert_smilies',
		);

		foreach ( $callbacks as $callback ) {
			$content = $callback( $content );
		}

		return $content;
	}

	public static function prepare_terms( $id, $taxonomy, $link ) {
		/** @var \WP_Term[] $terms */
		$terms = wp_get_post_terms( $id, $taxonomy );

		if ( empty( $terms ) || ! is_array( $terms ) ) {
			return '';
		}

		$prepared = array();

		foreach ( $terms as $term ) {

			if ( empty( $term ) ) {
				continue;
			}

			if ( $link ) {
				$term_url = get_term_link( $term );

				if ( ! is_wp_error( $term_url ) ) {
					$prepared[] = sprintf( '<a href="%s">%s</a> ', $term_url, esc_html( $term->name ) );
					continue;
				}
			}

			$prepared[] = sprintf( '<span>%s</span> ', esc_html( $term->name ) );
		}

		return implode( ', ', $prepared );
	}
}
