<?php

namespace Hizzle\Noptin\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * An integration for displaying a newsletter subscription checkbox.
 *
 * @since 1.2.6
 */
abstract class Checkbox_Integration {

	/**
	 * @var string Name of this integration.
	 * @since 1.2.6
	 */
	public $name = '';

	/**
	 * @var string Slug, used as an unique identifier for this integration.
	 * @since 1.2.6
	 */
	public $slug = '';

	/**
	 * @var string The context for subscribers.
	 * @since 1.2.6
	 */
	public $context;

	/**
	 * @var string source of checkbox.
	 * @since 1.7.0
	 */
	public $source = null;

	/**
	 * @var int The priority for hooks.
	 * @since 1.2.6
	 */
	public $priority = 20;

	/**
	 * @var string Documentation URL.
	 */
	public $url = '';

	/**
	 * Constructor
	 */
	public function __construct() {

		// Each integration needs a unique slug.
		if ( empty( $this->slug ) ) {
			return;
		}

		// Provide a context if not set.
		if ( empty( $this->context ) ) {
			$this->context = __( 'users', 'newsletter-optin-box' );
		}

		// Use slug as the source of submissions.
		if ( empty( $this->source ) ) {
			$this->source = $this->slug;
		}

		// Filter subscription sources.
		add_filter( 'noptin_subscription_sources', array( $this, 'register_subscription_source' ) );

		// Integration settings.
		add_filter( 'noptin_get_integration_settings', array( $this, 'add_options' ), $this->priority );

		// Map subscriber fields to customer fields.
		add_filter( 'noptin_get_custom_fields_map_settings', array( $this, 'add_field_map_settings' ), $this->priority );

		// Abort if the integration is not enabled.
		if ( ! $this->is_enabled() ) {
			return;
		}

		// initialize the integration.
		$this->initialize();

		// Attaches the checkbox display hooks.
		add_action( 'init', array( $this, 'hook_checkbox_code' ), -100 );

		// Fire action hook.
		do_action( 'noptin_initialize_checkbox_integration', $this );
	}

	/**
	 * This method is called after an integration is initialized.
	 *
	 * This is usefull for registering integration specific hooks. It is only called if the integration is enabled.
	 *
	 * @since 1.2.6
	 */
	public function initialize() {
		do_action( 'noptin_initialize_checkbox_integration', $this );
	}

	/**
	 * Whether to automaticall subscribe a new submission or not.
	 *
	 * @since 1.2.6
	 * @return bool
	 */
	public function auto_subscribe() {
		return ! (bool) get_noptin_option( $this->get_autosubscribe_integration_option_name(), true );
	}

	/**
	 * Checks if this integration is enabled.
	 *
	 * @since 1.2.6
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) get_noptin_option( $this->get_enable_integration_option_name(), false );
	}

	/**
	 * Returns extra texts to append to the hero
	 *
	 * @return array
	 * @since 1.2.6
	 */
	protected function get_hero_extra() {
		return array(
			array(
				'conditions' => array(
					array(
						'key'   => $this->get_enable_integration_option_name(),
						'value' => true,
					),
				),
				'text'       => __( 'Enabled', 'newsletter-optin-box' ),
				'props'      => array(
					'style' => array(
						'color' => '#43a047',
					),
				),
			),
			array(
				'conditions' => array(
					array(
						'key'   => $this->get_enable_integration_option_name(),
						'value' => false,
					),
				),
				'text'       => __( 'Disabled', 'newsletter-optin-box' ),
				'props'      => array(
					'variant' => 'muted',
				),
			),
		);
	}

	/**
	 * Filters subscription sources.
	 *
	 * @since 1.7.0
	 * @param array $sources The subscription sources.
	 * @return array
	 */
	public function register_subscription_source( $sources ) {
		$sources[ $this->source ] = $this->name;
		return $sources;
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
		$options = apply_filters( 'noptin_checkbox_integration_settings', $options, $slug, $this );

		// Documentation URL.
		if ( empty( $this->url ) ) {
			$this->url = 'getting-email-subscribers/';
		}

		$_options[ "settings_section_$slug" ] = array(
			'id'          => "noptin-integration-settings__$slug",
			'el'          => 'integration_panel',
			'settings'    => $options,
			'section'     => 'integrations',
			'heading'     => sanitize_text_field( $this->name ),
			'description' => sprintf(
				/* translators: %s is the integration name */
				__( 'Add a subscription checkbox to %s', 'newsletter-optin-box' ),
				$this->name
			),
			'help_url'    => noptin_get_guide_url( 'Settings', $this->url ),
			'badges'      => $this->get_hero_extra(),
			'className'   => 'noptin-integration-settings__' . $slug,
		);

		return apply_filters( "noptin_{$slug}_integration_settings", $_options, $this );
	}

	/**
	 * Saves a default value.
	 *
	 * @since 1.2.6
	 * @param string $option_name The unique option name.
	 * @param mixed  $value The option value.
	 */
	protected function maybe_save_default_value( $option_name, $value ) {
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
	 * @return array an updated array of Noptin options.
	 */
	protected function add_checkbox_message_integration_option( $options ) {

		$option_name = $this->get_checkbox_message_integration_option_name();

		$checkbox_positions      = $this->checkbox_positions();
		$options[ $option_name ] = array(
			'el'          => 'input',
			'type'        => 'text',
			'conditions'  => array_filter(
				array(
					array(
						'key'   => $this->get_enable_integration_option_name(),
						'value' => true,
					),
					array(
						'key'   => $this->get_autosubscribe_integration_option_name(),
						'value' => true,
					),
					$checkbox_positions ? array(
						'key'      => $this->get_checkbox_position_option_name(),
						'value'    => '',
						'operator' => '!=',
					) : false,
				)
			),
			'section'     => 'integrations',
			'label'       => __( 'Checkbox Message', 'newsletter-optin-box' ),
			'placeholder' => $this->get_checkbox_message_integration_default_value(),
			'default'     => $this->get_checkbox_message_integration_default_value(),
		);

		return $options;
	}

	/**
	 * Returns the checkbox message option name.
	 *
	 * @since 1.2.6
	 * @return string
	 */
	protected function get_checkbox_message_integration_option_name() {
		return sprintf( 'noptin_%s_integration_checkbox_message', $this->slug );
	}

	/**
	 * Returns the checkbox message default value.
	 *
	 * @since 1.2.6
	 * @return string
	 */
	public function get_checkbox_message_integration_default_value() {
		return __( 'Add me to your newsletter and keep me updated whenever you publish new content', 'newsletter-optin-box' );
	}

	/**
	 * Adds an enable integration checkbox.
	 *
	 * @since 1.2.6
	 * @param array  $options An array of Noptin options.
	 * @param string $title The option title.
	 * @param string $description The option description.
	 * @return array an updated array of Noptin options.
	 */
	public function add_enable_integration_option( $options ) {

		$options[ $this->get_enable_integration_option_name() ] = array(
			'type'        => 'checkbox_alt',
			'el'          => 'input',
			'section'     => 'integrations',
			'label'       => __( 'Enable', 'newsletter-optin-box' ),
			'description' => sprintf(
				/* translators: %s is the integration name */
				__( 'Enable the %s Integration', 'newsletter-optin-box' ),
				$this->name
			),
			'default'     => false,
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
		return sprintf( 'noptin_enable_%s_integration', $this->slug );
	}

	/**
	 * Adds a checkbox position select box.
	 *
	 * @since 1.2.6
	 * @param array  $options An array of Noptin options.
	 * @return array an updated array of Noptin options.
	 */
	public function add_checkbox_position_option( $options ) {

		// Abort early if no checkbox positions are registered.
		$checkbox_positions = $this->checkbox_positions();
		if ( empty( $checkbox_positions ) ) {
			return $options;
		}

		$position_keys = array_keys( $checkbox_positions );
		$options[ $this->get_checkbox_position_option_name() ] = array(
			'el'          => 'select',
			'section'     => 'integrations',
			'label'       => __( 'Checkbox position', 'newsletter-optin-box' ),
			'description' => __( 'Where should we add a newsletter subscription checkbox?', 'newsletter-optin-box' ),
			'conditions'  => array(
				array(
					'key'   => $this->get_enable_integration_option_name(),
					'value' => true,
				),
				array(
					'key'   => $this->get_autosubscribe_integration_option_name(),
					'value' => true,
				),
			),
			'options'     => $checkbox_positions,
			'default'     => current( $position_keys ),
			'placeholder' => __( 'Select an option', 'newsletter-optin-box' ),
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
	 * Adds an option to tick the checkbox by default.
	 *
	 * @since 1.7.4
	 * @param array  $options An array of Noptin options.
	 * @param string $title The option title.
	 * @param string $description The option description.
	 * @return array an updated array of Noptin options.
	 */
	public function add_autotick_checkbox_integration_option( $options ) {

		$options[ $this->get_autotick_checkbox_option_name() ] = array(
			'type'        => 'checkbox_alt',
			'el'          => 'input',
			'section'     => 'integrations',
			'label'       => __( 'Checked by default', 'newsletter-optin-box' ),
			'description' => __( 'Set the checkbox as checked by default.', 'newsletter-optin-box' ),
			'conditions'  => array(
				array(
					'key'   => $this->get_enable_integration_option_name(),
					'value' => true,
				),
				array(
					'key'   => $this->get_autosubscribe_integration_option_name(),
					'value' => true,
				),
			),
		);

		return $options;
	}

	/**
	 * Returns the auto-check checkbox option name.
	 *
	 * @since 1.7.4
	 */
	public function get_autotick_checkbox_option_name() {
		return sprintf( 'noptin_%s_integration_checkbox_autochecked', $this->slug );
	}

	/**
	 * Custom fields to subscribe to.
	 *
	 * @since 2.0.0
	 * @param array  $options An array of Noptin options.
	 * @return array an updated array of Noptin options.
	 */
	public function add_multi_checkbox_custom_field_options( $options ) {

		// Loop through all custom fields.
		foreach ( get_noptin_multicheck_custom_fields() as $field ) {

			// Skip if no options.
			if ( empty( $field['options'] ) ) {
				continue;
			}

			$label       = $field['label'];
			$option_name = sprintf(
				'%s_default_%s',
				$this->source,
				$field['merge_tag']
			);

			$options[ $option_name ] = array(
				'el'          => 'select',
				'section'     => 'integrations',
				'label'       => $label,
				'restrict'    => $this->get_enable_integration_option_name(),
				'options'     => array_replace(
					array(
						'-1' => __( 'Use default', 'newsletter-optin-box' ),
					),
					noptin_newslines_to_array( $field['options'] )
				),
				'default'     => '',
				'description' => sprintf(
					/* translators: %s is the context, e.g users, customers */
					__( 'Select the default %s to add new subscribers who sign up via this method.', 'newsletter-optin-box' ),
					$label
				),
				'placeholder' => __( 'Select an option', 'newsletter-optin-box' ),
			);
		}

		return $options;
	}

	/**
	 *Tags to subscribe to.
	 *
	 * @since 2.0.0
	 * @param array  $options An array of Noptin options.
	 * @return array an updated array of Noptin options.
	 */
	public function add_tag_options( $options ) {

		$option_name = sprintf( '%s_default_tags', $this->source );

		$options[ $option_name ] = array(
			'el'          => 'input',
			'type'        => 'text',
			'section'     => 'integrations',
			'label'       => __( 'Subscriber tags', 'newsletter-optin-box' ),
			'restrict'    => $this->get_enable_integration_option_name(),
			'description' => __( 'Enter a comma separated list of tags to assign new subscribers.', 'newsletter-optin-box' ),
			'placeholder' => 'Example tag 1, tag 2, tag 3',
		);

		return $options;
	}

	/**
	 * Adds an autosubscribe checkbox
	 *
	 * @since 1.2.6
	 * @param array  $options An array of Noptin options.
	 * @return array an updated array of Noptin options.
	 */
	public function add_autosubscribe_integration_option( $options ) {

		$options[ $this->get_autosubscribe_integration_option_name() ] = array(
			'type'        => 'checkbox_alt',
			'el'          => 'input',
			'section'     => 'integrations',
			'label'       => __( 'Manual Subscription', 'newsletter-optin-box' ),
			'description' => sprintf(
				/* translators: %s is the context, e.g users, customers */
				__( 'Check to display a subscription checkbox instead of automatically subscribing new %s', 'newsletter-optin-box' ),
				$this->context
			),
			'conditions'  => array(
				array(
					'key'   => $this->get_enable_integration_option_name(),
					'value' => true,
				),
			),
			'default'     => true,
		);

		return $options;
	}

	/**
	 * Returns the autosubscribe option name.
	 *
	 * @since 1.2.6
	 */
	public function get_autosubscribe_integration_option_name() {
		return sprintf( 'noptin_%s_integration_manual_subscription', $this->slug );
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
		$options = $this->add_autotick_checkbox_integration_option( $options );
		$options = $this->add_checkbox_position_option( $options );
		$options = $this->add_checkbox_message_integration_option( $options );

		// Only add tags if the subscribers module is enabled.
		if ( function_exists( 'noptin_get_subscriber' ) ) {
			$options = $this->add_tag_options( $options );
		}

		$options = $this->add_multi_checkbox_custom_field_options( $options );
		return $options;
	}

	// -------------------- Subscription Checkboxes ---------------- //

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
	 * @param string $checkbox_position The checkbox position.
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
		$position = get_noptin_option( $this->get_checkbox_position_option_name() );

		if ( is_null( $position ) ) {
			$all_positions = $this->checkbox_positions();

			if ( ! empty( $all_positions ) ) {
				$all_positions = array_keys( $all_positions );
				return current( $all_positions );
			}
		}

		return  $position;
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
	 * Appends a checkbox to a string
	 *
	 * @since 1.2.6
	 * @param string $text The original text.
	 */
	public function prepend_checkbox( $text ) {
		return $this->get_checkbox_markup() . PHP_EOL . $text;
	}

	/**
	 * Prepends a checkbox to a string
	 *
	 * @since 1.2.6
	 * @param string $text The original text.
	 */
	public function append_checkbox( $text ) {
		return $text . PHP_EOL . $this->get_checkbox_markup();
	}

	/**
	 * Returns the subscription checkbox markup.
	 *
	 * @param array $html_attrs An array of HTML attributes.
	 * @since 1.2.6
	 * @return string
	 */
	public function get_checkbox_markup( $html_attrs = array() ) {

		// Abort if we're not displaying a checkbox.
		if ( ! $this->can_show_checkbox() ) {
			return '';
		}

		ob_start();
		$this->output_checkbox( $html_attrs );
		return ob_get_clean();
	}

	/**
	 * Displays a subscription checkbox.
	 *
	 * @param array $html_attrs An array of HTML attributes.
	 * @since 1.2.6
	 */
	public function output_checkbox( $html_attrs = array() ) {

		// Abort if we're not displaying a checkbox.
		if ( ! $this->can_show_checkbox() ) {
			return;
		}

		if ( is_string( $html_attrs ) ) {
			$html_attrs = array( 'class' => $html_attrs );
		}

		if ( ! is_array( $html_attrs ) ) {
			$html_attrs = array();
		}

		// Checkbox opening wrapper.
		echo '<!-- Noptin Newsletters - https://noptin.com/ -->';
		do_action( 'noptin_integration_before_subscription_checkbox_wrapper', $this );
		do_action( 'noptin_integration_' . $this->slug . '_before_subscription_checkbox_wrapper', $this );

		// Prepare the label attributes.
		$html_attrs['class'] = empty( $html_attrs['class'] ) ? '' : $html_attrs['class'];
		$html_attrs['class'] = $html_attrs['class'] . sprintf( ' noptin-integration-subscription-checkbox noptin-integration-subscription-checkbox-%s', $this->slug );

		// usefull when wrapping the checkbox in an element.
		$this->before_checkbox_wrapper();

		?>
			<label <?php noptin_attr( 'integration_checkbox_label', $html_attrs, $this ); ?>>
				<input <?php noptin_attr( 'integration_checkbox_input', $this->get_checkbox_attributes(), $this ); ?>/>
				<span><?php echo wp_kses_post( $this->get_label_text() ); ?></span>
			</label>
		<?php

		// usefull when wrapping the checkbox in an element.
		$this->after_checkbox_wrapper();

		// Checkbox closing wrapper.
		do_action( 'noptin_integration_after_subscription_checkbox_wrapper', $this );
		do_action( 'noptin_integration_' . $this->slug . '_after_subscription_checkbox_wrapper', $this );
		echo '<!-- / Noptin Newsletters -->';
	}

	/**
	 * Get a string of attributes for the checkbox element.
	 *
	 * @return array
	 * @since 1.2.6
	 */
	protected function get_checkbox_attributes() {

		$attributes = array(
			'type'  => 'checkbox',
			'name'  => 'noptin-subscribe',
			'value' => '1',
		);

		if ( (bool) get_noptin_option( $this->get_autotick_checkbox_option_name() ) ) {
			$attributes['checked'] = 'checked';
		}

		$attributes = (array) apply_filters( 'noptin_integration_subscription_checkbox_attributes', $attributes, $this );

		return (array) apply_filters( "noptin_{$this->slug}_integration_subscription_checkbox_attributes", $attributes, $this );
	}

	/**
	 * Runs after the checkbox label closing wrapper is printed.
	 *
	 * @since 1.2.6
	 */
	public function after_checkbox_wrapper() {}

	/**
	 * Runs before the checkbox label opening wrapper is printed.
	 *
	 * @since 1.2.6
	 */
	public function before_checkbox_wrapper() {}

	/**
	 * Returns whether or not to display a checkbox.
	 *
	 * @return bool
	 * @since 1.2.6
	 */
	public function can_show_checkbox() {

		$show_checkbox = noptin_should_show_optins() && ! $this->auto_subscribe();

		// Filters whether to show the checkbox for all integrations.
		return (bool) apply_filters( 'noptin_integration_show_subscription_checkbox', $show_checkbox, $this->slug, $this );
	}

	/**
	 * Checks if a checkbox was checked.
	 *
	 * @return bool
	 * @since 1.2.6
	 */
	public function checkbox_was_checked() {
		return isset( $_REQUEST['noptin-subscribe'] );   // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Was integration triggered?
	 *
	 * Will always return true when integration is set to auto_subscribe. Otherwise, will check value of checkbox.
	 *
	 * @param int $object_id (optional). Useful when overriding method.
	 * @since 1.2.6
	 * @return bool
	 */
	public function triggered( $object_id = null ) {
		return $this->auto_subscribe() || $this->checkbox_was_checked();
	}

	/**
	 * Registers integration options.
	 *
	 * @since 3.2.0
	 * @param array $settings Current Noptin settings.
	 * @return array
	 */
	public function add_field_map_settings( $settings ) {
		$fields = $this->custom_fields();

		if ( ! empty( $fields ) ) {
			$settings[ $this->slug ] = array(
				'el'          => 'select',
				'label'       => sprintf(
					// translators: %s is the integration name.
					__( '%s Equivalent', 'newsletter-optin-box' ),
					$this->name
				),
				'conditions'  => array(
					array(
						'key'      => 'type',
						'operator' => '!includes',
						'value'    => get_noptin_predefined_custom_fields(),
					),
				),
				'options'     => $fields,
				'placeholder' => __( 'Not Mapped', 'newsletter-optin-box' ),
			);
		}

		return $settings;
	}

	/**
	 * Returns an array of available custom fields.
	 *
	 * @return array
	 * @since 1.5.5
	 */
	protected function custom_fields() {
		return array();
	}

	/**
	 * Maps custom fields for a submission.
	 */
	protected function map_custom_fields( $submission ) {
		$prepared = array();
		foreach ( get_noptin_custom_fields() as $custom_field ) {
			if ( ! empty( $custom_field[ $this->slug ] ) ) {
				$prepared[ $custom_field['merge_tag'] ] = $submission[ $custom_field[ $this->slug ] ] ?? '';
			}
		}

		return array_merge( $submission, $prepared );
	}

	/**
	 * Processes a submission.
	 *
	 * @param array $submission The submission data.
	 */
	public function process_submission( $submission ) {
		$submission = $this->map_custom_fields( $submission );

		// Abort if no email.
		if ( empty( $submission['email'] ) || ! is_email( $submission['email'] ) ) {
			return;
		}

		$submission['update_existing'] = ! empty( $submission['update_existing'] ) && (bool) $submission['update_existing'];

		// Fire an action for the submission.
		do_action( 'noptin_checkbox_integration_process_submission', $submission, $this );
	}
}
