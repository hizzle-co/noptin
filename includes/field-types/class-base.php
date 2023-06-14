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
	 * Whether or not it supports storing values in subscribers table.
	 *
	 * @var bool
	 */
	public $store_in_subscribers_table = false;

	/**
	 * Class constructor.
	 *
	 * @since 1.5.5
	 */
	public function __construct( $type ) {
		$this->type = $type;

		add_action( "noptin_display_{$this->type}_input", array( $this, 'output' ), 10, 2 );
		add_filter( "noptin_format_{$this->type}_value", array( $this, 'format_value' ), 10, 2 );
		add_action( 'noptin_custom_field_settings', array( $this, 'custom_field_settings' ) );

		if ( $this->store_in_subscribers_table ) {
			add_filter( "noptin_{$this->type}_store_in_subscribers_table", '__return_true' );
			add_filter( "noptin_filter_{$this->type}_schema", array( $this, 'filter_db_schema' ), 10, 2 );
			add_filter( "noptin_filter_{$this->type}_meta_to_migrate", array( $this, 'filter_meta_to_migrate' ), 10, 2 );
		}
	}

	/**
	 * Optionally specify additional settings for this field.
	 *
	 * @see Noptin_Vue::render_el
	 * @since 1.5.5
	 */
	public function custom_field_settings() {}

	/**
	 * Displays the actual markup for this field.
	 *
	 * @since 1.5.5
	 * @param array $args Field args
	 * @param false|Noptin_Subscriber $subscriber
	 */
	abstract public function output( $args, $subscriber );

	/**
	 * Formats a value for display.
	 *
	 * @since 1.5.5
	 * @param mixed $value Sanitized value
	 * @param Noptin_Subscriber $subscriber
	 */
	public function format_value( $value, $subscriber ) {
		return '' === $value ? '&mdash;' : $value;
	}

	/**
	 * Fetches a field's column name.
	 *
	 * @since 1.13.0
	 * @param array $custom_field
	 * @return string
	 */
	public function get_column_name( $custom_field ) {

		if ( ! empty( $custom_field['predefined'] ) ) {
			return $custom_field['merge_tag'];
		}

		return 'cf_' . $custom_field['merge_tag'];
	}

	/**
	 * Filters the meta to migrate.
	 *
	 * @since 1.13.0
	 * @param array $meta
	 * @param array $custom_field
	 * @return array
	 */
	public function filter_meta_to_migrate( $schema, $custom_field ) {
		$schema[ $custom_field['merge_tag'] ] = $this->get_column_name( $custom_field );

		return $schema;
	}
}
