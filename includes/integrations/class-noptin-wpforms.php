<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Handles integrations with WPForms
 *
 * @since       1.2.6
 */
class Noptin_WPForms {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'wpforms_builder_settings_sections', array( $this, 'settings_section' ), 20, 2 );
        add_filter( 'wpforms_form_settings_panel_content', array( $this, 'settings_section_content' ), 20 );
        add_action( 'wpforms_process_complete', array( $this, 'add_subscriber' ), 10, 4 );
	}

	/**
     * Add Settings Section
     *
	 * @param array $sections The current settings sections.
	 * @return array
     */
    function settings_section( $sections ) {
        $sections['noptin'] = 'Noptin';
        return $sections;
	}

	/**
     * Noptin Settings Content
     *
	 * @param stdClass $instance The form instance.
	 * @return void
     */
    function settings_section_content( $instance ) {
        echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-noptin">';
        echo '<div class="wpforms-panel-content-section-title">Noptin</div>';

        wpforms_panel_field(
            'checkbox',
            'settings',
            'enable_noptin',
            $instance->form_data,
            __( 'Enable Noptin Subscriptions', 'newsletter-optin-box' )
		);

		do_action( 'noptin_wp_forms_before_map_fields_section', $instance );
		echo '<div class="wpforms-map-noptin-fields wpforms-builder-settings-block">';
		echo '<div class="wpforms-builder-settings-block-header">';
		echo '<span>' . __( 'Map Fields', 'newsletter-optin-box' ) . ' <a href="https://noptin.com/guide/integrations/wpforms" target="_blank">' . __( 'Learn More!', 'newsletter-optin-box' ) . '</a></span>';
		echo '</div><div class="wpforms-builder-settings-block-content">';

        wpforms_panel_field(
            'select',
            'settings',
            'noptin_field_email',
            $instance->form_data,
            __( 'Email Address', 'newsletter-optin-box' ),
            array(
                'field_map'   => array( 'email' ),
                'placeholder' => __( '-- Map Field --', 'newsletter-optin-box' ),
            )
		);

		wpforms_panel_field(
            'select',
            'settings',
            'noptin_field_name',
            $instance->form_data,
            __( 'Subscriber Name (Optional)', 'newsletter-optin-box' ),
            array(
                'field_map'   => array( 'text', 'name' ),
                'placeholder' => __( '-- Map Field --', 'newsletter-optin-box' ),
            )
        );

		wpforms_panel_field(
            'select',
            'settings',
            'noptin_field_gdpr',
            $instance->form_data,
            __( 'GDPR checkbox (Optional)', 'newsletter-optin-box' ),
            array(
                'field_map'   => array( 'checkbox', 'gdpr-checkbox' ),
				'placeholder' => __( '-- Map Field --', 'newsletter-optin-box' ),
				'tooltip'     => __( 'If mapped, only users who consent will join your newsletter.', 'newsletter-optin-box' ),
            )
		);

		foreach ( get_noptin_custom_fields() as $custom_field ) {

            if ( ! $custom_field['predefined'] ) {
				wpforms_panel_field(
					'select',
					'settings',
					'noptin_field_' . $custom_field['merge_tag'],
					$instance->form_data,
					$custom_field['label'],
					array(
						'field_map'   => array( $custom_field['type'] ),
						'placeholder' => __( '-- Map Field --', 'newsletter-optin-box' ),
					)
				);
            }

		}

		do_action( 'noptin_wp_forms_map_fields_section', $instance );
		echo '</div>';

		do_action( 'noptin_wp_forms_after_map_fields_section', $instance );
        echo '</div>';
	}

	/**
     * Save subscriptions
     *
	 * @param array  $fields    List of fields.
	 * @param array  $entry     Submitted form entry.
	 * @param array  $form_data Form data and settings.
	 * @param int    $entry_id  Saved entry id.
     */
    function add_subscriber( $fields, $entry, $form_data, $entry_id ) {

		// Check that the form was configured for email subscriptions.
		if ( empty( $form_data['settings']['enable_noptin'] ) || '1' != $form_data['settings']['enable_noptin'] ) {
			return;
		}

		// Return early if no email.
        $email_field_id = $form_data['settings']['noptin_field_email'];
		if ( ! isset( $email_field_id ) || empty( $fields[ $email_field_id ]['value'] ) ) {
			return;
		}

		// Or no consent.
		$consent_field_id = $form_data['settings']['noptin_field_gdpr'];
		if ( '' !== $consent_field_id && empty( $fields[ $consent_field_id ]['value'] ) ) {
			return;
		}

		// Prepare Noptin Fields.
		$noptin_fields = array(
			'_subscriber_via' => 'WPForms',
			'email'           => sanitize_email( $fields[ $email_field_id ]['value'] ),
		);

		// Add the subscriber's IP address.
		$address = noptin_get_user_ip();
		if ( ! empty( $address ) && '::1' !== $address ) {
			$subscriber['ip_address'] = $address;
		}

		// Referral page.
		if ( ! empty( $_REQUEST['referrer'] ) ) {
            $subscriber['conversion_page'] = esc_url_raw( $_REQUEST['referrer'] );
        }

		// Maybe include the subscriber name...
		$name_field_id = $form_data['settings']['noptin_field_name'];
		if ( is_numeric( $name_field_id ) ) {
			$noptin_fields['name'] = noptin_clean( $fields[ $name_field_id ]['value'] );
		}

		// ... and their GDPR status.
		if ( is_numeric( $consent_field_id ) && ! empty( $fields[ $consent_field_id ]['value'] ) ) {
			$noptin_fields['GDPR_consent'] = 1;
		}

		// And special fields.
		foreach ( get_noptin_custom_fields() as $custom_field ) {

            if ( ! $custom_field['predefined'] ) {
				if ( isset( $form_data['settings']['noptin_field_' . $custom_field['merge_tag']] ) ) {
					$form_field                                  = $form_data['settings']['noptin_field_' . $custom_field['merge_tag']];
					$noptin_fields[ $custom_field['merge_tag'] ] = noptin_clean( $fields[ $form_field ]['value'] );
				}
            }

		}

		$noptin_fields['integration_data'] = compact( 'fields', 'entry', 'form_data', 'entry_id' );

		$noptin_fields = apply_filters( 'noptin_wpforms_integration_new_subscriber_fields', array_filter( $noptin_fields ) );

		add_noptin_subscriber( $noptin_fields );

    }

}
