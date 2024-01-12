<?php

namespace Hizzle\Noptin\Objects;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a WordPress post.
 *
 * @since 2.2.0
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
	public function get( $field ) {

		if ( ! $this->exists() ) {
			return null;
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
			return apply_filters( 'the_content', $this->external->post_content );
		}

		// Title.
		if ( 'title' === strtolower( $field ) ) {
			return get_the_title( $this->external );
		}

		// Excerpt.
		if ( 'excerpt' === strtolower( $field ) ) {
			return apply_filters( 'the_excerpt', $this->external->post_excerpt );
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
			$url = get_the_post_thumbnail_url( $this->external );
			return $url ? $url : '';
		}

		// Meta.
		if ( 0 === strpos( $field, 'meta.' ) ) {
			$field = substr( $field, 5 );
		}

		return get_post_meta( $this->external->ID, $field, true );
	}
}
