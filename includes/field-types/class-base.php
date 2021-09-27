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
		add_filter( "noptin_sanitize_{$this->type}_value", array( $this, 'sanitize_value' ), 10, 2 );
		add_filter( "noptin_format_{$this->type}_value", array( $this, 'format_value' ), 10, 2 );
		add_action( 'noptin_custom_field_settings', array( $this, 'custom_field_settings' ) );

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
	 * Sanitizes the submitted value.
	 *
	 * @since 1.5.5
	 * @param mixed $value Submitted value
	 * @param false|Noptin_Subscriber $subscriber
	 */
	abstract public function sanitize_value( $value, $subscriber );

	/**
	 * Formats a value for display.
	 *
	 * @since 1.5.5
	 * @param mixed $value Sanitized value
	 * @param Noptin_Subscriber $subscriber
	 */
	public function format_value( $value, $subscriber ) {
		$value = $this->sanitize_value( $value, $subscriber );
		return '' === $value ? "&mdash;" : $value;
	}

}
