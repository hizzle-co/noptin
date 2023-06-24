<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with the user registration form.
 *
 * @since       1.2.6
 */
class Noptin_WP_Registration_Form extends Noptin_Abstract_Integration {

	/**
	 * @var string source of subscriber.
	 * @since 1.7.0
	 */
	public $subscriber_via = 'registration';

	/**
	 * Init variables.
	 *
	 * @since       1.2.6
	 */
	public function before_initialize() {
		$this->slug        = 'registration_form';
		$this->name        = __( 'Registration Form', 'newsletter-optin-box' );
		$this->description = __( 'Subscribes people from your WordPress registration forms and keeps them in sync.', 'newsletter-optin-box' );
	}

	/**
	 * Setup hooks in case the integration is enabled.
	 *
	 * @since 1.2.6
	 */
	public function initialize() {
		add_action( 'um_user_register', array( $this, 'subscribe_from_registration' ), $this->priority );
		add_action( 'user_register', array( $this, 'subscribe_from_registration' ), $this->priority );
		add_action( 'profile_update', array( $this, 'subscribe_from_registration' ), $this->priority );
		add_action( 'bp_core_signup_user', array( $this, 'subscribe_from_registration' ), $this->priority );
	}

	/**
	 * Displays a checkbox if the integration uses checkbox positions.
	 *
	 * @since 1.2.6
	 */
	public function hook_checkbox_code() {
		add_action( 'register_form', array( $this, 'output_checkbox' ), $this->priority );
		// TODO: Add Ultimate Member fields to user smart tags.
		add_action( 'um_after_register_fields', array( $this, 'um_output_checkbox' ), $this->priority );
		add_action( 'woocommerce_register_form', array( $this, 'output_checkbox' ), $this->priority );
		// TODO: Add UsersWP fields to user smart tags.
		add_action( 'uwp_template_fields', array( $this, 'uwp_output_checkbox' ), $this->priority );
		// TODO: Add BuddyPress fields to user smart tags.
		add_action( 'bp_account_details_fields', array( $this, 'output_checkbox' ), 1000 );
	}

	/**
	 * Displays a subscription checkbox on UM registration pages.
	 *
	 * @since 1.2.6
	 */
	public function um_output_checkbox( $action ) {

		if ( $this->can_show_checkbox() ) {
			echo '<div class="um-field-area um-noptin-checkbox-field-area"> <label class="um-field-checkbox">';
			echo '<input type="checkbox" name="noptin-subscribe" value="1">';
			echo '<span class="um-field-checkbox-state"><i class="um-icon-android-checkbox-outline-blank"></i></span>';
			echo '<span class="um-field-checkbox-option">' . wp_kses_post( $this->get_label_text() ) . '</span>';
			echo '</label><div class="um-clear"></div></div>';

		}

	}

	/**
	 * Displays a subscription checkbox on UsersWP registration pages.
	 *
	 * @since 1.2.6
	 */
	public function uwp_output_checkbox( $action ) {

		if ( 'register' === $action && $this->can_show_checkbox() ) {

			if ( function_exists( 'aui' ) && uwp_get_option( 'design_style', 'bootstrap' ) ) {

				aui()->input(
					array(
						'type'  => 'checkbox',
						'id'    => wp_doing_ajax() ? 'noptin-subscribe_ajax' : 'noptin-subscribe',
						'name'  => 'noptin-subscribe',
						'value' => 1,
						'label' => $this->get_label_text(),
					),
					true
				);
				return;

			}

			$this->output_checkbox();

		}

	}

	/**
	 * Prints the checkbox wrapper.
	 *
	 */
	public function before_checkbox_wrapper() {

		if ( did_action( 'uwp_template_fields' ) ) {
			echo '<div class="uwp_form_checkbox_row uwp_clear">';
		} elseif ( doing_action( 'woocommerce_register_form' ) ) {
			echo "<p class='noptin_registration_form_optin_checkbox_wrapper woocommerce-form-row form-row'>";
		} else {
			echo "<p class='noptin_registration_form_optin_checkbox_wrapper'>";
		}

	}

	/**
	 * Prints the checkbox closing wrapper.
	 *
	 */
	public function after_checkbox_wrapper() {

		// UWP.
		if ( did_action( 'uwp_template_fields' ) ) {
			echo '</div>';
			return;
		}

		echo '</p>';
	}

	/**
	 * Returns the checkbox message option name.
	 *
	 * @since 1.2.6
	 * @return string
	 */
	public function get_checkbox_message_integration_option_name() {
		return 'register_form_msg';
	}

	/**
	 * Returns the enable option name.
	 *
	 * @since 1.2.6
	 * @return string
	 */
	public function get_enable_integration_option_name() {
		return 'register_form';
	}

	/**
	 * Subscribes from WP Registration Form
	 *
	 * @param int $user_id
	 *
	 * @return int|null
	 */
	public function subscribe_from_registration( $user_id ) {

		// Check if the user exists.
		$user = get_userdata( $user_id );

		if ( ! $user instanceof WP_User ) {
			return false;
		}

		// Prepare subscriber fields.
		$noptin_fields = array(
			'source'     => 'registration',
			'wp_user_id' => $user->ID,
			'email'      => $user->user_email,
			'name'       => $user->display_name,
			'first_name' => $user->user_firstname,
			'last_name'  => $user->user_lastname,
		);

		$noptin_fields = array_filter( $noptin_fields );
		$subscriber_id = get_noptin_subscriber_id_by_email( $user->user_email );

		// If the subscriber does not exist, create a new one.
		if ( empty( $subscriber_id ) ) {

			// Ensure the subscription checkbox was triggered.
			if ( $this->triggered() ) {
				return $this->add_subscriber( $noptin_fields, $user_id );
			}
			return null;

		}

		// Else, update the existing subscriber.
		unset( $noptin_fields['source'] );
		return $this->update_subscriber( $subscriber_id, $noptin_fields, $user_id );

	}

}
