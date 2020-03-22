<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Basic Abstract integration
 *
 * @since       1.2.6
 */
abstract class Noptin_Abstract_Integration {

	/**
	 * @var bool Whether or not this integration has a settings page.
	 */
	public $supports_settings = true;

	/**
	 * @var string Name of this integration.
	 */
	public $name = '';

	/**
	 * @var string Description
	 */
	public $description = '';

	/**
	 * @var string Slug, used as an unique identifier for this integration.
	 */
	public $slug = '';

	/**
	 * Constructor
	 */
	public function __construct() {

		// Fired before an integration is initialized.
		$this->before_initialize();

		// Each integration needs a unique slug.
		if ( empty( $this->slug ) ) {
			return;
		}

		do_action( "noptin_{$this->slug}_integration_before_initialize", $this );

		// Integration settings.
		if ( $this->supports_settings ) {
			add_filter( 'noptin_get_settings', array( $this, 'add_options' ) );
		}
		
		// Abort if the integration is not enabled.
		if ( ! $this->is_enabled() ) {
			return;
		}

		// initialize the integration.
		$this->initialize();

		do_action( "noptin_{$this->slug}_integration_after_initialize", $this );

	}

	/**
	 * This method is called before an integration is initialized.
	 *
	 * Useful for setting integration variables.
	 */
	public function before_initialize() {}

	/**
	 * This method is called after an integration is initialized.
	 *
	 * This is usefull for registering integration specific hooks. It is only called if the integration is enabled.
	 */
	public function initialize() {}

	/**
	 * Add options.
	 */
	public function add_options( $options ) {

		$slug = $this->slug;

		// Integration name hero text.
		if ( ! empty( $this->name ) ) {

			$options["noptin_{$slug}_integration_hero"] = array(
				'el'              => 'hero',
				'section'		  => 'integrations',
				'content'         => $this->name, 
			);

		}

		// Integration description text.
		if ( ! empty( $this->description ) ) {

			$options["noptin_{$slug}_integration_description"] = array(
				'el'              => 'paragraph',
				'section'		  => 'integrations',
				'content'         => $this->description, 
			);

		}

		$options = $this->get_options( $options );

		return apply_filters( "noptin_{$slug}_integration_get_settings", $options );

	}

	/**
	 * Saves a default value.
	 */
	public function maybe_save_default_value( $option_name, $value ) {
		$saved = get_noptin_option( $option_name );
		if ( is_null( $saved ) ) {
			update_noptin_option( $option_name, $value );
		}
	}

	/**
	 * Adds an integration subscription checkbox message.
	 */
	public function add_checkbox_message_integration_option( $options, $title = null, $description = '' ) {

		if ( is_null( $title ) ) {
			$title = __( 'Enable', 'newsletter-optin-box' );
		}

		$option_name = $this->get_checkbox_message_integration_option_name();
		$default     = $this->get_checkbox_message_integration_default_value();

		$this->maybe_save_default_value( $option_name, $default );

		$options[$option_name] = array(
            'el'          => 'input',
			'type'        => 'text',
			'restrict'    => sprintf( 
				'%s && %s',
				$this->get_enable_integration_option_name(),
				$this->get_autosubscribe_integration_option_name()
			),
            'section'     => 'integrations',
            'label'       => $title,
			'description' => $description,
			'placeholder' => $default,
		);

		return $options;

	}

	/**
	 * Returns the checkbox message option name.
	 */
	public function get_checkbox_message_integration_option_name() {
		return sprintf( 'noptin_%s_integration_checkbox_message', $this->slug);
	}

	/**
	 * Returns the checkbox message default value.
	 */
	public function get_checkbox_message_integration_default_value() {
		return __( 'Add me to your newsletter and keep me updated whenever your publish new blog posts', 'newsletter-optin-box' );
	}

	/**
	 * Adds an enable integration checkbox
	 */
	public function add_enable_integration_option( $options, $title = null, $description = null ) {

		if ( is_null( $title ) ) {
			$title = __( 'Enable', 'newsletter-optin-box' );
		}

		if ( is_null( $description ) ) {
			$description = sprintf(
				__( 'Enable %s Integration', 'newsletter-optin-box' ),
				$this->name
			);
		}

		$option_name = $this->get_enable_integration_option_name();
		$this->maybe_save_default_value( $option_name, $this->get_enable_integration_default_value() );

		$options[$this->get_enable_integration_option_name()] = array(
            'type'                  => 'checkbox_alt',
            'el'                    => 'input',
            'section'		        => 'integrations',
            'label'                 => $title,
            'description'           => $description,
		);

		return $options;

	}

	/**
	 * Returns the enable option name.
	 */
	public function get_enable_integration_option_name() {
		return sprintf( 'noptin_enable_%s_integration', $this->slug);
	}

	/**
	 * Returns the enable integration default value.
	 */
	public function get_enable_integration_default_value() {
		return false;
	}

	/**
	 * Adds an autosubscribe checkbox
	 */
	public function add_autosubscribe_integration_option( $options, $title = null, $description = null, $default = 'true' ) {

		$option_name = "noptin_{$this->slug}_integration_manual_subscription";

		$this->maybe_save_default_value( $option_name, $default );

		if ( is_null( $title ) ) {
			$title = __( 'Manual Subscription', 'newsletter-optin-box' );
		}

		if ( is_null( $description ) ) {
			$description = __( 'Check to display a subscription checkbox instead of automatically subscribing new users', 'newsletter-optin-box' );
		}

		$options[$option_name] = array(
            'type'                  => 'checkbox_alt',
            'el'                    => 'input',
            'section'		        => 'integrations',
            'label'                 => $title,
			'description'           => $description,
			'restrict'              => "noptin_enable_{$this->slug}_integration",
		);

		return $options;

	}

	/**
	 * Returns the autosubscribe option name.
	 */
	public function get_autosubscribe_integration_option_name() {
		return sprintf( 'noptin_%s_integration_manual_subscription', $this->slug);
	}

	/**
	 * Returns integration specific settings.
	 *
	 * @param array $options Current Noptin settings.
	 */
	public function get_options( $options ) {
		$options = $this->add_enable_integration_option( $options );
		$options = $this->add_autosubscribe_integration_option( $options );
		$options = $this->add_checkbox_message_integration_option( $options );
		return $options;
	}

	/**
	 * Subscription checkbox.
	 */
	public function get_label_text() {
		$label = get_noptin_option( "noptin_{$this->slug}_integration_checkbox_message" );

		if ( empty( $label ) ) {
			return $this->get_checkbox_message_integration_default_value();
		}
		return $label;
	}

	/**
	 * Whether to automaticall subscribe a new customer or not.
	 */
	public function auto_subscribe() {
		return false === get_noptin_option( $this->get_autosubscribe_integration_option_name() );
	}

	/**
	 * Checks if this integration is enabled.
	 */
	public function is_enabled() {
		return true === get_noptin_option( $this->get_enable_integration_option_name() );
	}

	/**
	 * Displays a subscription checkbox.
	 */
	function output_checkbox() {
		echo $this->get_checkbox_markup();
	}

	/**
	 * Returns the subscription checkbox markup.
	 *
	 * @param array $html_attrs An array of HTML attributes.
	 * @return string
	 */
	function get_checkbox_markup( array $html_attrs = array() ) {

		// Do not display a checkbox if auto_subscribe is enabled.
		$show_checkbox = $this->can_show_checkbox();

		// Filters whether to show the checkbox for all integrations.
		$show_checkbox = (bool) apply_filters( 'noptin_integration_show_subscription_checkbox', $show_checkbox, $this->slug, $this );

		// Filters whether to show the checkbox for a specific integration.
		$show_checkbox = (bool) apply_filters( "noptin_{$this->slug}_integration_show_subscription_checkbox", $show_checkbox, $this );

		// Abort if we're not displaying a checkbox.
		if ( ! $show_checkbox ) {
			return '';
		}

		ob_start();

		// Checkbox opening wrapper.
		echo '<!-- Noptin Newsletters - https://noptin.com/ -->';
		do_action( 'noptin_integration_before_subscription_checkbox_wrapper', $this );
		do_action( 'noptin_integration_' . $this->slug . '_before_subscription_checkbox_wrapper', $this );

		// Prepare the label attributes.
		$html_attrs['class'] = empty( $html_attrs['class'] ) ? '' : $html_attrs['class'];
		$html_attrs['class'] = $html_attrs['class'] . sprintf( ' noptin-integration-subscription-checkbox noptin-integration-subscription-checkbox-%s', $this->slug );


		// Convert them to strings.
		$html_attr_str = '';
		foreach ( $html_attrs as $key => $value ) {
			$html_attr_str .= sprintf( '%s="%s" ', $key, esc_attr( $value ) );
		}

		// usefull when wrapping the checkbox in an element.
		$this->before_checkbox_wrapper();

		echo "<label $html_attr_str>";
		echo sprintf( '<input %s />', $this->get_checkbox_attributes() );
		echo sprintf( '<span>%s</span>', $this->get_label_text() );
		echo '</label>';

		// usefull when wrapping the checkbox in an element.
		$this->after_checkbox_wrapper();

		// Checkbox closing wrapper.
		do_action( 'noptin_integration_after_subscription_checkbox_wrapper', $this );
		do_action( 'noptin_integration_' . $this->slug . '_after_subscription_checkbox_wrapper', $this );
		echo '<!-- / Noptin Newsletters -->';

		return ob_get_clean();

	}

	/**
	 * Get a string of attributes for the checkbox element.
	 *
	 * @return string
	 */
	protected function get_checkbox_attributes() {

		$attributes = array(
			'type'  => 'checkbox',
			'name'  => 'noptin-subscribe',
			'value' => '1',
		);
		$attributes = (array) apply_filters( 'noptin_integration_subscription_checkbox_attributes', $attributes, $this );

		$attributes = (array) apply_filters( "noptin_{$this->slug}_integration_subscription_checkbox_attributes", $attributes, $this );

		$string = '';
		foreach ( $attributes as $key => $value ) {
			$string .= sprintf( '%s="%s"', $key, esc_attr( $value ) );
		}

		return $string;
	}

	/**
	 * Runs after the checkbox label closing wrapper is printed.
	 */
	function after_checkbox_wrapper() {}

	/**
	 * Runs before the checkbox label closing wrapper is printed.
	 */
	function before_checkbox_wrapper() {}

	/**
	 * Returns whether or not to display a checkbox.
	 *
	 * @return bool
	 */
	function can_show_checkbox() {
		return noptin_should_show_optins() && ! is_admin() && ! $this->auto_subscribe();
	}

	/**
	 * Checks if a checkbox was checked.
	 *
	 * @return bool
	 */
	public function checkbox_was_checked() {
		return isset( $_POST['noptin-subscribe'] );
	}

	/**
	 * Was integration triggered?
	 *
	 * Will always return true when integration is auto_subscribe is true. Otherwise, will check value of checkbox.
	 *
	 * @param int $object_id Useful when overriding method. (optional)
	 * @return bool
	 */
	public function triggered( $object_id = null ) {
		return $this->auto_subscribe() || $this->checkbox_was_checked();
	}

	/**
	 * Subscribes a new user.
	 *
	 * @param array $subscriber_details An array of subscriber details.
	 * @param mixed $integration_details Subscriber Integration details.
	 */
	protected function add_subscriber( array $subscriber_details, $integration_details = null ) {

		// Append integration details to the subscriber.
		$subscriber_details['integration_data'] = $integration_details;

		// Filter the subscriber details for a specific integration.
		$subscriber_details = apply_filters( "noptin_{$this->slug}_integration_new_subscriber_fields", $subscriber_details, $integration_details, $this );

		// Filter the subscriber details for all integrations.
		$subscriber_details = apply_filters( "noptin_integration_new_subscriber_fields", $subscriber_details, $this->slug, $integration_details, $this );

		// Subscribe the new user.
		add_noptin_subscriber( $subscriber_details );
	}

	/**
	 * Return a string to link the integrations title.
	 *
	 * @param int $object_id
	 * @return string
	 */
	public function get_object_link( $object_id ) {
		return '';
	}

}
