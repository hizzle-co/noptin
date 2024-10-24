<?php

/**
 * This class handles the display and management of custom fields.
 *
 * @since 1.5.5
 */
class Noptin_Custom_Fields {

	/**
	 * @param Noptin_Custom_Field_Type
	 */
	public $custom_field_types = array();

	/**
	 * Class Constructor.
	 *
	 * @since 1.5.5
	 */
	public function __construct() {

		// Load dependancies.
		foreach ( array( 'base', 'text', 'textarea', 'checkbox', 'date', 'dropdown', 'email', 'number', 'radio', 'multi-checkbox' ) as $file ) {
			require_once plugin_dir_path( __FILE__ ) . "field-types/class-$file.php";
		}

		if ( noptin_is_multilingual() ) {
			require_once plugin_dir_path( __FILE__ ) . 'field-types/class-language.php';
		}

		do_action( 'noptin_load_custom_field_files' );

		// Load custom field types.
		foreach ( get_noptin_custom_field_types() as $type => $data ) {

			if ( ! empty( $data['class'] ) ) {
				$this->custom_field_types[ $type ] = new $data['class']( $type );
			}
		}

		add_filter( 'noptin_form_editor_data', array( $this, 'form_editor_data' ) );

		// Deprecated functionality.
		add_action( 'noptin_field_type_optin_markup', array( $this, 'output_preview' ) );
		add_action( 'noptin_field_type_frontend_optin_markup', array( $this, 'output_frontend' ) );
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
	public function form_editor_data( $data ) {
		$data['fields'] = array();

		foreach ( get_noptin_custom_fields() as $custom_field ) {

			if ( empty( $custom_field['type'] ) || empty( $this->custom_field_types[ $custom_field['type'] ] ) ) {
				continue;
			}

			$custom_field['name']  = $custom_field['merge_tag'];
			$custom_field['id']    = 'noptin_field_' . sanitize_html_class( $custom_field['merge_tag'] );
			$custom_field['value'] = '';
			$custom_field['react'] = true;

			/**@var Noptin_Custom_Field_Type */
			$field = $this->custom_field_types[ $custom_field['type'] ];

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

	/**
	 * Displays the field preview in the old field editor.
	 *
	 * This is deprecated functionality.
	 * @deprecated
	 * @since 1.5.5
	 */
	public function output_preview() {

		foreach ( get_noptin_custom_fields() as $custom_field ) {

			if ( empty( $custom_field['type'] ) || empty( $this->custom_field_types[ $custom_field['type'] ] ) ) {
				continue;
			}

			$custom_field['name']  = $custom_field['merge_tag'];
			$custom_field['id']    = 'noptin_field_' . sanitize_html_class( $custom_field['merge_tag'] );
			$custom_field['value'] = '';
			$custom_field['react'] = true;

			/**@var Noptin_Custom_Field_Type */
			$field = $this->custom_field_types[ $custom_field['type'] ];

			printf(
				'<div v-if="field.type.type==\'%s\'" class="noptin-field-%s">',
				esc_attr( $custom_field['merge_tag'] ),
				esc_attr( $custom_field['type'] )
			);

			$field->output( $custom_field, false );

			echo '</div>';

		}

	}

	/**
	 * Displays the field markup.
	 *
	 * This is deprecated functionality.
	 * @deprecated
	 * @since 1.5.5
	 */
	public function output_frontend( $field ) {

		foreach ( get_noptin_custom_fields() as $custom_field ) {

			if ( $field['type']['type'] !== $custom_field['merge_tag'] ) {
				continue;
			}

			if ( empty( $custom_field['type'] ) || empty( $this->custom_field_types[ $custom_field['type'] ] ) ) {
				continue;
			}

			$custom_field['name']     = 'noptin_fields[' . $custom_field['merge_tag'] . ']';
			$custom_field['id']       = uniqid( sanitize_html_class( $custom_field['merge_tag'] ) );
			$custom_field['value']    = '';
			$custom_field['required'] = ! empty( $field['require'] ) && 'false' !== $field['require'];

			if ( ! empty( $field['type']['label'] ) ) {
				$custom_field['label']       = $field['type']['label'];
				$custom_field['placeholder'] = $field['type']['label'];
			}

			/**@var Noptin_Custom_Field_Type */
			$_field = $this->custom_field_types[ $custom_field['type'] ];

			printf( '<div class="noptin-field-%s">', esc_attr( $custom_field['type'] ) );

			$_field->output( $custom_field, false );

			echo '</div>';

		}
	}

}
