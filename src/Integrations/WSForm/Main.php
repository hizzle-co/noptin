<?php

namespace Hizzle\Noptin\Integrations\WSForm;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with WS Forms
 *
 * @since 2.1.0
 */
class Main extends \Hizzle\Noptin\Integrations\Form_Integration {

	/**
	 * @var string
	 */
	public $slug = 'ws_form';

	/**
	 * @var string
	 */
	public $name = 'WS Form';

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		// Process submission.
		add_action( 'wsf_submit_post_complete', array( $this, 'process_form' ) );
	}

	/**
	 * Retrieves all forms.
	 *
	 * @return array
	 */
	protected function get_forms() {
		$forms = array();

		// Loop through all forms.
		foreach ( wsf_form_get_all() as $form ) {
			/** @var \WS_Form_Form $form */
			$form = wsf_form_get_object( $form['id'] );

			$forms[ $form->id ] = array(
				'name'   => $form->label,
				'fields' => $this->prepare_noptin_automation_rule_fields( wsf_form_get_fields( $form ) ),
			);
		}

		return $forms;
	}

	/**
	 * @param \WS_Form_Submit $submission The submission.
	 */
	public function process_form( $submission ) {

		$fields      = wsf_form_get_fields( $submission->form_object );
		$form_fields = $this->prepare_noptin_automation_rule_fields( $fields, 'id' );
		$posted      = array(
			'submission_id'    => $submission->id,
			'submission_hash'  => $submission->hash,
			'submission_token' => $submission->token,
		);

		foreach ( $form_fields as $key => $field_id ) {
			$value          = wsf_submit_get_value( $submission, WS_FORM_FIELD_PREFIX . $field_id );
			$posted[ $key ] = is_array( $value ) ? implode( ', ', $value ) : $value;
		}

		$this->process_form_submission( $submission->form_id, $posted );
	}

	/**
	 * Prepares form fields.
	 *
	 * @param \WS_Form_Field[] $fields The form fields.
	 * @param string $to_return Either array or id.
	 * @return array
	 */
	public function prepare_noptin_automation_rule_fields( $fields, $to_return = 'array' ) {

		$prepared_fields = array();
		$skip            = array( 'submit', 'spacer', 'note', 'divider', 'reset', 'tab_next', 'tab_previous', 'turnstile', 'hcaptcha', 'recaptcha' );

		// Loop through all fields.
		foreach ( $fields as $ws_field ) {

			// Skip fields with no name.
			if ( empty( $ws_field->label ) ) {
				continue;
			}

			if ( empty( $ws_field->type ) || in_array( $ws_field->type, $skip, true ) ) {
				continue;
			}

			$key = sanitize_title( $ws_field->label );

			if ( 'id' === $to_return ) {
				$prepared_fields[ $key ] = $ws_field->id;
			} else {
				$field = array(
					'description'       => $ws_field->label,
					'conditional_logic' => 'number' === $ws_field->type ? 'number' : 'string',
				);

				try {
					$options = wsf_field_get_datagrid( $ws_field );

					if ( ! empty( $options->groups ) ) {
						$field['options'] = array();

						foreach ( $options->groups as $group ) {
							if ( ! empty( $group->rows ) ) {
								foreach ( $group->rows as $row ) {
									$value                      = isset( $row->data[1] ) ? $row->data[1] : $row->data[0];
									$field['options'][ $value ] = $row->data[0];
								}
							}
						}
					}
				} catch ( \Exception $e ) {} // phpcs:ignore

				$prepared_fields[ $key ] = $field;
			}
		}

		return $prepared_fields;
	}
}
