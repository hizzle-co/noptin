<?php

namespace Hizzle\Noptin\Fields;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main fields class.
 *
 * @since 3.0.0
 */
class Main {

	/**
	 * Custom field types.
	 *
	 * @var Field[]
	 */
	public static $custom_field_types = array();

	/**
	 * Main custom fields class.
	 */
	public static function init() {

		// Load functions.
		require_once plugin_dir_path( __FILE__ ) . 'functions.php';

		// Load custom field types.
		add_action( 'after_setup_theme', array( __CLASS__, 'load_custom_field_types' ) );

		// Add field preview data to form editor.
		add_filter( 'noptin_form_editor_data', array( __CLASS__, 'form_editor_data' ) );
	}

	/**
	 * Loads custom field types.
	 */
	public static function load_custom_field_types() {
		foreach ( get_noptin_custom_field_types() as $type => $data ) {

			if ( ! empty( $data['class'] ) ) {
				self::$custom_field_types[ $type ] = new $data['class']( $type );
			}
		}

		do_action( 'noptin_load_custom_field_types' );
	}

	/**
	 * Get predefined fields
	 */
	public static function predefined_fields() {
		$predefined_fields = array();

		foreach ( get_noptin_custom_field_types() as $type => $data ) {
			if ( ! empty( $data['predefined'] ) ) {
				$predefined_fields[] = $type;
			}
		}

		return $predefined_fields;
	}

	/**
	 * Get option fields
	 */
	public static function option_fields() {
		$option_fields = array();

		foreach ( get_noptin_custom_field_types() as $type => $data ) {
			if ( ! empty( $data['supports_options'] ) ) {
				$option_fields[] = $type;
			}
		}

		return $option_fields;
	}

	/**
	 * Get default fields
	 *
	 */
	public static function default_fields() {

		$fields = array(
			array(
				'type'       => 'first_name',
				'merge_tag'  => 'first_name',
				'label'      => __( 'First Name', 'newsletter-optin-box' ),
				'visible'    => true,
				'required'   => false,
				'predefined' => false,
			),
			array(
				'type'       => 'last_name',
				'merge_tag'  => 'last_name',
				'label'      => __( 'Last Name', 'newsletter-optin-box' ),
				'visible'    => true,
				'required'   => false,
				'predefined' => false,
			),
			array(
				'type'       => 'email',
				'merge_tag'  => 'email',
				'label'      => __( 'Email Address', 'newsletter-optin-box' ),
				'visible'    => true,
				'required'   => true,
				'predefined' => true,
			),
		);

		if ( noptin_is_multilingual() ) {
			$fields[] = array(
				'type'       => 'language',
				'merge_tag'  => 'language',
				'label'      => __( 'Language', 'newsletter-optin-box' ),
				'visible'    => false,
				'required'   => false,
				'predefined' => true,
			);
		}

		return apply_filters( 'noptin_default_custom_fields', $fields );
	}

	/**
	 * Filters the form editor data.
	 *
	 */
	public static function form_editor_data( $data ) {
		$data['fields'] = array();

		foreach ( get_noptin_custom_fields() as $custom_field ) {

			if ( empty( $custom_field['type'] ) || empty( self::$custom_field_types[ $custom_field['type'] ] ) ) {
				continue;
			}

			$custom_field['name']  = $custom_field['merge_tag'];
			$custom_field['id']    = 'noptin_field_' . sanitize_html_class( $custom_field['merge_tag'] );
			$custom_field['value'] = '';
			$custom_field['react'] = true;

			/** @var Types\Base */
			$field = self::$custom_field_types[ $custom_field['type'] ];

			ob_start();
			$field->output( $custom_field, false );
			$custom_field['markup']                       = ob_get_clean();
			$data['fields'][ $custom_field['merge_tag'] ] = array(
				'label'  => $custom_field['label'],
				'type'   => $custom_field['type'],
				'markup' => $custom_field['markup'],
			);
		}

		return $data;
	}
}
