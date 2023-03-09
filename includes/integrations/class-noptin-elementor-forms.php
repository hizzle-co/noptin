<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with Elementor Forms
 *
 * @since 1.13.3
 */
class Noptin_Elementor_Forms {

	public $ignore_fields = array( 'step', 'recaptcha', 'recaptcha_v3', 'honeypot', 'html' );

	/**
	 * Constructor
	 */
	public function __construct() {

		// Save subscriber.
        add_action( 'elementor_pro/forms/new_record', array( $this, 'process_form' ) );

		// Load automation rule.
		if ( did_action( 'noptin_automation_rules_load' ) ) {
			$this->load_automation_rule( noptin()->automation_rules );
		} else {
			add_action( 'noptin_automation_rules_load', array( $this, 'load_automation_rule' ) );
		}

		add_filter( 'noptin_elementor_forms', array( $this, 'filter_forms' ) );
	}

	/**
	 * Loads our automation rule.
	 *
	 * @param Noptin_Automation_Rules $rules The automation rules instance.
	 */
	public function load_automation_rule( $rules ) {
		$rules->add_trigger( new Noptin_Form_Submit_Trigger( 'elementor', 'Elementor' ) );
	}

	/**
	 * Filters forms.
	 *
	 * @param array $forms An array of forms.
	 * @return array
	 */
	public function filter_forms( $forms ) {
		global $noptin_elementor_forms;

		// Return cached forms.
		if ( is_array( $noptin_elementor_forms ) ) {
			return array_replace( $forms, $noptin_elementor_forms );
		}

		$noptin_elementor_forms = $this->get_forms();

		return array_replace( $forms, $noptin_elementor_forms );
	}

	/**
	 * Fetch all forms.
	 *
	 * @param array $forms An array of forms.
	 * @return array
	 */
	public function get_forms() {

		$forms           = array();
		$elementor_posts = get_posts(
			array(
				'post_type'      => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_key'       => '_elementor_data',
				'meta_value'     => 'form_fields',
				'meta_compare'   => 'LIKE',
				'post_status'    => array( 'publish', 'draft', 'future', 'pending', 'private' ),
			)
		);

		foreach ( $elementor_posts as $post_id ) {
			$elements = get_post_meta( $post_id, '_elementor_data', true );
			$forms    = array_merge( $forms, $this->get_all_inner_forms( json_decode( $elements ) ) );
		}

		return $forms;
	}

	/**
	 * Retrieves all inner forms from a given element.
	 *
     * @param array $elements An array of elements.
     */
	protected function get_all_inner_forms( $elements ) {
		$forms = array();

		// Abort if no elements are found.
		if ( ! is_array( $elements ) ) {
			return $forms;
		}

		foreach ( $elements as $element ) {

			// Abort if not object.
			if ( ! is_object( $element ) ) {
				continue;
			}

			// Check for inner elements.
			if ( ! empty( $element->elements ) ) {
				$forms = array_merge( $forms, $this->get_all_inner_forms( $element->elements ) );
			}

			if ( ! isset( $element->elType ) || ! isset( $element->widgetType ) ) {
				continue;
			}

			if ( 'widget' === $element->elType && 'form' === $element->widgetType ) {
				$forms[ $element->id ] = array(
					'name'   => $element->settings->form_name . " (ID: {$element->id})",
					'fields' => $this->prepare_noptin_automation_rule_fields( $element->settings->form_fields ),
				);
			}
		}

		return $forms;
	}

	/**
     * @param ElementorPro\Modules\Forms\Classes\Form_Record $record The submitted record.
     */
    public function process_form( $record ) {

		$form_id = $record->get_form_settings( 'id' );

		if ( empty( $form_id ) ) {
			return;
		}

		// Posted fields.
		$posted = wp_list_pluck( $record->get( 'fields' ), 'value', 'id' );

		// Add meta.
		$posted = array_merge( wp_list_pluck( $record->get( 'meta' ), 'value' ), $posted );

        do_action( 'noptin_elementor_form_submitted', $form_id, $posted );
	}

	/**
     * Prepares form fields.
     *
     * @param object[] $fields The form fields.
     * @return array
     */
    public function prepare_noptin_automation_rule_fields( $fields ) {

        $prepared_fields = array(
			'page_url'   => array(
				'description'       => __( 'Page URL', 'newsletter-optin-box' ),
				'conditional_logic' => 'string',
			),
			'user_agent' => array(
				'description'       => __( 'User Agent', 'newsletter-optin-box' ),
				'conditional_logic' => 'string',
			),
			'remote_ip'  => array(
				'description'       => __( 'IP Address', 'newsletter-optin-box' ),
				'conditional_logic' => 'string',
			),
		);

        // Loop through all fields.
        foreach ( $fields as $elementor_field ) {

            // Skip fields with no name.
            if ( empty( $elementor_field->field_label ) ) {
                continue;
            }

			if ( ! empty( $elementor_field->field_type ) && in_array( $elementor_field->field_type, $this->ignore_fields, true ) ) {
				continue;
			}

            $field = array(
				'description'       => $elementor_field->field_label,
				'conditional_logic' => 'string',
			);

			if ( ! empty( $elementor_field->field_type ) && 'number' === $elementor_field->field_type ) {
				$field['conditional_logic'] = 'number';
			}

			$options = $this->get_field_options( $elementor_field );

			if ( ! empty( $options ) ) {
				$field['options'] = $options;
			}

			$prepared_fields[ $elementor_field->custom_id ] = $field;
        }

        return $prepared_fields;
    }

	/**
	 * Retrieves the field options.
	 *
	 * @param object $field The field.
	 * @return array
	 */
	public function get_field_options( $field ) {

		// Abort if field has no options.
		if ( empty( $field->field_options ) ) {
			return array();
		}

		$options = array();

		// Split options by line.
		foreach ( preg_split( '/\r\n|\r|\n/', $field->field_options ) as $option ) {

			// Split option by pipe.
			$option = explode( '|', $option );

			// Use the label as the value if no value is provided.
			if ( ! isset( $option[1] ) ) {
				$option[1] = $option[0];
			}

			$options[ trim( $option[1] ) ] = trim( $option[0] );
		}

		return $options;
	}
}
