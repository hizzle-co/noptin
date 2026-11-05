<?php
/**
 * The base custom field class.
 *
 * Custom field types such as texts, textarea etc can extend this class.
 * They will then be instantiated with details of the corresponding custom field.
 *
 * @since 1.0.0
 *
 */

namespace Hizzle\Noptin\Fields\Types;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Custom field class.
 *
 * @since 1.5.5
 */
abstract class Base {

	/**
	 * Custom field type.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Class constructor.
	 *
	 * @since 1.5.5
	 */
	public function __construct( $type ) {
		$this->type = $type;

		add_action( "noptin_display_{$this->type}_input", array( $this, 'output' ) );
		add_filter( "noptin_filter_{$this->type}_schema", array( $this, 'filter_db_schema' ), 10, 2 );
	}

	/**
	 * Displays the actual markup for this field.
	 *
	 * @since 1.5.5
	 * @param array $args Field args
	 */
	abstract public function output( $args );

	/**
	 * Fetches a field's column name.
	 *
	 * @since 2.0.0
	 * @param array $custom_field
	 * @return string
	 */
	public function get_column_name( $custom_field ) {
		return sanitize_key( $custom_field['merge_tag'] );
	}

	/**
	 * Filters the database schema.
	 *
	 * @since 2.0.0
	 * @param array $schema
	 * @param array $field
	 */
	public function filter_db_schema( $schema, $custom_field ) {
		$columns_name = $this->get_column_name( $custom_field );

		$schema[ $columns_name ] = array(
			'type'        => 'TEXT',
			'label'       => wp_strip_all_tags( $custom_field['label'] ),
			'description' => wp_strip_all_tags( $custom_field['label'] ),
		);

		if ( ! empty( $custom_field['dynamic'] ) ) {
			$schema[ $columns_name ]['is_dynamic'] = true;
		}

		if ( isset( $custom_field['default_value'] ) && '' !== $custom_field['default_value'] ) {
			$schema[ $columns_name ]['default'] = $custom_field['default_value'];
		}
		return $schema;
	}

	/**
	 * Remove merge tags and shortcode syntax from subscriber-entered text.
	 *
	 * @param mixed $value Plain text.
	 * @return string
	 */
	protected function strip_dynamic_tags( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		do {
			$previous = $value;
			// Remove complete merge tags, including tags nested in their attributes.
			$value = preg_replace( '/\[\[(?:[^\[\]]++|(?R)|\[(?!\[)|\](?!\]))*\]\]/s', '', $value ) ?? '';
			// The email parser also recognizes entity-encoded square brackets.
			$value = preg_replace( '/&#91;&#91;.*?&#93;&#93;/s', '', $value ) ?? '';
			$value = preg_replace( '/\{\{?\/?[a-zA-Z_][a-zA-Z0-9_.:-]*(?:\s+[^{}]*)?\/?\}\}?/s', '', $value ) ?? '';

			// Discover names from the input rather than the active shortcode registry.
			// Unknown shortcodes must not become executable when an add-on is enabled later.
			if ( preg_match_all( '@\[/?([^<>&/\[\]\x00-\x20=]++)@', $value, $matches ) ) {
				$value = preg_replace( '/' . get_shortcode_regex( array_unique( $matches[1] ) ) . '/s', '', $value ) ?? '';
			}
			// Removal can join fragments into another tag; strip those too.
		} while ( $value !== $previous );

		return $value;
	}
}
