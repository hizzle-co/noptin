<?php

namespace Hizzle\Noptin\Integrations\Contact_Form_7;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with Contact Form 7.
 *
 * @since 2.1.0
 */
class Main extends \Hizzle\Noptin\Integrations\Form_Integration {

	/**
	 * @var string
	 */
	public $slug = 'contact_form_7';

	/**
	 * @var string
	 */
	public $name = 'Contact Form 7';

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		// Custom action.
		if ( function_exists( 'add_noptin_subscriber' ) ) {
			// Register our custom panel.
			add_filter( 'wpcf7_editor_panels', array( $this, 'add_panel' ) );

			// Save panel settings.
			add_action( 'wpcf7_after_save', array( $this, 'save_settings' ) );
		}

		// Process form submission.
		add_action( 'wpcf7_submit', array( $this, 'process_form' ), 10, 2 );
	}

	/**
	 * Retrieves all forms.
	 *
	 * @return array
	 */
	protected function get_forms() {

		/** @var \WPCF7_ContactForm[] $all_forms */
		$all_forms = \WPCF7_ContactForm::find();
		$prepared  = array();
		$ignore    = array( 'quiz', 'submit' );

		// Loop through all forms.
		foreach ( $all_forms as $form ) {
			$fields = array(
				'conversion_page' => array(
					'description'       => 'Conversion Page',
					'conditional_logic' => 'string',
				),
			);

			foreach ( $form->scan_form_tags() as $tag ) {

				/** @var \WPCF7_FormTag $tag */

				// Abort if no name.
				if ( empty( $tag->name ) || in_array( $tag->basetype, $ignore, true ) ) {
					continue;
				}

				// Acceptance field.
				if ( 'acceptance' === $tag->basetype ) {
					$fields[ $tag->name ] = array(
						'description'       => empty( $tag->content ) ? $tag->name : $tag->content,
						'conditional_logic' => 'string',
						'options'           => array(
							'1' => 'Checked',
							'0' => 'Unchecked',
						),
					);
					continue;
				}

				$field = array(
					'description'       => $tag->name,
					'conditional_logic' => 'number' === $tag->basetype ? 'number' : 'string',
				);

				if ( is_array( $tag->raw_values ) && 1 < count( $tag->raw_values ) ) {
					$field['options'] = array_combine( $tag->raw_values, $tag->raw_values );
				}

				$fields[ $tag->name ] = $field;
			}

			$prepared[ $form->id() ] = array(
				'name'   => $form->title(),
				'fields' => $fields,
			);
		}

		return $prepared;
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
			'callback' => array( $this, 'render_panel_content' ),
		);

		return $panels;
	}

	/**
	 * Renders panel settings.
	 *
	 * @param \WPCF7_ContactForm $contact_form The contact form being edited.
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
		include plugin_dir_path( __FILE__ ) . 'settings.php';
	}

	/**
	 * Returns an array of map fields
	 */
	public function get_map_fields() {

		$map_fields = array(

			array(
				'name'  => 'GDPR_consent',
				'label' => __( 'GDPR Consent', 'newsletter-optin-box' ),
				'type'  => 'acceptance',
			),

		);

		foreach ( get_editable_noptin_subscriber_fields() as $key => $field ) {

			$map_fields[] = array(
				'name'  => $key,
				'label' => empty( $field['label'] ) ? $field['description'] : $field['label'],
				'type'  => 'email' === $key ? 'email' : null,
			);
		}

		return apply_filters( 'noptin_contact_form_7_map_fields', $map_fields );
	}

	/**
	 * Saves contact form settings.
	 *
	 * @param \WPCF7_ContactForm $contact_form The contact form being edited.
	 */
	public function save_settings( $contact_form ) {

		if ( empty( $_POST['noptin_settings'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$post_id = $contact_form->id();

		update_post_meta( $post_id, 'noptin_settings', noptin_clean( wp_unslash( $_POST['noptin_settings'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * @param \WPCF7_ContactForm $contact_form The contact form being edited.
	 * @param array $result The result of the submission.
	 */
	public function process_form( $contact_form, $result ) {

		// Check the submission status
		if ( empty( $result['status'] ) || ! in_array( $result['status'], array( 'mail_sent', 'mail_failed' ), true ) ) {
			return;
		}

		// Prepare args.
		$submission  = \WPCF7_Submission::get_instance();
		$posted_data = $submission->get_posted_data();

		$posted_data['conversion_page'] = $this->get_conversion_page( $posted_data );

		// Prepare acceptance fields as either 0 or 1.
		foreach ( $contact_form->scan_form_tags() as $tag ) {

			/** @var \WPCF7_FormTag $tag */

			// Acceptance field.
			if ( 'acceptance' === $tag->basetype ) {
				$posted_data[ $tag->name ] = empty( $posted_data[ $tag->name ] ) ? '0' : '1';
			}
		}

		// Maybe add subscriber.
		$this->maybe_add_subscriber( $contact_form, $posted_data );

		// Trigger action.
		$this->process_form_submission( $contact_form->id(), $posted_data );
	}

	/**
	 * @param \WPCF7_ContactForm $contact_form The contact form being edited.
	 * @param array $posted_data The posted data
	 */
	private function maybe_add_subscriber( $contact_form, $posted_data ) {

		// Get our settings for the form.
		$settings = get_post_meta( $contact_form->id(), 'noptin_settings', true );
		$settings = is_array( $settings ) ? $settings : array();

		// Retrieve field maps.
		$mapped_fields = isset( $settings['custom_fields'] ) ? $settings['custom_fields'] : array();

		// Prepare Noptin Fields.
		$noptin_fields = $this->map_fields( $posted_data, $mapped_fields );

		// Abort if newsletter checkbox was not checked.
		$conditional = isset( $mapped_fields['GDPR_consent'] ) ? $mapped_fields['GDPR_consent'] : '';
		if ( ! empty( $conditional ) && empty( $noptin_fields['GDPR_consent'] ) ) {
			return;
		}

		// We need an email.
		if ( empty( $noptin_fields['email'] ) ) {
			return;
		}

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
			'source'          => 'Contact Form 7',
			'conversion_page' => $this->get_conversion_page( $posted_data ),
		);

		// Add the subscriber's IP address.
		$address = noptin_get_user_ip();
		if ( ! empty( $address ) && '::1' !== $address ) {
			$subscriber['ip_address'] = $address;
		}

		// Add mapped fields.
		foreach ( $mapped_fields as $noptin => $cf7 ) {
			if ( isset( $posted_data[ $cf7 ] ) && '' !== $posted_data[ $cf7 ] ) {
				$subscriber[ $noptin ] = $posted_data[ $cf7 ];
			}
		}

		return $subscriber;
	}

	/**
	 * Fetches the conversion page.
	 *
	 * @param array $posted_data Posted data
	 *
	 * @return string
	 */
	private function get_conversion_page( $posted_data ) {

		if ( ! empty( $posted_data['_wpcf7_container_post'] ) ) {
			return get_permalink( $posted_data['_wpcf7_container_post'] );
		}

		if ( ! empty( $_REQUEST['referrer'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return esc_url_raw( $_REQUEST['referrer'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return '';
	}
}
