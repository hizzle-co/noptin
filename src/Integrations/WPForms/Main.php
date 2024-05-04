<?php

namespace Hizzle\Noptin\Integrations\WPForms;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles integrations with WPForms.
 *
 * @since 2.1.0
 */
class Main extends \Hizzle\Noptin\Integrations\Form_Integration {

	/**
	 * @var string
	 */
	public $slug = 'wpforms';

	/**
	 * @var string
	 */
	public $name = 'WPForms';

	/**
	 * @var array
	 */
	public $multiple = array(
		'checkbox',
		'gdpr-checkbox',
		'payment-checkbox',
		'payment-multiple',
		'payment-select',
		'select',
	);

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		// Process form submission.
		add_action( 'wpforms_process_complete', array( $this, 'process_form' ), 10, 4 );

		// Custom action.
		if ( function_exists( 'add_noptin_subscriber' ) ) {
			add_filter( 'wpforms_builder_settings_sections', array( $this, 'settings_section' ) );
			add_action( 'wpforms_form_settings_panel_content', array( $this, 'settings_section_content' ), 20 );
		}
	}

	/**
	 * Retrieves all forms.
	 *
	 * @return array
	 */
	protected function get_forms() {

		// Get all forms.
		/** @var \WP_Post[] $all_forms */
		$all_forms = wpforms()->form->get(
			'',
			array(
				'orderby'        => 'title',
				'order'          => 'ASC',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);
		$prepared  = array();

		// Loop through all forms.
		foreach ( $all_forms as $form ) {

			// Pull and format the form data out of the form object.
			$form_data = ! empty( $form->post_content ) ? wpforms_decode( $form->post_content ) : '';

			if ( empty( $form_data ) ) {
				continue;
			}

			$prepared[ $form->ID ] = array(
				'name'   => $form->post_title,
				'fields' => $this->prepare_fields( $form_data['fields'] ),
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
	private function prepare_fields( $fields ) {

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
			} elseif ( 'payment-checkbox' === $wpforms_field['type'] ) {
				$child_fields = array(
					'value_choice' => __( 'Chosen Option', 'newsletter-optin-box' ),
					'amount'       => __( 'Chosen Amount', 'newsletter-optin-box' ),
					'currency'     => __( 'Chosen Currency', 'newsletter-optin-box' ),
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
	 * Process form submissions.
	 *
	 * @param array  $fields    List of fields.
	 * @param array  $entry     Submitted form entry.
	 * @param array  $form_data Form data and settings.
	 * @param int    $entry_id  Saved entry id.
	 */
	public function process_form( $fields, $entry, $form_data, $entry_id ) {

		$posted = array();
		$skip   = array( 'name', 'value', 'value_raw', 'id', 'type' );

		foreach ( $fields as $field ) {
			$key            = sanitize_title( $field['name'] );
			$posted[ $key ] = $field['value'];

			// IF type is checkbox, split values on new line.
			if ( in_array( $field['type'], $this->multiple, true ) && is_string( $field['value'] ) ) {
				$posted[ $key ] = explode( "\n", $field['value'] );

				// Unless value is one.
				if ( 1 === count( $posted[ $key ] ) ) {
					$posted[ $key ] = $posted[ $key ][0];
				}
			}

			foreach ( $field as $child_key => $value ) {
				if ( in_array( $child_key, $skip, true ) ) {
					continue;
				}

				$child_key            = $key . '.' . $child_key;
				$posted[ $child_key ] = $value;
			}
		}

		// Trigger action.
		$this->process_form_submission( $form_data['id'], $posted );

		// Maybe add subscriber.
		$this->maybe_add_subscriber( $form_data, $fields );
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
								'noptin_field_gdpr',
								$instance->form_data,
								__( 'GDPR checkbox (Optional)', 'newsletter-optin-box' ),
								array(
									'field_map'   => array( 'checkbox', 'gdpr-checkbox' ),
									'placeholder' => __( '-- Map Field --', 'newsletter-optin-box' ),
									'tooltip'     => __( 'If mapped, only users who consent will join your newsletter.', 'newsletter-optin-box' ),
								)
							);

						foreach ( get_editable_noptin_subscriber_fields() as $key => $field ) {

							wpforms_panel_field(
								'select',
								'settings',
								'noptin_field_' . $key,
								$instance->form_data,
								empty( $field['label'] ) ? $field['description'] : $field['label'],
								array(
									'field_map'   => array(
										'address',
										'checkbox',
										'date-time',
										'email',
										'file-upload',
										'gdpr-checkbox',
										'hidden',
										'likert_scale',
										'name',
										'net_promoter_score',
										'number',
										'number-slider',
										'payment-checkbox',
										'payment-multiple',
										'payment-select',
										'payment-single',
										'payment-total',
										'phone',
										'radio',
										'rating',
										'richtext',
										'select',
										'signature',
										'text',
										'textarea',
										'url',
									),
									'placeholder' => __( '-- Map Field --', 'newsletter-optin-box' ),
									'tooltip'     => ! empty( $field['label'] ) && $field['description'] !== $field['label'] ? $field['description'] : '',
								)
							);
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
	 * Adds subscriber.
	 *
	 * @param array  $form_data Form data and settings.
	 * @return array
	 */
	private function maybe_add_subscriber( $form_data, $fields ) {

		// Check that the form was configured for email subscriptions.
		if ( ! function_exists( 'add_noptin_subscriber' ) || empty( $form_data['settings']['enable_noptin'] ) || '1' !== $form_data['settings']['enable_noptin'] ) {
			return;
		}

		$prepared = array(
			'source' => 'WPForms',
		);

		// Add the user's IP address.
		$address = noptin_get_user_ip();
		if ( ! empty( $address ) && '::1' !== $address ) {
			$prepared['ip_address'] = $address;
		}

		// Referral page.
		if ( ! empty( $_REQUEST['referrer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$prepared['conversion_page'] = esc_url_raw( $_REQUEST['referrer'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		foreach ( $form_data['settings'] as $key => $field_id ) {

			// Skip if key does not start with noptin_field_.
			if ( '' === $field_id || 0 !== strpos( $key, 'noptin_field_' ) ) {
				continue;
			}

			$key = str_replace( 'noptin_field_', '', $key );

			// If field is gdpr, skip if not checked.
			if ( 'gdpr' === $key && empty( $fields[ $field_id ]['value'] ) ) {
				return;
			}

			if ( isset( $fields[ $field_id ]['value'] ) ) {
				$prepared[ $key ] = noptin_clean( $fields[ $field_id ]['value'] );

				// IF type is checkbox, split values on new line.
				if ( in_array( $fields[ $field_id ]['type'], $this->multiple, true ) && is_string( $fields[ $field_id ]['value'] ) ) {
					$value = explode( "\n", $fields[ $field_id ]['value'] );

					// Unless value is one.
					if ( 1 < count( $value ) ) {
						$prepared[ $key ] = noptin_clean( $value );
					}
				}
			}
		}

		// Abort if email is not set.
		if ( empty( $prepared['email'] ) ) {
			return;
		}

		// Add subscriber.
		add_noptin_subscriber( $prepared );
	}
}
