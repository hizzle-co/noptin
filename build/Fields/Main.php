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

		// Custom field settings.
		add_filter( 'noptin_get_settings', array( __CLASS__, 'custom_field_settings' ), 40 );

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
	 * Custom field settings.
	 *
	 * This adds a repeater field to the settings page
	 * where users can add custom fields.
	 * These custom fields can then be used in subscription forms
	 * or integrated with third-party services.
	 *
	 * @param array $settings
	 */
	public static function custom_field_settings( $settings ) {
		$field_map_settings        = apply_filters( 'noptin_get_custom_fields_map_settings', array() );
		$settings['custom_fields'] = array(
			'el'               => 'repeater',
			'section'          => 'fields',
			'label'            => __( 'Custom Fields', 'newsletter-optin-box' ),
			'default'          => self::default_fields(),
			'description'      => sprintf(
				'%s <a href="https://noptin.com/guide/email-subscribers/custom-fields/" target="_blank">%s</a>',
				__( 'Collect more information from your subscribers by adding custom fields. ', 'newsletter-optin-box' ),
				__( 'Learn More', 'newsletter-optin-box' )
			),
			'customAttributes' => array(
				'repeaterKey'         => array(
					'from'      => 'label',
					'to'        => 'merge_tag',
					'newOnly'   => true,
					'maxLength' => 20,
					'display'   => '[[%s]]',
				),
				'defaultItem'         => array(
					'predefined' => false,
				),
				'hideLabelFromVision' => true,
				'fields'              => array_merge(
					array(
						'type'          => array(
							'el'          => 'select',
							'label'       => __( 'Field Type', 'newsletter-optin-box' ),
							'options'     => wp_list_pluck(
								wp_list_filter(
									get_noptin_custom_field_types(),
									array( 'predefined' => false )
								),
								'label'
							),
							'description' => __( 'Select the field type', 'newsletter-optin-box' ),
							'default'     => 'text',
							'conditions'  => array(
								array(
									'key'      => 'type',
									'operator' => '!includes',
									'value'    => get_noptin_predefined_custom_fields(),
								),
							),
						),
						'label'         => array(
							'el'          => 'input',
							'label'       => __( 'Field Name', 'newsletter-optin-box' ),
							'description' => __( 'Enter a descriptive name for the field, for example, Phone Number', 'newsletter-optin-box' ),
						),
						'placeholder'   => array(
							'el'          => 'input',
							'label'       => __( 'Placeholder', 'newsletter-optin-box' ),
							'description' => __( 'Optional. Enter the default placeholder for this field', 'newsletter-optin-box' ),
							'conditions'  => array(
								array(
									'key'      => 'type',
									'operator' => 'includes',
									'value'    => array( 'text', 'textarea', 'number', 'email', 'first_name', 'last_name' ),
								),
							),
						),
						'options'       => array(
							'el'          => 'textarea',
							'label'       => __( 'Available Options', 'newsletter-optin-box' ),
							'description' => __( 'Enter one option per line. You can use pipes to separate values and labels.', 'newsletter-optin-box' ),
							'conditions'  => array(
								array(
									'key'      => 'type',
									'operator' => 'includes',
									'value'    => get_noptin_option_supported_fields(),
								),
							),
							'placeholder' => implode( PHP_EOL, array( 'Option 1', 'Option 2', 'Option 3' ) ),
						),
						'default_value' => array(
							'el'          => 'input',
							'label'       => __( 'Default value', 'newsletter-optin-box' ),
							'description' => __( 'Optional. Enter the default value for this field', 'newsletter-optin-box' ),
						),
					),
					$field_map_settings,
					array(
						'visible'  => array(
							'el'          => 'input',
							'type'        => 'checkbox_alt',
							'label'       => __( 'Editable', 'newsletter-optin-box' ),
							'description' => __( 'Can subscribers view and edit this field?', 'newsletter-optin-box' ),
							'tooltip'     => __( 'If unchecked, you can\'t add this field to the subscription form.', 'newsletter-optin-box' ),
							'default'     => true,
							'conditions'  => array(
								array(
									'key'      => 'merge_tag',
									'operator' => '!=',
									'value'    => 'email',
								),
							),
						),
						'required' => array(
							'el'          => 'input',
							'type'        => 'checkbox_alt',
							'label'       => __( 'Required', 'newsletter-optin-box' ),
							'description' => __( 'Subscribers MUST fill this field whenever it is added to a subscription form.', 'newsletter-optin-box' ),
							'conditions'  => array(
								array(
									'key'      => 'merge_tag',
									'operator' => '!=',
									'value'    => 'email',
								),
							),
						),
					)
				),
			),
		);

		return $settings;
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
