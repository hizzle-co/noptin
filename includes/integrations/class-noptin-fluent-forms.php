<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with Fluent Forms
 *
 * @since 1.13.3
 */
class Noptin_Fluent_Forms {

	public $ignore_fields = array(
		'custom_html',
		'section_break',
		'shortcode',
		'action_hook',
		'form_step',
		'tabular_grid',
		'custom_submit_button',
		'save_progress_button',
		'recaptcha',
		'hcaptcha',
		'turnstile',
		'repeater_field',
		'chained_select',
		'payment_summary_component',
		'subscription_payment_component',
	);

	/**
	 * Constructor
	 */
	public function __construct() {

		// Save subscriber.
		add_action( 'fluentform_submission_inserted', array( $this, 'process_form' ), 10, 3 );

		// Load automation rule.
		if ( did_action( 'noptin_automation_rules_load' ) ) {
			$this->load_automation_rule( noptin()->automation_rules );
		} else {
			add_action( 'noptin_automation_rules_load', array( $this, 'load_automation_rule' ) );
		}

		add_filter( 'noptin_fluentform_forms', array( $this, 'filter_forms' ) );
	}

	/**
	 * Loads our automation rule.
	 *
	 * @param Noptin_Automation_Rules $rules The automation rules instance.
	 */
	public function load_automation_rule( $rules ) {
		$rules->add_trigger( new Noptin_Form_Submit_Trigger( 'fluentform', 'Fluent Forms' ) );
	}

	/**
	 * Filters forms.
	 *
	 * @param array $forms An array of forms.
	 * @return array
	 */
	public function filter_forms( $forms ) {
		global $noptin_fluentform_forms;

		// Return cached forms.
		if ( is_array( $noptin_fluentform_forms ) ) {
			return array_replace( $forms, $noptin_fluentform_forms );
		}

		$noptin_fluentform_forms = $this->get_forms();

		return array_replace( $forms, $noptin_fluentform_forms );
	}

	/**
	 * Fetch all forms.
	 *
	 * @param array $forms An array of forms.
	 * @return array
	 */
	public function get_forms() {

		if ( ! method_exists( wpFluent()->table( 'fluentform_forms' ), 'get' ) ) {
			return array();
		}

		$forms = wpFluent()->table( 'fluentform_forms' )
				->select( array( 'id', 'title', 'form_fields' ) )
				->orderBy( 'title' )
				->get();

		if ( empty( $forms ) ) {
			return array();
		}

		$prepared = array();

		foreach ( $forms as $form ) {
			$fields                = json_decode( $form->form_fields, true );
			$fields                = isset( $fields['fields'] ) ? $fields['fields'] : array();
			$prepared[ $form->id ] = array(
				'name'   => $form->title,
				'fields' => $this->prepare_noptin_automation_rule_fields( $fields ),
			);
		}

		return $prepared;
	}

	/**
	 * Process form submission.
	 *
	 * @param int $entry_id The entry id.
	 * @param array $data The entry data.
	 * @param object $form The submitted form.
	 */
	public function process_form( $entry_id, $data, $form ) {

		if ( empty( $form->id ) ) {
			return;
		}

		$posted = $this->flatten_form_data( $data );

		do_action( 'noptin_fluentform_form_submitted', $form->id, $posted );
	}

	/**
	 * Flattens form data.
	 *
	 * @param array $data The form data.
	 * @param string $parent_key The parent key.
	 * @return array
	 */
	public function flatten_form_data( $data, $parent_key = '' ) {

		$form_data = array();

		foreach ( $data as $key => $value ) {

			if ( ! empty( $parent_key ) ) {
				$key = $parent_key . '.' . $key;
			}

			// Check if we have a scalar value...
			if ( is_scalar( $value ) ) {
				$form_data[ $key ] = $value;
				continue;
			}

			// ... or a numeric array.
			if ( is_array( $value ) && isset( $value[0] ) ) {
				$form_data[ $key ] = implode( ', ', $value );
				continue;
			}

			// Flatten nested arrays.
			if ( is_array( $value ) ) {
				$form_data = array_merge( $form_data, $this->flatten_form_data( $value, $key ) );
			}
		}

		return $form_data;
	}

	/**
	 * Prepares form fields.
	 *
	 * @param object[] $fields The form fields.
	 * @return array
	 */
	public function prepare_noptin_automation_rule_fields( $fields, $parent = array() ) {

		$prepared_fields = array();

		// Loop through all fields.
		foreach ( $fields as $form_field ) {

			if ( isset( $form_field['columns'] ) && ! empty( $form_field['columns'] ) ) {
				foreach ( $form_field['columns'] as $column ) {
					$prepared_fields = array_merge( $prepared_fields, $this->prepare_noptin_automation_rule_fields( $column['fields'], $parent ) );
				}
				continue;
			}

			$attribute_name = isset( $form_field['attributes']['name'] ) && ! empty( $form_field['attributes']['name'] ) ? $form_field['attributes']['name'] : '';
			$id             = isset( $parent['id'] ) ? $parent['id'] . '.' . $attribute_name : $attribute_name;
			$type           = isset( $form_field['element'] ) ? $form_field['element'] : '';
			$admin_label    = ! empty( $form_field['settings']['admin_field_label'] ) ? $form_field['settings']['admin_field_label'] : '';
			$label          = ! empty( $form_field['settings']['label'] ) ? $form_field['settings']['label'] : $admin_label;
			$label          = ! empty( $parent['label'] ) ? $parent['label'] . ' - ' . $label : $label;

			if ( ! empty( $form_field['fields'] ) ) {
				$prepared_fields = array_merge(
					$prepared_fields,
					$this->prepare_noptin_automation_rule_fields(
						$form_field['fields'],
						array(
							'id'    => $id,
							'label' => $label,
						)
					)
				);
				continue;
			}

			// Skip fields with no name.
			if ( empty( $label ) || empty( $id ) ) {
				continue;
			}

			if ( ! empty( $type ) && in_array( $type, $this->ignore_fields, true ) ) {
				continue;
			}

			$field = array(
				'description'       => $label,
				'conditional_logic' => 'string',
			);

			if ( 'input_number' === $type ) {
				$field['conditional_logic'] = 'number';
			}

			$options = $this->get_field_options( $form_field );

			if ( ! empty( $options ) ) {
				$field['options'] = $options;
			}

			$prepared_fields[ $id ] = $field;
		}

		return $prepared_fields;
	}

	/**
	 * Retrieves the field options.
	 *
	 * @param array $form_field The field.
	 * @return array
	 */
	public function get_field_options( $form_field ) {

		// Abort if field has no options.
		if ( empty( $form_field['settings']['advanced_options'] ) ) {
			return array();
		}

		return wp_list_pluck( $form_field['settings']['advanced_options'], 'label', 'value' );
	}
}
