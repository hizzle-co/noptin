<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Base integration class.
 *
 * @since       1.2.6
 */
abstract class Noptin_Abstract_Integration {

	/**
	 * @var bool Whether or not this integration has a settings page.
	 * @since 1.2.6
	 */
	public $supports_settings = true;

	/**
	 * @var string Name of this integration.
	 * @since 1.2.6
	 */
	public $name = '';

	/**
	 * @var string Description
	 * @since 1.2.6
	 */
	public $description = '';

	/**
	 * @var string Slug, used as an unique identifier for this integration.
	 * @since 1.2.6
	 */
	public $slug = '';

	/**
	 * @var string The context for subscribers.
	 * @since 1.2.6
	 */
	public $context = 'users';

	/**
	 * @var string type of integration.
	 * @since 1.2.6
	 */
	public $integration_type = 'normal';

	/**
	 * @var int The priority for hooks.
	 * @since 1.2.6
	 */
	public $priority = 20;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->context = __( 'users', 'newsletter-optin-box' );

		// Fired before an integration is initialized.
		$this->before_initialize();

		// Each integration needs a unique slug.
		if ( empty( $this->slug ) ) {
			return;
		}

		do_action( "noptin_{$this->slug}_integration_before_initialize", $this );

		// Integration settings.
		if ( $this->supports_settings ) {
			add_filter( 'noptin_get_integration_settings', array( $this, 'add_options' ), $this->priority );
		}

		// Abort if the integration is not enabled.
		if ( ! $this->is_enabled() ) {
			return;
		}

		// initialize the integration.
		$this->initialize();

		// Attaches the checkbox display hooks.
		$this->hook_checkbox_code();

		do_action( "noptin_{$this->slug}_integration_after_initialize", $this );

	}

	/**
	 * This method is called before an integration is initialized.
	 *
	 * Useful for setting integration variables.
	 *
	 * @since 1.2.6
	 */
	public function before_initialize() {}

	/**
	 * This method is called after an integration is initialized.
	 *
	 * This is usefull for registering integration specific hooks. It is only called if the integration is enabled.
	 *
	 * @since 1.2.6
	 */
	public function initialize() {}

	/**
	 * Whether to automaticall subscribe a new customer or not.
	 *
	 * @since 1.2.6
	 * @return bool
	 */
	public function auto_subscribe() {
		return ! (bool) get_noptin_option( $this->get_autosubscribe_integration_option_name() );
	}

	/**
	 * Checks if this integration is enabled.
	 *
	 * @since 1.2.6
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) get_noptin_option( $this->get_enable_integration_option_name() );
	}

	/**
	 * Returns extra texts to append to the hero
	 *
	 * @return string
	 * @since 1.2.6
	 */
	public function get_hero_extra() {

		$option   = $this->get_enable_integration_option_name();
		$enabled  = __( 'Enabled', 'newsletter-optin-box' );
		$disabled = __( 'Disabled', 'newsletter-optin-box' );

		return "
			<span style='color: #43a047;' v-if='$option'>$enabled</span>
			<span style='color: #616161;' v-else>$disabled</span>
		";

	}

	/**
	 * Registers integration options.
	 *
	 * @since 1.2.6
	 * @param array $_options Current Noptin settings.
	 * @return array
	 */
	public function add_options( $_options ) {

		$slug    = $this->slug;
		$options = $this->get_options( array() );
		$options = apply_filters( "noptin_single_integration_settings", $options, $slug, $this );

		$_options["settings_section_$slug"] = array(
			'id'          => "settings_section_$slug",
			'el'          => 'settings_section',
			'children'    => $options,
			'section'     => 'integrations',
			'heading'     => sanitize_text_field( $this->name ),
			'description' => sanitize_text_field( $this->description ),
			'badge'       => $this->get_hero_extra(),
		);

		return apply_filters( "noptin_{$slug}_integration_settings", $_options, $this );

	}

	/**
	 * Saves a default value.
	 *
	 * @since 1.2.6
	 * @param string $option_name The unique option name.
	 * @param mixed $value The option value.
	 */
	public function maybe_save_default_value( $option_name, $value ) {
		$saved = get_noptin_option( $option_name );
		if ( is_null( $saved ) ) {
			update_noptin_option( $option_name, $value );
		}
	}

	/**
	 * Adds an integration subscription checkbox message.
	 *
	 * @since 1.2.6
	 * @param array  $options An array of Noptin options.
	 * @param string $title The option title
	 * @param string $description The option description.
	 * @return array an updated array of Noptin options.
	 */
	public function add_checkbox_message_integration_option( $options, $title = null, $description = '' ) {

		if ( is_null( $title ) ) {
			$title = __( 'Checkbox Message', 'newsletter-optin-box' );
		}

		$option_name = $this->get_checkbox_message_integration_option_name();
		$default     = $this->get_checkbox_message_integration_default_value();

		$this->maybe_save_default_value( $option_name, $default );

		$checkbox_positions    = $this->checkbox_positions();
		$options[$option_name] = array(
            'el'          => 'input',
			'type'        => 'text',
			'restrict'    => sprintf( 
				'%s && %s && %s',
				$this->get_enable_integration_option_name(),
				$this->get_autosubscribe_integration_option_name(),
				empty( $checkbox_positions ) ? 1 : $this->get_checkbox_position_option_name()
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
	 *
	 * @since 1.2.6
	 * @return string
	 */
	public function get_checkbox_message_integration_option_name() {
		return sprintf( 'noptin_%s_integration_checkbox_message', $this->slug);
	}

	/**
	 * Returns the checkbox message default value.
	 *
	 * @since 1.2.6
	 * @return string
	 */
	public function get_checkbox_message_integration_default_value() {
		return __( 'Add me to your newsletter and keep me updated whenever you publish new blog posts', 'newsletter-optin-box' );
	}

	/**
	 * Adds an enable integration checkbox.
	 *
	 * @since 1.2.6
	 * @param array  $options An array of Noptin options.
	 * @param string $title The option title
	 * @param string $description The option description.
	 * @return array an updated array of Noptin options.
	 */
	public function add_enable_integration_option( $options, $title = null, $description = null ) {

		if ( is_null( $title ) ) {
			$title = __( 'Enable', 'newsletter-optin-box' );
		}

		if ( is_null( $description ) ) {
			$description = sprintf(
				__( 'Enable the %s Integration', 'newsletter-optin-box' ),
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
	 *
	 * @since 1.2.6
	 * @return string
	 */
	public function get_enable_integration_option_name() {
		return sprintf( 'noptin_enable_%s_integration', $this->slug);
	}

	/**
	 * Returns the enable integration default value.
	 *
	 * @since 1.2.6
	 * @return bool
	 */
	public function get_enable_integration_default_value() {
		return false;
	}

	/**
	 * Adds a checkbox position select box.
	 *
	 * @since 1.2.6
	 * @param array  $options An array of Noptin options.
	 * @param string $title The option title
	 * @param string $description The option description.
	 * @param string $placeholder The select box placeholder.
	 * @return array an updated array of Noptin options.
	 */
	public function add_checkbox_position_option( $options, $title = null, $description = null, $placeholder = null ) {

		// Abort early if no checkbox positions are registered.
		$checkbox_positions = $this->checkbox_positions();
		if ( empty( $checkbox_positions ) ) {
			return $options;
		}

		if ( is_null( $title ) ) {
			$title = __( 'Checkbox position', 'newsletter-optin-box' );
		}

		if ( is_null( $description ) ) {
			$description = __( 'Where should we add a newsletter subscription checkbox?', 'newsletter-optin-box' );
		}

		if ( is_null( $placeholder ) ) {
			$placeholder = sprintf(
				__( 'Do not subscribe new %s', 'newsletter-optin-box' ),
				$this->context
			);
		}

		$option_name = $this->get_checkbox_position_option_name();

		$options[ $option_name ] = array(
            'el'                    => 'select',
            'section'		        => 'integrations',
            'label'                 => $title,
			'description'           => $description,
			'restrict'              => sprintf( 
				'%s && %s',
				$this->get_enable_integration_option_name(),
				$this->get_autosubscribe_integration_option_name()
			),
			'options'               => $checkbox_positions,
			'placeholder'           => $placeholder,
		);

		return $options;

	}

	/**
	 * Returns the enable option name.
	 *
	 * @since 1.2.6
	 */
	public function get_checkbox_position_option_name() {
		return sprintf( 'noptin_%s_integration_checkbox_position', $this->slug );
	}

	/**
	 * Adds an autosubscribe checkbox
	 *
	 * @since 1.2.6
	 * @param array  $options An array of Noptin options.
	 * @param string $title The option title
	 * @param string $description The option description.
	 * @return array an updated array of Noptin options.
	 */
	public function add_autosubscribe_integration_option( $options, $title = null, $description = null ) {

		if ( is_null( $title ) ) {
			$title = __( 'Manual Subscription', 'newsletter-optin-box' );
		}

		if ( is_null( $description ) ) {
			$description = sprintf(
				__( 'Check to display a subscription checkbox instead of automatically subscribing new %s', 'newsletter-optin-box' ),
				$this->context
			);
		}

		$option_name = $this->get_autosubscribe_integration_option_name();

		$this->maybe_save_default_value( $option_name, true );

		$options[ $option_name ] = array(
            'type'                  => 'checkbox_alt',
            'el'                    => 'input',
            'section'		        => 'integrations',
            'label'                 => $title,
			'description'           => $description,
			'restrict'              => $this->get_enable_integration_option_name(),
		);

		return $options;

	}

	/**
	 * Returns the autosubscribe option name.
	 *
	 * @since 1.2.6
	 */
	public function get_autosubscribe_integration_option_name() {
		return sprintf( 'noptin_%s_integration_manual_subscription', $this->slug);
	}

	/**
	 * Returns integration specific settings.
	 *
	 * Ideally, you will want to  rewrite this in your integration class.
	 *
	 * @param array $options Current Noptin settings.
	 * @since 1.2.6
	 * @return array
	 */
	public function get_options( $options ) {
		$options = $this->add_enable_integration_option( $options );
		$options = $this->add_autosubscribe_integration_option( $options );
		$options = $this->add_checkbox_position_option( $options );
		$options = $this->add_checkbox_message_integration_option( $options );
		return $options;
	}

	//-------------------- Subscription Checkboxes ----------------//

	/**
	 * Displays a checkbox if the integration uses checkbox positions.
	 *
	 * @since 1.2.6
	 */
	public function hook_checkbox_code() {

		// Display a subscription checkbox.
		$checkbox_position = $this->get_checkbox_position();
		if ( ! empty( $checkbox_position ) ) {
			$this->hook_show_checkbox_code( $checkbox_position );
		}

	}

	/**
	 * Hooks the display checkbox code.
	 *
	 * @since 1.2.6
	 */
	public function hook_show_checkbox_code( $checkbox_position ) {
		add_action( $checkbox_position, array( $this, 'output_checkbox' ), $this->priority );
	}

	/**
	 * Returns an array of subscription checkbox positions.
	 *
	 * @since 1.2.6
	 * @return array
	 */
	public function checkbox_positions() {
		return array();
	}

	/**
	 * Returns a single subscription checkbox position.
	 *
	 * If an integration does not support checkbox positions, rewrite this to return a constant string.
	 *
	 * @since 1.2.6
	 * @return string|null
	 */
	public function get_checkbox_position() {
		return get_noptin_option( $this->get_checkbox_position_option_name() );
	}

	/**
	 * Subscription checkbox.
	 *
	 * @since 1.2.6
	 * @return string
	 */
	public function get_label_text() {
		$label = get_noptin_option( $this->get_checkbox_message_integration_option_name() );
		return empty( $label ) ? $this->get_checkbox_message_integration_default_value() : $label;
	}

	/**
	 * Displays a subscription checkbox.
	 *
	 * @since 1.2.6
	 */
	function output_checkbox() {
		echo $this->get_checkbox_markup();
	}

	/**
	 * Appends a checkbox to a string
	 *
	 * @since 1.2.6
	 * @param string $text The original text
	 */
	function prepend_checkbox( $text ) {
		return $this->get_checkbox_markup() . PHP_EOL . $text;
	}

	/**
	 * Prepends a checkbox to a string
	 *
	 * @since 1.2.6
	 * @param string $text The original text
	 */
	function append_checkbox( $text ) {
		return $text . PHP_EOL . $this->get_checkbox_markup();
	}

	/**
	 * Returns the subscription checkbox markup.
	 *
	 * @param array $html_attrs An array of HTML attributes.
	 * @since 1.2.6
	 * @return string
	 */
	function get_checkbox_markup( array $html_attrs = array() ) {

		// Abort if we're not displaying a checkbox.
		if ( ! $this->can_show_checkbox() ) {
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
	 * @since 1.2.6
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
	 *
	 * @since 1.2.6
	 */
	function after_checkbox_wrapper() {}

	/**
	 * Runs before the checkbox label closing wrapper is printed.
	 *
	 * @since 1.2.6
	 */
	function before_checkbox_wrapper() {}

	/**
	 * Returns whether or not to display a checkbox.
	 *
	 * @return bool
	 * @since 1.2.6
	 */
	function can_show_checkbox() {

		$show_checkbox = noptin_should_show_optins() && ! $this->auto_subscribe();

		// Filters whether to show the checkbox for all integrations.
		$show_checkbox = (bool) apply_filters( 'noptin_integration_show_subscription_checkbox', $show_checkbox, $this->slug, $this );

		// Filters whether to show the checkbox for a specific integration.
		$show_checkbox = (bool) apply_filters( "noptin_{$this->slug}_integration_show_subscription_checkbox", $show_checkbox, $this );

		return $show_checkbox;

	}

	/**
	 * Checks if a checkbox was checked.
	 *
	 * @return bool
	 * @since 1.2.6
	 */
	public function checkbox_was_checked() {
		return isset( $_REQUEST['noptin-subscribe'] );
	}

	/**
	 * Was integration triggered?
	 *
	 * Will always return true when integration is set to auto_subscribe. Otherwise, will check value of checkbox.
	 *
	 * @param int $object_id Useful when overriding method. (optional)
	 * @since 1.2.6
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
	 * @since 1.2.6
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
	 * Updates an existing subscriber.
	 *
	 * @param int $subscriber_id Subscriber id.
	 * @param array $subscriber_details An array of subscriber details.
	 * @param mixed $integration_details Subscriber Integration details.
	 * @since 1.2.6
	 */
	protected function update_subscriber( $subscriber_id, array $subscriber_details, $integration_details = null ) {

		// Append integration details to the subscriber.
		$subscriber_details['integration_data'] = $integration_details;

		// Filter the subscriber details for a specific integration.
		$subscriber_details = apply_filters( "noptin_{$this->slug}_integration_update_subscriber_fields", $subscriber_details, $integration_details, $this, $subscriber_id );

		// Filter the subscriber details for all integrations.
		$subscriber_details = apply_filters( "noptin_integration_update_subscriber_fields", $subscriber_details, $this->slug, $integration_details, $this, $subscriber_id );

		// Update the subscriber.
		update_noptin_subscriber( $subscriber_id, $subscriber_details );
	}

}
