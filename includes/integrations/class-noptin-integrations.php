<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with other products and services
 *
 * @since       1.0.8
 */
class Noptin_Integrations {

	/**
	 * @var array Available Noptin integrations.
	 */
	public $integrations = array();

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Load integrations.
		$integrations = array(
			'nf_init'            => 'load_ninja_forms_integration',
			'wpforms_loaded'     => 'load_wpforms_integration',
			'wpcf7_init'         => 'load_contact_form_7_integration',
			'elementor_pro/init' => 'load_elementor_forms_integration',
			'gform_loaded'       => 'load_gravity_forms_integration',
			'wpml_loaded'        => 'load_wpml_integration',
			'pll_init'           => 'load_polylang_integration',
		);

		foreach ( $integrations as $action => $method ) {

			add_action( $action, array( $this, $method ) );
			if ( did_action( $action ) ) {
				call_user_func( array( $this, $method ) );
			}
		}

		// WooCommerce integration.
		if ( class_exists( 'WooCommerce' ) ) {
			$this->integrations['woocommerce'] = new Noptin_WooCommerce();
		}

		// EDD integration.
		if ( class_exists( 'Easy_Digital_Downloads' ) ) {
			$this->integrations['edd'] = new Noptin_EDD();
		}

		// WS Form integration.
		if ( class_exists( 'WS_Form' ) ) {
			$this->integrations['ws_form'] = new Noptin_WS_Form();
		}

		// Fluent Forms integration.
		if ( class_exists( '\FluentForm\App\Modules\Form\FormHandler' ) ) {
			$this->integrations['fluentform'] = new Noptin_Fluent_Forms();
		}

		// WP Registration form integration.
		$this->integrations['wp_registration_form'] = new Noptin_WP_Registration_Form();

		// WP Comment form integration.
		$this->integrations['wp_comment_form'] = new Noptin_WP_Comment_Form();

		// Formidable forms.
		add_action( 'frm_registered_form_actions', array( $this, 'register_formidable_form_action' ) );
		add_action( 'frm_action_groups', array( $this, 'group_formidable_form_action' ) );
		add_action( 'frm_trigger_noptin_action', 'Noptin_Formidable_Forms::process_form', 10, 2 );

		// Ninja forms.
		add_action( 'noptin_automation_rules_load', array( $this, 'load_ninja_forms_automation_rule' ) );
		add_filter( 'noptin_ninja_forms_forms', array( $this, 'filter_ninja_forms_forms' ) );
		add_action( 'ninja_forms_after_submission', array( $this, 'fire_ninja_forms_submission' ) );

		// Filter subscription sources.
		add_filter( 'noptin_subscription_sources', array( $this, 'register_subscription_source' ) );

		do_action( 'noptin_integrations_load', $this );

	}

	/**
	 * Loads WPForms integration
	 *
	 * @access      public
	 * @since       1.2.6
	 */
	public function load_wpforms_integration() {
		new Noptin_WPForms();
	}

	/**
	 * Loads WPML integration
	 *
	 * @access      public
	 * @since       1.6.2
	 */
	public function load_wpml_integration() {
		new Noptin_WPML();
	}

	/**
	 * Loads Polylang integration
	 *
	 * @access      public
	 * @since       1.6.2
	 */
	public function load_polylang_integration() {
		new Noptin_Polylang();
	}

	/**
	 * Loads Ninja Forms integration
	 *
	 * @access      public
	 * @since       1.3.3
	 */
	public function load_ninja_forms_integration() {
		if ( class_exists( 'Ninja_Forms' ) ) {
			$ninja_forms                    = Ninja_Forms::instance();
			$ninja_forms->actions['noptin'] = new Noptin_Ninja_Forms();
		}
	}

	/**
	 * Loads Ninja Forms automation rule
	 *
	 * @access public
	 * @since  1.10.3
	 * @param  Noptin_Automation_Rules $rules The automation rules instance.
	 */
	public function load_ninja_forms_automation_rule( $rules ) {
		if ( class_exists( 'Ninja_Forms' ) ) {
			$rules->add_trigger( new Noptin_Form_Submit_Trigger( 'ninja_forms', 'Ninja Forms' ) );
		}
	}

	/**
	 * Filters forms.
	 *
	 * @param array $forms An array of forms.
	 * @return array
	 */
	public function filter_ninja_forms_forms( $forms ) {
		global $noptin_ninja_forms_forms;

		// Return cached forms.
		if ( is_array( $noptin_ninja_forms_forms ) ) {
			return array_replace( $forms, $noptin_ninja_forms_forms );
		}

		$noptin_ninja_forms_forms = array();

		// Get all forms.
		$all_forms = Ninja_Forms()->form()->get_forms();
		$all_forms = is_array( $all_forms ) ? $all_forms : array();

		/** @var NF_Database_Models_Form[] $all_forms */
		foreach ( $all_forms as $form ) {

			$fields = array();

			foreach ( Ninja_Forms()->form( $form->get_id() )->get_fields() as $ninja_field ) {

				/** @var NF_Database_Models_Field $ninja_field */
				if ( in_array( $ninja_field->get_setting( 'type' ), array( 'submit', 'spam', 'html', 'hr', 'listselect', 'listcheckbox', 'listradio' ), true ) ) {
					continue;
				}

				$logic = 'string';

				if ( in_array( $ninja_field->get_setting( 'type' ), array( 'number', 'calc', 'starrating' ), true ) ) {
					$logic = 'number';
				}

				$fields[ $ninja_field->get_setting( 'key' ) ] = array(
					'description'       => $ninja_field->get_setting( 'label' ),
					'conditional_logic' => $logic,
				);
			}

			$noptin_ninja_forms_forms[ $form->get_id() ] = array(
				'name'   => $form->get_setting( 'title' ),
				'fields' => $fields,
			);
		}

		return array_replace( $forms, $noptin_ninja_forms_forms );
	}

	/**
	 * Fires when a Ninja Forms form is submitted.
	 *
	 * @param array $data The form data.
	 */
	public function fire_ninja_forms_submission( $data ) {
		$form_id = $data['form_id'];
		$posted  = wp_list_pluck( $data['fields'], 'value', 'key' );
		do_action( 'noptin_ninja_forms_form_submitted', $form_id, $posted );
	}

	/**
	 * Loads Gravity Forms integration
	 *
	 * @access      public
	 * @since       1.3.3
	 */
	public function load_gravity_forms_integration() {
		if ( class_exists( 'GFAddOn' ) ) {
			GFAddOn::register( 'Noptin_Gravity_Forms' );
		}
	}

	/**
	 * Loads Contact Form 7 integration
	 *
	 * @access      public
	 * @since       1.3.3
	 */
	public function load_contact_form_7_integration() {
		new Noptin_Contact_Form_7();
	}

	/**
	 * Loads Elementor forms integration
	 *
	 * @access      public
	 * @since       1.3.2
	 */
	public function load_elementor_forms_integration() {

		// Ensure the elementor pro class exists.
		if ( ! class_exists( '\ElementorPro\Plugin' ) ) {
			return;
		}

		// Register the automation rule.
		$this->integrations['elementor'] = new Noptin_Elementor_Forms();

		// Instantiate the action class
		$action = new Noptin_Elementor_Forms_Integration();

		// Register the action with form widget
		/** @var \ElementorPro\Modules\Forms\Module $forms */
		$forms = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' );
		$forms->actions_registrar->register( $action, $action->get_name() );
	}

	/**
	 * Registers formidable forms action.
	 *
	 * @param      array $actions
	 * @since      1.5.5
	 */
	public function register_formidable_form_action( $actions ) {
		$actions['noptin'] = 'Noptin_Formidable_Forms';
		return $actions;
	}

	/**
	 * Groups the formidable forms action.
	 *
	 * @param      array $groups
	 * @since      1.5.5
	 */
	public function group_formidable_form_action( $groups ) {
		$groups['marketing']['actions'][] = 'noptin';
		return $groups;
	}

	/**
	 * Filters subscription sources.
	 *
	 * @since 1.7.0
	 * @param array $sources
	 * @return array
	 */
	public function register_subscription_source( $sources ) {

		if ( did_action( 'wpforms_loaded' ) ) {
			$sources['WPForms'] = 'WPForms';
		}

		if ( did_action( 'nf_init' ) ) {
			$sources['Ninja Forms'] = 'Ninja Forms';
		}

		if ( did_action( 'gform_loaded' ) ) {
			$sources['Gravity Forms'] = 'Gravity Forms';
		}

		if ( did_action( 'wpcf7_init' ) ) {
			$sources['Contact Form 7'] = 'Contact Form 7';
		}

		if ( class_exists( '\ElementorPro\Plugin' ) ) {
			$sources['Elementor'] = 'Elementor';
		}

		return $sources;
	}

}
