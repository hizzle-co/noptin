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

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Custom field class.
 *
 * @since 1.5.5
 */
abstract class Noptin_Custom_Field_Type {

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

		add_action( "noptin_display_{$this->type}_input", array( $this, 'output' ), 10, 2 );
		add_filter( "noptin_filter_{$this->type}_schema", array( $this, 'filter_db_schema' ), 10, 2 );
	}

	/**
	 * Displays the actual markup for this field.
	 *
	 * @since 1.5.5
	 * @param array $args Field args
	 * @param false|Noptin_Subscriber $subscriber
	 */
	abstract public function output( $args, $subscriber );

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
		$schema[ $this->get_column_name( $custom_field ) ] = array(
			'type'        => 'TEXT',
			'label'       => wp_strip_all_tags( $custom_field['label'] ),
			'description' => wp_strip_all_tags( $custom_field['label'] ),
		);

		if ( isset( $custom_field['default_value'] ) && '' !== $custom_field['default_value'] ) {
			$schema[ $this->get_column_name( $custom_field ) ]['default'] = $custom_field['default_value'];
		}
		return $schema;
	}
}
