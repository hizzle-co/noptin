<?php

namespace Hizzle\Noptin\Integrations\Fluent_Forms;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles integrations with Fluent Forms.
 *
 * @since 2.1.0
 */
class Main extends \Hizzle\Noptin\Integrations\Form_Integration {

	/**
	 * @var string
	 */
	public $slug = 'fluentform';

	/**
	 * @var string
	 */
	public $name = 'Fluent Forms';

	private $ignore_fields = array(
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

		parent::__construct();

		// Process form submission.
		add_action( 'fluentform_submission_inserted', array( $this, 'process_form' ), 10, 3 );
	}

	/**
	 * Retrieves all forms.
	 *
	 * @return array
	 */
	protected function get_forms() {

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
				'fields' => array_merge(
					$this->prepare_fields( $fields ),
					array(
						'_wp_http_referer' => array(
							'description'       => 'Referrer',
							'conditional_logic' => 'string',
						),
					)
				),
			);
		}

		return $prepared;
	}

	/**
	 * Prepares form fields.
	 *
	 * @param array[] $fields The form fields.
	 * @return array
	 */
	private function prepare_fields( $fields, $parent = array() ) {
		$prepared = array();

		// Loop through all fields.
		foreach ( $fields as $form_field ) {

			if ( isset( $form_field['columns'] ) && ! empty( $form_field['columns'] ) ) {
				foreach ( $form_field['columns'] as $column ) {
					$prepared = array_merge( $prepared, $this->prepare_fields( $column['fields'], $parent ) );
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
				$prepared = array_merge(
					$prepared,
					$this->prepare_fields(
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

			$prepared[ $id ] = array(
				'description'       => $label,
				'conditional_logic' => 'input_number' === $type ? 'number' : 'string',
			);

			if ( ! empty( $form_field['settings']['advanced_options'] ) ) {
				$prepared[ $id ]['options'] = wp_list_pluck(
					$form_field['settings']['advanced_options'],
					'label',
					'value'
				);
			}
		}

		return $prepared;
	}

	/**
	 * Process form submissions.
	 *
	 * @param int $entry_id The entry id.
	 * @param array $data The entry data.
	 * @param object $form The submitted form.
	 */
	public function process_form( $entry_id, $data, $form ) {

		// Trigger action.
		if ( ! empty( $form->id ) ) {
			$this->process_form_submission( $form->id, $this->flatten_form_data( $data ) );
		}
	}

	/**
	 * Flattens form data.
	 *
	 * @param array $data The form data.
	 * @param string $parent_key The parent key.
	 * @return array
	 */
	private function flatten_form_data( $data, $parent_key = '' ) {

		$form_data = array();

		foreach ( $data as $key => $value ) {

			// Add home_url to the referrer if it's empty.
			if ( '_wp_http_referer' === $key ) {
				$value = 0 === strpos( $value, 'http' ) ? $value : home_url( $value );
			}

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
}
