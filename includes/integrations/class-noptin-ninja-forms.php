<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'NF_Abstracts_Action' ) ) {
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
	 * Returns an array of map fields
	 */
	public function get_map_fields() {

		$map_fields = array(

            array(
                'name'        => 'name',
				'label'       => __( 'Subscriber Name', 'newsletter-optin-box' ),
				'placeholder' => __( "The subscriber's name", 'newsletter-optin-box' ),
            ),

            array(
                'name'        => 'email',
				'label'       => __( 'Subscriber Email', 'newsletter-optin-box' ),
				'placeholder' => __( "The subscriber's email address", 'newsletter-optin-box' ),
            ),

            array(
                'name'  => 'GDPR_consent',
				'label' => __( 'GDPR Consent', 'newsletter-optin-box' ),
				'help'  => __( 'Optional. If set, users will only join your newsletter if they consent.', 'newsletter-optin-box' ),
			),

			array(
                'name'  => 'conversion_page',
                'label' => __( 'Conversion Page', 'newsletter-optin-box' ),
            ),

        );

		foreach ( get_noptin_custom_fields() as $custom_field ) {

            if ( ! $custom_field['predefined'] ) {
                $map_fields[] = array(
                    'name'  => $custom_field['merge_tag'],
                    'label' => $custom_field['label'],
                );
            }
		}

		return apply_filters( 'noptin_ninja_forms_map_fields', $map_fields );

	}

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->_nicename = 'Noptin';

		/*
		 * Settings
		 */
		$map_fields = $this->get_map_fields();

		foreach ( $map_fields as $field ) {
			$name = 'noptin_' . $field['name'];

			$this->_settings[ $name ] = array(

				'name'	         => $name,
				'type'	         => isset( $field['type'] ) ? $field['type'] : 'textbox',
				'label'	         => $field['label'],
				'width'	         => isset( $field['width'] ) ? $field['width'] : 'full',
				'group'	         => isset( $field['group'] ) ? $field['group'] : 'primary',
				'value'	         => isset( $field['value'] ) ? $field['value'] : '',
				'placeholder'    => isset( $field['placeholder'] ) ? $field['placeholder'] : '',
				'help'           => isset( $field['help'] ) ? $field['help'] : '',
				'use_merge_tags' => isset( $field['use_merge_tags'] ) ? $field['use_merge_tags'] : true,

			);
		}

		$this->_settings = apply_filters( 'noptin_ninja_forms_integration_action_settings', $this->_settings );

	}

	/**
	 * Process the action
	 *
	 * @param array $action_settings
	 * @param int   $form_id
	 * @param array $data
	 *
	 * @return array
	 */
	public function process( $action_settings, $form_id, $data ) {

		// All subscribers need an email address.
		if ( ! is_email( $action_settings['noptin_email'] ) ) {
			return $data;
		}

		// Abort if marketing consent was not given.
		if ( ! empty( $action_settings['noptin_GDPR_consent'] ) && __( 'Unchecked', 'newsletter-optin-box' ) === $action_settings['noptin_GDPR_consent'] ) {
			return $data;
		}

		// Prepare Noptin Fields.
		$noptin_fields = $this->map_fields( $action_settings );

		// Filter the subscriber fields.
		$noptin_fields = apply_filters( 'noptin_ninja_forms_integration_new_subscriber_fields', $noptin_fields );

		// Register the subscriber.
		add_noptin_subscriber( $noptin_fields );

		// Return subscriber data.
		return $data;
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	private function map_fields( $settings ) {

		// Prepare subscriber details.
		$subscriber = array(
            'source' => 'Ninja Forms',
        );

        // Add the subscriber's IP address.
		$address = noptin_get_user_ip();
		if ( ! empty( $address ) && '::1' !== $address ) {
			$subscriber['ip_address'] = $address;
		}

		// Add map fields.
		$map_fields = wp_list_pluck( $this->get_map_fields(), 'name' );

		foreach ( $map_fields as $field ) {
			if ( isset( $settings[ "noptin_$field" ] ) ) {
				$subscriber[ $field ] = $settings[ "noptin_$field" ];
			}
		}

		return $subscriber;
	}

}
