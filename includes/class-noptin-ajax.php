<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles Noptin Ajax Requests
 *
 * @since       1.0.5
 */
class Noptin_Ajax {

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Register new subscriber.
		add_action( 'wp_ajax_noptin_new_subscriber', array( $this, 'add_subscriber' ) ); // @deprecated
		add_action( 'wp_ajax_nopriv_noptin_new_subscriber', array( $this, 'add_subscriber' ) ); // @deprecated

		// Save settings.
		add_action( 'wp_ajax_noptin_save_options', array( $this, 'save_options' ) );

		// Save rule.
		add_action( 'wp_ajax_noptin_save_automation_rule', array( $this, 'save_rule' ) );
		add_action( 'wp_ajax_noptin_toggle_automation_rule', array( $this, 'toggle_rule' ) );
		add_action( 'wp_ajax_noptin_delete_automation_rule', array( $this, 'delete_rule' ) );

		// Delete campaign.
		add_action( 'wp_ajax_noptin_delete_campaign', array( $this, 'delete_campaign' ) );

		// Stop campaigns.
		add_action( 'wp_ajax_noptin_stop_campaign', array( $this, 'stop_campaign' ) );

	}

	/**
	 * Deletes a campaign
	 *
	 * @access      public
	 * @since       1.1.2
	 * @return      void
	 */
	public function delete_campaign() {

		// Verify nonce.
		check_ajax_referer( 'noptin_admin_nonce' );

		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['id'] ) ) {
			wp_die( -1, 403 );
		}

		if ( wp_delete_post( trim( $_GET['id'] ), true ) ) {
			exit;
		}

		wp_die( -1, 500 );
	}

	/**
	 * Stop sending a campaign
	 *
	 * @access      public
	 * @since       1.2.3
	 * @return      void
	 */
	public function stop_campaign() {

		// Verify nonce.
		check_ajax_referer( 'noptin_admin_nonce' );

		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['id'] ) ) {
			wp_die( -1, 403 );
		}

		$updated = wp_update_post(
			array(
				'ID'          => trim( $_GET['id'] ),
				'post_status' => 'draft',
			)
		);

		if ( ! empty( $updated ) ) {
			exit;
		}

		wp_die( -1, 500 );
	}

	/**
	 * Adds a new subscriber via ajax
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 * @deprecated
	 */
	public function add_subscriber() {

		// Verify nonce.
		if ( noptin_verify_subscription_nonces() ) {
			check_ajax_referer( 'noptin' );
		}

		// avoid bot submissions.
		if ( ! empty( $_POST['noptin_confirm_submit'] ) ) {
			exit;
		}

		/**
		 * Fires before a subscriber is added via ajax.
		 *
		 * @since 1.2.4
		 */
		do_action( 'noptin_before_add_ajax_subscriber' );

		// Prepare form fields.
		$form = 0;

		if ( empty( $_POST['noptin_form_id'] ) ) {

			$fields = array(
				array(
					'type'    => array(
						'label' => __( 'Email Address', 'newsletter-optin-box' ),
						'name'  => 'email',
						'type'  => 'email',
					),
					'require' => 'true',
					'key'     => 'email',
				),
			);

		} else {

			// Get the form.
			$form = noptin_get_optin_form( $_POST['noptin_form_id'] );

			if ( empty( $form ) || ! $form->is_published() ) {
				wp_send_json_error( __( 'This form is in-active.', 'newsletter-optin-box' ) );
			}

			$fields = $form->fields;
		}

		$filtered = array();

		// Check gdpr.
		if ( is_object( $form ) && $form->gdprCheckbox && empty( $_POST['noptin_gdpr_checkbox'] ) ) {
			wp_send_json_error( __( 'You must consent to receive promotional emails.', 'newsletter-optin-box' ) );
		}

		if ( ! empty( $_POST['noptin_gdpr_checkbox'] ) ) {
			$filtered['GDPR_consent'] = 1;
		}

		$custom_fields = wp_list_pluck( get_noptin_custom_fields(), 'type', 'merge_tag' );

		foreach ( $fields as $field ) {

			$type = $field['type']['type'];

			if ( isset( $custom_fields[ $type ] ) ) {
				$name  = $type;
				$value = isset( $_POST[ $type ] ) ? $_POST[ $type ] : '';

				if ( 'checkbox' === $custom_fields[ $type ] ) {
					$value = (int) ! empty( $value );
				}
			} else {

				// backwards compatibility.
				$name = 'name' === $type ? 'name' : $field['type']['name'];
				$key  = esc_attr( $field['key'] );

				if ( isset( $_POST[ $key ] ) ) {
					$value = $_POST[ $key ];
				} else {
					$value = '';
				}

				if ( 'checkbox' === $type ) {
					$value = (int) ! empty( $value );
				}
			}

			// required fields.
			if ( ! empty( $field['require'] ) && 'false' !== $field['require'] && empty( $value ) ) {
				wp_send_json_error( esc_html__( 'Ensure that you fill all required fields.', 'newsletter-optin-box' ) );
			}

			// Sanitize email fields.
			if ( 'email' === $type && ! empty( $value ) ) {

				$value = sanitize_email( $value );
				if ( empty( $value ) ) {
					wp_send_json_error( esc_html__( 'Please provide a valid email address', 'newsletter-optin-box' ) );
				}
			}

			$filtered[ $name ] = $value;

		}

		// Add the subscriber's IP address.
		$address = noptin_get_user_ip();
		if ( ! empty( $address ) && '::1' !== $address ) {
			$filtered['ip_address'] = $address;
		}

		if ( ! empty( $_POST['conversion_page'] ) ) {
			$filtered['conversion_page'] = esc_url_raw( trim( $_POST['conversion_page'] ) );
		}

		if ( is_object( $form ) ) {
			$filtered['source'] = $form->ID;
		}

		/**
		 * Filters subscriber details when adding a new subscriber via ajax.
		 *
		 * @since 1.2.4
		 */
		$filtered = apply_filters( 'noptin_add_ajax_subscriber_filter_details', wp_unslash( $filtered ), $form );
		$inserted = add_noptin_subscriber( $filtered );

		if ( is_string( $inserted ) ) {
			wp_send_json_error( $inserted );
		}

		do_action( 'noptin_add_ajax_subscriber', $inserted, $form );

		$result = array(
			'action' => 'msg',
			'msg'    => get_noptin_option( 'success_message' ),
		);

		if ( empty( $result['msg'] ) ) {
			$result['msg'] = esc_html__( 'Thanks for subscribing to the newsletter', 'newsletter-optin-box' );
		}

		if ( is_object( $form ) ) {

			$count = (int) get_post_meta( $form->ID, '_noptin_subscribers_count', true );
			update_post_meta( $form->ID, '_noptin_subscribers_count', $count + 1 );

			// msg.
			if ( 'message' === $form->subscribeAction ) {
				$result['msg'] = $form->successMessage;
			} else {
				// redirects.
				$result['action']   = 'redirect';
				$result['redirect'] = $form->redirectUrl;
			}
		}

		$result['msg'] = add_noptin_merge_tags( $result['msg'], get_noptin_subscriber_merge_fields( $inserted ) );

		wp_send_json_success( $result );
	}

	/**
	 * Saves settings
	 *
	 * @access      public
	 * @since       1.0.8
	 */
	public function save_options() {

		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 403 );
		}

		// Check nonce.
		check_ajax_referer( 'noptin_admin_nonce' );

		/**
		 * Runs before saving a settings
		 */
		do_action( 'noptin_before_save_options' );

		// Prepare settings.
		$settings = json_decode( wp_unslash( $_POST['state'] ), true );
		unset( $settings['noptin_admin_nonce'] );
		unset( $settings['saved'] );
		unset( $settings['error'] );
		unset( $settings['currentTab'] );
		unset( $settings['currentSection'] );
		unset( $settings['openSections'] );
		unset( $settings['fieldTypes'] );

		if ( ! empty( $settings['custom_fields'] ) ) {

			foreach ( $settings['custom_fields'] as $index => $custom_field ) {
				if ( isset( $custom_field['new'] ) ) {
					unset( $custom_field['new'] );
					$settings['custom_fields'][ $index ] = $custom_field;
				}
			}
		}

		/**
		 * Sanitizes noptin settings.
		 *
		 * @param array $settings Noptin settings.
		 */
		$settings = apply_filters( 'noptin_sanitize_settings', $settings );

		// Save them.
		update_noptin_options( $settings );

		// Fire an action.
		do_action( 'noptin_admin_save_options', $settings );

		wp_send_json_success( 1 );

	}

	/**
	 * Saves rules
	 *
	 * @access      public
	 * @since       1.3.0
	 */
	public function save_rule() {

		if ( ! current_user_can( get_noptin_capability() ) || empty( $_POST['id'] ) ) {
			wp_die( -1, 403 );
		}

		// Check nonce.
		check_ajax_referer( 'noptin_automation_rules' );

		/**
		 * Runs before saving rules
		 */
		do_action( 'noptin_before_save_automation_rule' );

		// Fetch the automation rule.
		$data = wp_unslash( $_POST );
		$rule = noptin_get_automation_rule( absint( $data['id'] ) );

		if ( ! empty( $data['is_creating'] ) && ! empty( $data['trigger_id'] ) && ! empty( $data['action_id'] ) ) {

			$rule = noptin_get_automation_rule( 0 );
			$rule->set_trigger_id( $data['trigger_id'] );
			$rule->set_action_id( $data['action_id'] );
		} elseif ( is_wp_error( $rule ) || ! $rule->exists() ) {
			wp_die( -1, 404 );
		}

		// Prepare settings.
		$trigger_settings = isset( $data['trigger_settings'] ) && is_array( $data['trigger_settings'] ) ? $data['trigger_settings'] : array();
		$action_settings  = isset( $data['action_settings'] ) && is_array( $data['action_settings'] ) ? $data['action_settings'] : array();

		// Prepare the conditional logic.
		$trigger_settings['conditional_logic'] = noptin_get_default_conditional_logic();
		if ( ! empty( $data['conditional_logic'] ) ) {
			$trigger_settings['conditional_logic'] = $data['conditional_logic'];
		}

		$rule->set_trigger_settings( $trigger_settings );
		$rule->set_action_settings( $action_settings );

		// Save them.
		$result = $rule->save();

		if ( is_wp_error( $result ) ) {
			wp_die( -1, 500 );
		}

		wp_send_json_success(
			array(
				'rule_id'  => $rule->get_id(),
				'edit_url' => $rule->get_edit_url(),
			)
		);

	}

	/**
	 * Toggles rules.
	 *
	 * @access public
	 * @since  1.3.0
	 */
	public function toggle_rule() {

		if ( ! current_user_can( get_noptin_capability() ) || empty( $_POST['rule_id'] ) ) {
			wp_die( -1, 403 );
		}

		// Check nonce.
		check_ajax_referer( 'noptin_automation_rules' );

		/**
		 * Runs before toggling rules
		 */
		do_action( 'noptin_before_toggle_automation_rule' );

		// Save them.
		noptin()->automation_rules->update_rule(
			absint( $_POST['rule_id'] ),
			array(
				'status' => empty( $_POST['enabled'] ) ? 0 : 1,
			)
		);

		wp_send_json_success( 1 );

	}

	/**
	 * Deletes rules.
	 *
	 * @access public
	 * @since  1.3.0
	 */
	public function delete_rule() {

		if ( ! current_user_can( get_noptin_capability() ) || empty( $_POST['rule_id'] ) ) {
			wp_die( -1, 403 );
		}

		// Check nonce.
		check_ajax_referer( 'noptin_automation_rules' );

		// Delete the rule.
		noptin()->automation_rules->delete_rule( absint( $_POST['rule_id'] ) );

		wp_send_json_success( 1 );

	}
}
