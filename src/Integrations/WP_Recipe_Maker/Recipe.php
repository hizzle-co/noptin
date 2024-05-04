<?php

namespace Hizzle\Noptin\Integrations\WP_Recipe_Maker;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a recipe.
 *
 * @since 2.2.0
 */
class Recipe extends \Hizzle\Noptin\Objects\Record {

	/**
	 * @var \WPRM_Recipe The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {
		$this->external = \WPRM_Recipe_Manager::get_recipe( $external );
	}

	/**
	 * Checks if the post exists.
	 * @return bool
	 */
	public function exists() {
		if ( ! is_a( $this->external, 'WPRM_Recipe' ) ) {
			return false;
		}

		return $this->external->id() > 0;
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
				return $this->external->meta( $args['key'], '' );
			}

			return '';
		}

		// ID.
		if ( 'id' === strtolower( $field ) ) {
			return $this->external->id();
		}

		// URL.
		if ( 'url' === strtolower( $field ) ) {
			return $this->external->permalink();
		}

		// Image url.
		if ( 'image' === strtolower( $field ) ) {
			$image_id   = $this->external->image_id();
			$image_size = ! empty( $args['size'] ) ? $args['size'] : 'large';
			if ( ! empty( $image_id ) ) {
				return wp_get_attachment_image_url( $image_id, $image_size );
			}

			return '';
		}

		// Instructions.
		if ( 'instructions' === strtolower( $field ) ) {
			if ( ! empty( $args['format'] ) ) {
				return $this->external->instructions_flat();
			}

			$instructions = wp_list_pluck( $this->external->instructions_flat(), 'text' );

			// Replace paragraphs with line breaks.
			$instructions = '<ol><li>' . implode( '</li><li>', array_map( 'trim', $instructions ) ) . '</li></ol>';
			$instructions = str_replace( array( '<p>', '</p>' ), array( '', '<br/>' ), $instructions );
			$instructions = str_replace( array( '<br/></li>' ), array( '', '</li>' ), $instructions );
			return $instructions;
		}

		// Ingredients.
		if ( 'ingredients' === strtolower( $field ) || 'equipment' === strtolower( $field ) ) {

			$method = 'ingredients' === strtolower( $field ) ? 'ingredients_flat' : 'equipment';

			if ( ! empty( $args['format'] ) ) {
				return $this->external->$method();
			}

			$ingredients = '<ul>';

			foreach ( $this->external->$method() as $ingredient ) {
				$prepared = sprintf(
					'%s %s %s',
					isset( $ingredient['amount'] ) ? $ingredient['amount'] : '',
					isset( $ingredient['unit'] ) ? $ingredient['unit'] : '',
					isset( $ingredient['name'] ) ? $ingredient['name'] : ''
				);

				$prepared = trim( $prepared );

				if ( ! empty( $ingredient['notes'] ) ) {
					$prepared .= ' (' . $ingredient['notes'] . ')';
				}

				if ( ! empty( $prepared ) ) {
					$ingredients .= '<li>' . esc_html( $prepared ) . '</li>';
				}
			}

			return $ingredients . '</ul>';
		}

		// Time.
		foreach ( array( 'prep_time', 'cook_time', 'total_time', 'custom_time' ) as $time ) {
			if ( strtolower( $field ) === $time && empty( $args['format'] ) ) {
				$use_zero = is_callable( array( $this->external, $time . '_zero' ) ) ? $this->external->{$time . '_zero'}() : false;
				return \WPRM_Template_Helper::time( $time, $this->external->{$time}(), $use_zero, ! empty( $args['shorthand'] ) );
			}
		}

		// Taxonomies.
		foreach ( array_keys( \WPRM_Taxonomies::get_taxonomies() ) as $taxonomy ) {
			if ( strtolower( $field ) === $taxonomy ) {
				return \Hizzle\Noptin\Objects\Generic_Post::prepare_terms( $this->external->id(), $taxonomy, ! empty( $args['link'] ) );
			}
		}

		if ( method_exists( $this->external, $field ) ) {
			return $this->external->{$field}();
		}

		return apply_filters( 'noptin_post_get_meta', null, $field, $this->external->id(), $args );
	}
}
