<?php

namespace Hizzle\Noptin\Integrations\Gravity_Forms;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles integrations with Gravity Forms.
 *
 * @since 2.1.0
 */
class Main extends \Hizzle\Noptin\Integrations\Form_Integration {

	/**
	 * @var string
	 */
	public $slug = 'gravity_forms';

	/**
	 * @var string
	 */
	public $name = 'Gravity Forms';

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		// Process form submission.
		add_action( 'gform_entry_post_save', array( $this, 'process_form' ), 10, 2 );

		// Custom feed.
		add_action( 'gform_loaded', array( $this, 'register_feed' ) );
	}

	/**
	 * Retrieves all forms.
	 *
	 * @return array
	 */
	protected function get_forms() {

		// Get all forms.
		$all_forms = array_filter( \GFAPI::get_forms( null, false, 'title' ) );
		$prepared  = array();

		foreach ( $all_forms as $form ) {
			$prepared[ $form['id'] ] = array(
				'name'   => $form['title'],
				'fields' => $this->prepare_fields( $form['fields'] ),
			);
		}

		return $prepared;
	}

	/**
	 * Prepares form fields.
	 * @param \GF_Field[] $fields
	 * @return array
	 */
	private function prepare_fields( $fields, $should_return = 'array' ) {

		$prepared_fields = array();
		$static_fields   = array(
			'source_url'     => array(
				'description'       => __( 'Source URL', 'newsletter-optin-box' ),
				'conditional_logic' => 'string',
			),
			'ip'             => array(
				'description'       => __( 'IP Address', 'newsletter-optin-box' ),
				'conditional_logic' => 'string',
			),
			'currency'       => array(
				'description'       => __( 'Currency', 'newsletter-optin-box' ),
				'conditional_logic' => 'string',
			),
			'user_agent'     => array(
				'description'       => __( 'User Agent', 'newsletter-optin-box' ),
				'conditional_logic' => 'string',
			),
			'payment_amount' => array(
				'description'       => __( 'Payment Amount', 'newsletter-optin-box' ),
				'conditional_logic' => 'number',
			),
		);

		if ( 'id' !== $should_return ) {
			$prepared_fields = $static_fields;
		}

		// Loop through all fields.
		foreach ( $fields as $gravity_field ) {

			// Skip fields with no name.
			if ( empty( $gravity_field->label ) ) {
				continue;
			}

			$key = sanitize_title( $gravity_field->label );

			if ( ! empty( $gravity_field->type ) && ! in_array( $gravity_field->type, array( 'section', 'address', 'product' ), true ) ) {
				if ( 'id' === $should_return ) {
					$prepared_fields[ $key ] = 'consent' === $gravity_field->type ? $gravity_field->id . '.1' : $gravity_field->id;
				} else {
					$field = array(
						'description'       => $gravity_field->label,
						'conditional_logic' => 'number' === $gravity_field->type ? 'number' : 'string',
					);

					if ( ! empty( $gravity_field->choices ) && is_array( $gravity_field->choices ) ) {
						$field['options'] = wp_list_pluck( $gravity_field->choices, 'text', 'value' );
					}

					// Consent.
					if ( 'consent' === $gravity_field->type ) {
						$field['options'] = array(
							esc_html__( 'Checked', 'gravityforms' )      => esc_html__( 'Checked', 'gravityforms' ),
							esc_html__( 'Not Checked', 'gravityforms' )  => esc_html__( 'Not Checked', 'gravityforms' ),
						);
					}

					$prepared_fields[ $key ] = $field;
				}
			}

			// Fields with multiple inputs.
			if ( is_array( $gravity_field->inputs ) && ! in_array( $gravity_field->type, array( 'checkbox', 'consent', 'email' ), true ) ) {

				foreach ( $gravity_field->inputs as $input ) {

					$input_key = $key . '_' . sanitize_title( $input['label'] );

					if ( 'id' === $should_return ) {
						$prepared_fields[ $input_key ] = $input['id'];
						continue;
					}

					$field = array(
						'description'       => $gravity_field->label . ' (' . $input['label'] . ')',
						'conditional_logic' => 'string',
					);

					if ( ! empty( $input['choices'] ) && is_array( $input['choices'] ) ) {
						$field['options'] = wp_list_pluck( $input['choices'], 'text', 'value' );
					}

					$prepared_fields[ $input_key ] = $field;
				}
			}
		}

		return $prepared_fields;
	}

	/**
	 * Process form submission.
	 *
	 * @param array $entry The Entry Object currently being processed.
	 * @param array $form The Form Object currently being processed.
	 */
	public function process_form( $entry, $form ) {

		if ( 'spam' === $entry['status'] ) {
			return $entry;
		}

		$form_fields = $this->prepare_fields( $form['fields'], 'id' );
		$posted      = array(
			'payment_amount' => empty( $entry['payment_amount'] ) ? 0 : (float) $entry['payment_amount'],
			'ip'             => $entry['ip'],
			'currency'       => $entry['currency'],
			'user_agent'     => $entry['user_agent'],
		);
		$feed        = Feed::get_instance();

		foreach ( $form_fields as $key => $field_id ) {
			$posted[ $key ] = $feed->get_field_value( $form, $entry, $field_id );
		}

		// Trigger action.
		$this->process_form_submission( $form['id'], $posted );

		return $entry;
	}

	/**
	 * Registers gravity forms action.
	 *
	 * @since 1.5.5
	 */
	public function register_feed() {
		if ( function_exists( 'add_noptin_subscriber' ) ) {
			\GFAddOn::register( '\\Hizzle\\Noptin\\Integrations\\Gravity_Forms\\Feed' );
		}
	}
}
