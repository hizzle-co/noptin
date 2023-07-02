<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
		add_action( 'wpforms_form_settings_panel_content', array( $this, 'settings_section_content' ), 20 );
		add_action( 'wpforms_process_complete', array( $this, 'add_subscriber' ), 10, 4 );

		// Load automation rule.
		if ( did_action( 'noptin_automation_rules_load' ) ) {
			$this->load_automation_rule( noptin()->automation_rules );
		} else {
			add_action( 'noptin_automation_rules_load', array( $this, 'load_automation_rule' ) );
		}
		add_filter( 'noptin_wpforms_forms', array( $this, 'filter_forms' ) );
	}

	/**
	 * Loads our automation rule.
	 *
	 * @param Noptin_Automation_Rules $rules The automation rules instance.
	 */
	public function load_automation_rule( $rules ) {
		$rules->add_trigger( new Noptin_Form_Submit_Trigger( 'wpforms', 'WPForms' ) );
	}

	/**
	 * Filters forms.
	 *
	 * @param array $forms An array of forms.
	 * @return array
	 */
	public function filter_forms( $forms ) {
		global $noptin_wpforms_forms;

		// Return cached forms.
		if ( is_array( $noptin_wpforms_forms ) ) {
			return array_replace( $forms, $noptin_wpforms_forms );
		}

		$noptin_wpforms_forms = array();

		/** @var WP_Post[] $all_forms */
		$all_forms = wpforms()->form->get(
			'',
			array(
				'orderby'        => 'title',
				'order'          => 'ASC',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		/** @var WP_Post[] $all_forms */
		$all_forms = is_array( $all_forms ) ? $all_forms : array();

		// Loop through all forms.
		foreach ( $all_forms as $form ) {

			// Pull and format the form data out of the form object.
			$form_data = ! empty( $form->post_content ) ? wpforms_decode( $form->post_content ) : '';

			if ( empty( $form_data ) ) {
				continue;
			}

			$noptin_wpforms_forms[ $form->ID ] = array(
				'name'   => $form->post_title,
				'fields' => $this->prepare_noptin_automation_rule_fields( $form_data['fields'] ),
			);
		}

		return array_replace( $forms, $noptin_wpforms_forms );
	}

	/**
     * Prepares form fields.
     *
     * @param array[] $fields The form fields.
     * @return array
     */
    public function prepare_noptin_automation_rule_fields( $fields ) {

        $prepared_fields      = array();
		$form_fields_disallow = array( 'divider', 'html', 'pagebreak', 'captcha' );

        // Loop through all fields.
        foreach ( $fields as $wpforms_field ) {

            if ( in_array( $wpforms_field['type'], $form_fields_disallow, true ) ) {
				continue;
			}

            $key = sanitize_title( $wpforms_field['label'] );

			$prepared_fields[ $key ] = array(
				'description'       => $wpforms_field['label'],
				'conditional_logic' => 'number' === $wpforms_field['type'] ? 'number' : 'string',
			);

			// Child fields.
			if ( 'name' === $wpforms_field['type'] ) {
				$child_fields = array(
					'first'  => __( 'First', 'newsletter-optin-box' ),
					'middle' => __( 'Middle', 'newsletter-optin-box' ),
					'last'   => __( 'Last', 'newsletter-optin-box' ),
				);
			} elseif ( 'address' === $wpforms_field['type'] ) {
				$child_fields = array(
					'address' => __( 'Address', 'newsletter-optin-box' ),
					'city'    => __( 'City', 'newsletter-optin-box' ),
					'state'   => __( 'State', 'newsletter-optin-box' ),
					'zip'     => __( 'Zip', 'newsletter-optin-box' ),
					'country' => __( 'Country', 'newsletter-optin-box' ),
				);
			} else {
				$child_fields = array();
			}

			foreach ( $child_fields as $child_key => $child_label ) {
				$child_key = $key . '.' . $child_key;

				$prepared_fields[ $child_key ] = array(
					'description'       => $wpforms_field['label'] . ' (' . $child_label . ')',
					'conditional_logic' => 'string',
				);
			}
        }

        return $prepared_fields;
    }

	/**
	 * Add Settings Section
	 *
	 * @param array $sections The current settings sections.
	 * @return array
	 */
	public function settings_section( $sections ) {
		$sections['noptin'] = 'Noptin';
		return $sections;
	}

	/**
	 * Noptin Settings Content
	 *
	 * @param stdClass $instance The form instance.
	 * @return void
	 */
	public function settings_section_content( $instance ) {

		?>
		<div class="wpforms-panel-content-section wpforms-panel-content-section-noptin">
			<div class="wpforms-panel-content-section-title">Noptin</div>

				<?php

					wpforms_panel_field(
						'checkbox',
						'settings',
						'enable_noptin',
						$instance->form_data,
						__( 'Enable Noptin Subscriptions', 'newsletter-optin-box' )
					);

					do_action( 'noptin_wp_forms_before_map_fields_section', $instance );
				?>

				<div class="wpforms-map-noptin-fields wpforms-builder-settings-block">
					<div class="wpforms-builder-settings-block-header">
						<span><?php esc_html_e( 'Map Fields', 'newsletter-optin-box' ); ?> <a href="https://noptin.com/guide/integrations/wpforms" target="_blank"><?php esc_html_e( 'Learn More!', 'newsletter-optin-box' ); ?></a></span>
					</div>
					<div class="wpforms-builder-settings-block-content">
						<?php

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
						?>
					</div>
				</div>

				<?php do_action( 'noptin_wp_forms_after_map_fields_section', $instance ); ?>
			</div>
		</div>

		<?php

	}

	/**
	 * Save subscriptions
	 *
	 * @param array  $fields    List of fields.
	 * @param array  $entry     Submitted form entry.
	 * @param array  $form_data Form data and settings.
	 * @param int    $entry_id  Saved entry id.
	 */
	public function add_subscriber( $fields, $entry, $form_data, $entry_id ) {

		$posted = array();

		foreach ( $fields as $field ) {
			$key            = sanitize_title( $field['name'] );
			$posted[ $key ] = $field['value'];

			foreach ( $field as $child_key => $value ) {
				$child_key            = $key . '.' . $child_key;
				$posted[ $child_key ] = $value;
			}
		}

		// Fire an action before we process the form.
		do_action( 'noptin_wpforms_form_submitted', $form_data['id'], $posted );

		// Check that the form was configured for email subscriptions.
		if ( empty( $form_data['settings']['enable_noptin'] ) || '1' !== $form_data['settings']['enable_noptin'] ) {
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
			'source' => 'WPForms',
			'email'  => sanitize_email( $fields[ $email_field_id ]['value'] ),
		);

		// Add the subscriber's IP address.
		$address = noptin_get_user_ip();
		if ( ! empty( $address ) && '::1' !== $address ) {
			$noptin_fields['ip_address'] = $address;
		}// TODO: Send confirmation links for existing unconfirmed subscribers.

		// Referral page.
		if ( ! empty( $_REQUEST['referrer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$noptin_fields['conversion_page'] = esc_url_raw( $_REQUEST['referrer'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
				if ( isset( $form_data['settings'][ 'noptin_field_' . $custom_field['merge_tag'] ] ) ) {
					$form_field                                  = $form_data['settings'][ 'noptin_field_' . $custom_field['merge_tag'] ];
					$noptin_fields[ $custom_field['merge_tag'] ] = noptin_clean( $fields[ $form_field ]['value'] );
				}
			}
		}

		$noptin_fields = apply_filters( 'noptin_wpforms_integration_new_subscriber_fields', array_filter( $noptin_fields ) );

		add_noptin_subscriber( $noptin_fields );

	}

}
