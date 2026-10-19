<?php

namespace Hizzle\Noptin\Integrations\Elementor;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with Elementor Forms
 *
 * @since 1.3.2
 */

class Action extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	/**
	 * Get Name
	 *
	 * Return the action name
	 *
	 * @access public
	 * @since 1.3.2
	 * @return string
	 */
	public function get_name() {
		return 'noptin';
	}

	/**
	 * Get Label
	 *
	 * Returns the action label
	 *
	 * @access public
	 * @since 1.3.2
	 * @return string
	 */
	public function get_label() {
		return 'Noptin';
	}

	/**
	 * Run
	 *
	 * Runs the action after submit
	 *
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {

		// Prepare subscriber.
		$subscriber = $this->map_fields( $record );

		// Make sure that we have an email field.
		if ( empty( $subscriber['email'] ) ) {
			return;
		}

		/**
		 * Filters subscriber details when adding a new subscriber via elementor forms.
		 *
		 * @since 1.3.1
		 */
		$subscriber = apply_filters( 'noptin_elementor_forms_subscriber_details', $subscriber, $record, $ajax_handler );

		add_noptin_subscriber( wp_unslash( $subscriber ) );
	}

	/**
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 *
	 * @return array
	 */
	private function map_fields( $record ) {

		// Retrieve form settings.
		$settings = $record->get( 'form_settings' );

		if ( empty( $settings['noptin_fields_map'] ) ) {
			return array();
		}

		// Get submitetd Form data.
		$fields = $record->get( 'fields' );

		$subscriber = array(
			'source' => 'Elementor',
		);

		// Referral page.
		if ( ! empty( $_REQUEST['referrer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$subscriber['conversion_page'] = esc_url_raw( $_REQUEST['referrer'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// Add the subscriber's IP address.
		$address = noptin_get_user_ip();
		if ( ! empty( $address ) && '::1' !== $address ) {
			$subscriber['ip_address'] = $address;
		}

		$has_accepted = true;

		foreach ( $settings['noptin_fields_map'] as $map_item ) {

			// Abort if not mapped.
			if ( empty( $map_item['local_id'] ) ) {
				continue;
			}

			// Acceptance field.
			if ( 'GDPR_consent' === $map_item['remote_id'] && ( empty( $fields[ $map_item['local_id'] ]['value'] ) || 'on' !== $fields[ $map_item['local_id'] ]['value'] ) ) {
				$has_accepted = false;
			}

			if ( empty( $fields[ $map_item['local_id'] ]['value'] ) ) {
				continue;
			}

			$subscriber[ $map_item['remote_id'] ] = $fields[ $map_item['local_id'] ]['value'];
		}

		if ( ! $has_accepted ) {
			unset( $subscriber['email'] );
		}

		return $subscriber;
	}

	/**
	 * Register Settings Section
	 *
	 * Registers the Action controls
	 *
	 * @access public
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) {

		$widget->start_controls_section(
			'section_noptin',
			array(
				'label'     => $this->get_label(),
				'condition' => array(
					'submit_actions' => $this->get_name(),
				),
			)
		);

		$map_fields = array(

			array(
				'remote_id'    => 'GDPR_consent',
				'remote_label' => __( 'GDPR Consent', 'newsletter-optin-box' ),
				'remote_type'  => 'acceptance',
			),

		);

		foreach ( get_editable_noptin_subscriber_fields() as $key => $field ) {

			$map_fields[] = array(
				'remote_id'       => $key,
				'remote_label'    => empty( $field['label'] ) ? $field['description'] : $field['label'],
				'remote_type'     => 'email' === $key ? 'email' : 'text',
				'remote_required' => 'email' === $key,
			);
		}

		// Map Fields.
		$widget->add_control(
			'noptin_fields_map',
			array(
				'label'     => __( 'Field Mapping', 'newsletter-optin-box' ),
				'type'      => \ElementorPro\Modules\Forms\Controls\Fields_Map::CONTROL_TYPE,
				'separator' => 'before',
				'fields'    => array(

					array(
						'name' => 'remote_id',
						'type' => \Elementor\Controls_Manager::HIDDEN,
					),

					array(
						'name' => 'local_id',
						'type' => \Elementor\Controls_Manager::SELECT,
					),

				),

				'default'   => apply_filters( 'noptin_elementor_map_fields', $map_fields ),
			)
		);

		do_action( 'noptin_elementor_forms_integration_settings', $widget, $this );

		$widget->end_controls_section();
	}

	/**
	 * On Export
	 *
	 * Clears form settings on export
	 * @access public
	 * @param array $element
	 */
	public function on_export( $element ) {

		unset( $element['noptin_fields_map'] );
		$element = apply_filters( 'noptin_elementor_forms_on_export', $element );
		return $element;
	}
}
