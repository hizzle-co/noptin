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
	 * The excerpt limit.
	 */
	public $excerpt_length = null;

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
	 * @param array $args Optional arguments.
	 * @return mixed $value The value.
	 */
	public function get( $field, $args = array() ) {
		global $post;

		if ( ! $this->exists() ) {
			return null;
		}

		$post = $this->external;
		setup_postdata( $post );
		$value = $this->do_get( $field, $args );
		wp_reset_postdata();
		return $value;
	}

	/**
	 * Actually retrieves a given field's value.
	 *
	 * @param string $field The field.
	 * @param array $args Optional arguments.
	 * @return mixed $value The value.
	 */
	protected function do_get( $field, $args = array() ) {

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
			$content = $this->external->post_content;

			// Check if the user has specified the number of paragraphs to display.
			if ( ! empty( $args['paragraphs'] ) ) {
				$content = excerpt_remove_footnotes( excerpt_remove_blocks( $content ) );
			} else {
				$content = do_blocks( $content );
			}

			$content = $this->filter_content( $content );

			// Check if the user has specified the number of paragraphs to display.
			if ( ! empty( $args['paragraphs'] ) ) {
				$paragraphs = explode( '</p>', $content );
				$content    = implode( '</p>', array_slice( $paragraphs, 0, (int) $args['paragraphs'] ) );
			}

			return $content;
		}

		// Title.
		if ( 'title' === strtolower( $field ) ) {
			return get_the_title( $this->external );
		}

		// Excerpt.
		if ( 'excerpt' === strtolower( $field ) ) {

			// Are we limiting the excerpt length?
			if ( ! empty( $args['words'] ) ) {
				$this->excerpt_length = (int) $args['words'];
				add_filter( 'excerpt_length', array( $this, 'excerpt_length' ) );
			}

			// Prevent wp_rss_aggregator from appending the feed name to excerpts.
			$wp_rss_aggregator_fix = has_filter( 'get_the_excerpt', 'mdwp_MarkdownPost' );

			if ( false !== $wp_rss_aggregator_fix ) {
				remove_filter( 'get_the_excerpt', 'mdwp_MarkdownPost', $wp_rss_aggregator_fix );
			}

			// Register WPBakery shortcodes.
			if ( is_callable( '\\WPBMap::addAllMappedShortcodes' ) ) {
				\WPBMap::addAllMappedShortcodes();
			}

			// Apply filters and strip tags
			$excerpt = wp_strip_all_tags( apply_filters( 'the_excerpt', get_the_excerpt( $this->external ) ) );

			if ( false !== $wp_rss_aggregator_fix ) {
				add_filter( 'get_the_excerpt', 'mdwp_MarkdownPost', $wp_rss_aggregator_fix );
			}

			// Remove the excerpt length filter.
			if ( ! empty( $args['words'] ) ) {
				$this->excerpt_length = null;
				remove_filter( 'excerpt_length', array( $this, 'excerpt_length' ) );
				$excerpt = wp_trim_words( $excerpt, $args['words'], '' );
			}

			return $excerpt;
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

		$value = get_post_meta( $this->external->ID, $field, true );
		return apply_filters( 'noptin_post_get_meta', $value, $field, $this->external->ID, $args );
	}

	/**
	 * Filter the content.
	 */
	protected function filter_content( $content ) {
		$callbacks = array(
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

		if ( $link ) {
			$link = self::is_taxonomy_linkable( $taxonomy );
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

	public static function is_taxonomy_linkable( $taxonomy ) {
		// Check if the taxonomy exists
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return false;
		}

		// Get the taxonomy object
		$taxonomy_object = get_taxonomy( $taxonomy );

		// Check if the taxonomy is public
		return $taxonomy_object->public;
	}

	/**
	 * Filter the excerpt length.
	 */
	public function excerpt_length( $length = 55 ) {
		return empty( $this->excerpt_length ) ? $length : $this->excerpt_length;
	}
}
