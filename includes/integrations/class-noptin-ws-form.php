<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with WS Forms
 *
 * @since 1.10.3
 */
class Noptin_WS_Form {

	/**
	 * Constructor
	 */
	public function __construct() {

		// Save subscriber.
        add_action( 'wsf_submit_post_complete', array( $this, 'process_form' ) );

		// Load automation rule.
		if ( did_action( 'noptin_automation_rules_load' ) ) {
			$this->load_automation_rule( noptin()->automation_rules );
		} else {
			add_action( 'noptin_automation_rules_load', array( $this, 'load_automation_rule' ) );
		}
		add_filter( 'noptin_ws_form_forms', array( $this, 'filter_forms' ) );
	}

	/**
	 * Loads our automation rule.
	 *
	 * @param Noptin_Automation_Rules $rules The automation rules instance.
	 */
	public function load_automation_rule( $rules ) {
		$rules->add_trigger( new Noptin_Form_Submit_Trigger( 'ws_form', 'WS Form' ) );
	}

	/**
	 * Filters forms.
	 *
	 * @param array $forms An array of forms.
	 * @return array
	 */
	public function filter_forms( $forms ) {
		global $noptin_ws_form_forms;

		// Return cached forms.
		if ( is_array( $noptin_ws_form_forms ) ) {
			return array_replace( $forms, $noptin_ws_form_forms );
		}

		$noptin_ws_form_forms = array();

		// Loop through all forms.
		foreach ( wsf_form_get_all() as $form ) {
			/** @var WS_Form_Form $form */
			$form   = wsf_form_get_object( $form['id'] );

			$noptin_ws_form_forms[ $form->id ] = array(
				'name'   => $form->label,
				'fields' => $this->prepare_noptin_automation_rule_fields( wsf_form_get_fields( $form ) ),
			);
		}

		return array_replace( $forms, $noptin_ws_form_forms );
	}

	/**
     * @param WS_Form_Submit $submission The submission.
     */
    public function process_form( $submission ) {

		$fields      = wsf_form_get_fields( $submission->form_object );
		$form_fields = $this->prepare_noptin_automation_rule_fields( $fields, 'id' );
		$posted      = array();

        foreach ( $form_fields as $key => $field_id ) {
			$value          = wsf_submit_get_value( $submission, WS_FORM_FIELD_PREFIX . $field_id );
            $posted[ $key ] = is_array( $value ) ? implode( ', ', $value ) : $value;
        }

        do_action( 'noptin_ws_form_form_submitted', $submission->form_id, $posted );
	}

	/**
     * Prepares form fields.
     *
     * @param objects[] $fields The form fields.
     * @param string $return Either array or id.
     * @return array
     */
    public function prepare_noptin_automation_rule_fields( $fields, $return = 'array' ) {

        $prepared_fields = array();

        // Loop through all fields.
        foreach ( $fields as $ws_field ) {

            // Skip fields with no name.
            if ( empty( $ws_field->label ) ) {
                continue;
            }

			if ( empty( $ws_field->type ) || 'submit' === $ws_field->type ) {
				continue;
			}

            $key = sanitize_title( $ws_field->label );

            if ( 'id' === $return ) {
                $prepared_fields[ $key ] = $ws_field->id;
            } else {
                $field = array(
                    'description'       => $ws_field->label,
                    'conditional_logic' => 'number' === $ws_field->type ? 'number' : 'string',
                );

                $prepared_fields[ $key ] = $field;
            }
        }

        return $prepared_fields;
    }
}
