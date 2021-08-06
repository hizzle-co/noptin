<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Handles integrations with Contact Form 7
 *
 * @since       1.3.3
 */
class Noptin_Contact_Form_7 {

	/**
	 * Constructor
	 */
	public function __construct() {

		// Register our custom panel.
		add_filter( 'wpcf7_editor_panels', array( $this, 'add_panel' ) );

		// Save panel settings.
		add_action( 'wpcf7_after_save', array( $this, 'save_settings' ) );

		// Save subscriber.
        add_action( 'wpcf7_submit', array( $this, 'process_form' ), 10, 2 );

	}

	/**
	 * Registers our custom panel.
	 *
	 * @param array $panels An array of available panels.
	 * @return array
	 */
	public function add_panel( $panels ) {

        $panels['noptin'] = array(
            'title'    => 'Noptin',
            'callback' => array( $this, 'render_panel_content' )
        );

        return $panels;
	}

	/**
	 * Renders panel settings.
	 *
     * @param WPCF7_ContactForm $contact_form The contact form being edited.
     */
    public function render_panel_content( $contact_form ) {

		// Get the id of the form.
        $form_id = $contact_form->id();

		// Get our settings for the form.
		$settings = get_post_meta( $form_id, 'noptin_settings', true );
		$settings = is_array( $settings ) ? $settings : array();

		// Our custom fields.
        $custom_fields = $this->get_map_fields();

		// Load the settings template.
		get_noptin_template( 'contact-form-7-settings.php', compact( 'contact_form', 'settings', 'custom_fields' ) );

	}

	/**
	 * Returns an array of map fields
	 */
	public function get_map_fields() {

		$map_fields = array(

            array(
                'name'  => 'name',
				'label' => __( 'Subscriber Name', 'newsletter-optin-box' ),
				'type'  => 'text',
            ),

            array(
                'name'  => 'email',
				'label' => __( 'Subscriber Email', 'newsletter-optin-box' ),
				'type'  => 'email',
            ),

            array(
                'name'  => 'GDPR_consent',
				'label' => __( 'GDPR Consent', 'newsletter-optin-box' ),
				'type'  => 'acceptance',
			)

        );

		foreach ( get_noptin_custom_fields() as $custom_field ) {

            if ( ! $custom_field['predefined'] ) {
                $map_fields[] = array(
                    'name'    => $custom_field['merge_tag'],
                    'label'   => $custom_field['label'],
                );
            }

		}

		return apply_filters( 'noptin_contact_form_7_map_fields', $map_fields );

	}

	/**
	 * Saves contact form settings.
	 *
     * @param WPCF7_ContactForm $contact_form The contact form being edited.
     */
    public function save_settings( $contact_form ) {

        if ( empty( $_POST ) ) {
			return;
		}

        $post_id = $contact_form->id();

        update_post_meta( $post_id, 'noptin_settings', noptin_clean( wp_unslash( $_POST['noptin_settings'] ) ) );
	}

	/**
     * @param WPCF7_ContactForm $contact_form The contact form being edited.
	 * @param array $result The result of the submission.
     */
    public function process_form( $contact_form, $result ) {

		// Check if we're in demo mode.
		if ( $contact_form->in_demo_mode() ) {
			return;
		}

		// Check the submission status
		if ( empty( $result['status'] ) || ! in_array( $result['status'], array( 'mail_sent', 'mail_failed' ), true ) ) {
			return;
		}

		// Prepare args.
        $submission  = WPCF7_Submission::get_instance();
        $posted_data = $submission->get_posted_data();

        // Get our settings for the form.
		$settings    = get_post_meta( $contact_form->id(), 'noptin_settings', true );
		$settings    = is_array( $settings ) ? $settings : array();

		// Retrieve field maps.
		$mapped_fields = isset( $settings['custom_fields'] ) ? $settings['custom_fields'] : array();

		// Prepare Noptin Fields.
		$noptin_fields = $this->map_fields( $posted_data, $mapped_fields );

		// Abort if newsletter checkbox was not checked.
		$conditional = isset( $mapped_fields['GDPR_consent'] ) ? $mapped_fields['GDPR_consent'] : '';
		if ( ! empty( $conditional ) && empty( $noptin_fields[ 'GDPR_consent' ] ) ) {
			return;
		}
	
		// We need an email.
		if ( empty( $noptin_fields['email'] ) ) {
			return;
		}

		// Add integration data.
		$noptin_fields['integration_data'] = compact( 'contact_form', 'posted_data' );

		// Filter the subscriber fields.
		$noptin_fields = apply_filters( 'noptin_contact_form_7_integration_new_subscriber_fields', $noptin_fields, $this );

		// Register the subscriber.
		add_noptin_subscriber( $noptin_fields );

	}

	/**
	 * Maps custom fields.
	 *
	 * @param array $posted_data Posted data
	 * @param array $mapped_fields Fields to map
	 *
	 * @return array
	 */
	private function map_fields( $posted_data, $mapped_fields ) {

		// Prepare subscriber details.
		$subscriber = array(
            '_subscriber_via' => 'Contact Form 7',
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

		if ( ! empty( $posted_data['_wpcf7_container_post'] ) ) {
            $subscriber['conversion_page'] = get_permalink( $posted_data['_wpcf7_container_post'] );
		}

		// Add mapped fields.
		foreach ( $mapped_fields as $noptin => $cf7 ) {
			if ( isset( $posted_data[ $cf7 ] ) && '' !== $posted_data[ $cf7 ] ) {
				$subscriber[ $noptin ] = $posted_data[ $cf7 ];
			}
		}

		return $subscriber;
	}

}
