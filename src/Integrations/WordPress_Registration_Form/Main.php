<?php

/**
 * Handles integrations with the user registration form.
 *
 * @since 1.0.0
 */

namespace Hizzle\Noptin\Integrations\WordPress_Registration_Form;

defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with the user registration form.
 *
 * @since 1.2.6
 */
class Main extends \Hizzle\Noptin\Integrations\Checkbox_Integration {

	/**
	 * Init variables.
	 *
	 * @since 1.2.6
	 */
	public function __construct() {
		$this->slug   = 'registration_form';
		$this->source = 'registration';
		$this->name   = __( 'Registration Form', 'newsletter-optin-box' );
		$this->url    = 'getting-email-subscribers/wordpress-registration-forms/';

		parent::__construct();
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

		/**
		 * BuddyPress signups are a two-stage process - the data is first added to
		 * the 'signups' table and then converted into an actual user during the
		 * activation process.
		 *
		 * To avoid all signups being subscribed until they
		 * have responded to the activation email, a value is stored in the signup
		 * usermeta data which is retrieved on activation and acted upon.
		 */
		add_filter( 'bp_signup_usermeta', array( $this, 'store_bp_usermeta' ), $this->priority, 1 );
		add_action( 'bp_core_activated_user', array( $this, 'subscribe_from_bp_usermeta' ), $this->priority, 3 );
	}

	/**
	 * Displays a checkbox if the integration uses checkbox positions.
	 *
	 * @since 1.2.6
	 */
	public function hook_checkbox_code() {

		// Normal registration forms.
		add_action( 'register_form', array( $this, 'output_checkbox' ), $this->priority );

		// Ultimate Member.
		add_action( 'um_after_register_fields', array( $this, 'um_output_checkbox' ), $this->priority );

		// WooCommerce.
		add_action( 'woocommerce_register_form', array( $this, 'output_checkbox' ), $this->priority );

		// UsersWP
		add_action( 'uwp_template_fields', array( $this, 'uwp_output_checkbox' ), $this->priority );

		// BuddyPress
		add_action( 'bp_before_registration_submit_buttons', array( $this, 'output_checkbox' ), 1000 );
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

		// Abort if checkbox was not checked.
		if ( ! $this->triggered() ) {
			return;
		}

		// Check if the user exists.
		$user = get_userdata( $user_id );

		if ( ! $user instanceof \WP_User ) {
			return false;
		}

		// Process the submission.
		$this->process_submission(
			array(
				'source'     => $this->source,
				'email'      => $user->user_email,
				'name'       => $user->display_name,
				'first_name' => $user->user_firstname,
				'last_name'  => $user->user_lastname,
				'website'    => $user->user_url,
				'login'      => $user->user_login,
				'bio'        => $user->user_description,
				'ip_address' => noptin_get_user_ip(),
			)
		);
	}

	/**
	 * Stores subscription data from BuddyPress Registration Form.
	 *
	 * @param array $usermeta The existing usermeta
	 * @return array $usermeta The modified usermeta
	 */
	public function store_bp_usermeta( $usermeta ) {

		// only add meta if triggered (checked)
		if ( $this->triggered() ) {
			$usermeta['noptin_subscribe'] = '1';
		}

		return $usermeta;
	}

	/**
	 * Subscribes from BuddyPress Activation.
	 *
	 * @param int $user_id The activated user ID
	 * @param string $key the activation key (not used)
	 * @param array $userdata An array containing the activated user data
	 * @return bool
	 */
	public function subscribe_from_bp_usermeta( $user_id, $key, $userdata ) {

		// sanity check
		if ( empty( $user_id ) ) {
			return false;
		}

		// bail if our usermeta key is not switched on
		$meta = $userdata['meta'] ?? array();
		if ( empty( $meta['noptin_subscribe'] ) ) {
			return false;
		}

		$GLOBALS['bp_noptin_checkbox_was_checked'] = true;

		return $this->subscribe_from_registration( $user_id );
	}

	/**
	 * @inheritdoc
	 */
	public function custom_fields() {
		return array(
			'user_id'    => __( 'User ID', 'newsletter-optin-box' ),
			'name'       => __( 'Display Name', 'newsletter-optin-box' ),
			'first_name' => __( 'First Name', 'newsletter-optin-box' ),
			'last_name'  => __( 'Last Name', 'newsletter-optin-box' ),
			'website'    => __( 'Website', 'newsletter-optin-box' ),
			'login'      => __( 'Login', 'newsletter-optin-box' ),
			'bio'        => __( 'Bio', 'newsletter-optin-box' ),
		);
	}

	/**
	 * Checks if a checkbox was checked.
	 *
	 * @return bool
	 * @since 1.2.6
	 */
	public function checkbox_was_checked() {
		if ( ! empty( $GLOBALS['bp_noptin_checkbox_was_checked'] ) ) {
			return true;
		}

		return parent::checkbox_was_checked();
	}
}
