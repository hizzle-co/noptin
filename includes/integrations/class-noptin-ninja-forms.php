<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'NF_Abstracts_Action' )  ) {
	die;
}

/**
 * Handles integrations with Ninja Forms
 *
 * @since       1.2.6
 */
class Noptin_Ninja_Forms extends NF_Abstracts_Action {

	/**
	 * @var string
	 */
	protected $_name = 'noptin';

	/**
	 * @var array
	 */
	protected $_tags = array( 'noptin', 'newsletter', 'email' );

	/**
	 * @var int
	 */
	protected $_transient_expiration = MINUTE_IN_SECONDS;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->_nicename = __( 'Noptin', 'newsletter-optin-box' );

		$this->_settings = apply_filters( 'noptin_ninja_forms_integration_action_settings', $this->_settings );

	}

	/**
	 * Get lists.
	 */
	public function get_lists() {
		return array();
	}

	/**
	 * Process the action
	 *
	 * @param array $action_id
	 * @param int   $form_id
	 * @param array $data
	 *
	 * @return array
	 */
	public function process( $action_id, $form_id, $data ) {

		// Ensure the form has fields.
		if ( empty( $data['fields'] ) ) {
			return $data;
		}

		// Prepare Noptin Fields.
		$noptin_fields = array(
			'_subscriber_via' => 'ninja_forms',
			'ninja_form_id'   => $form_id,
		);

		// Take care of special fields.
		$mappings = array(
			'firstname' => 'first_name',
			'lastname'  => 'last_name',
			'name'      => 'name',
			'email'     => 'email',
		);

		// Process each field separately.
		foreach ( $data['fields'] as $field ) {

			// Ignore submit buttons and fields that do not have a value.
			if ( $field['type'] === 'submit' || $field['value'] === '' ) {
				continue;
			}

			$value = $field['value'];

			// Convert checkboxes to yes/no values.
			if ( $field['type'] === 'checkbox' ) {

				if ( empty( $value ) ) {
					$value = __( 'No', 'newsletter-optin-box' );
				} else {
					$value = __( 'Yes', 'newsletter-optin-box' );
				}
			}

			// Map to a special field or save as is.
			if ( isset( $mappings[ $field['type'] ] ) ) {
				$noptin_fields[ $mappings[ $field['type'] ] ] = $value;
			} else {
				$noptin_fields[ $field['label'] ] = $value;
			}

		}

		$noptin_fields['integration_data'] = $data;

		$noptin_fields = apply_filters( 'noptin_ninja_forms_integration_new_subscriber_fields', $noptin_fields );

		add_noptin_subscriber( $noptin_fields );

		return $data;
	}


}

$ninja_forms = Ninja_Forms::instance();
$ninja_forms->actions['noptin'] = new Noptin_Ninja_Forms();
