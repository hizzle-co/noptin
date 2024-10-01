<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a form is submitted.
 *
 * @since 1.10.1
 */
class Noptin_Form_Submit_Trigger extends Noptin_Abstract_Trigger {

	/**
	 * @var string The form provider slug.
	 */
	protected $form_provider_slug;

	/**
	 * Constructor.
	 *
	 * @since 1.10.1
	 * @param string $form_provider_slug The form provider slug.
	 * @param string $form_provider_name The form provider name.
	 */
	public function __construct( $form_provider_slug, $form_provider_name ) {
		$this->form_provider_slug = $form_provider_slug;
		$this->category           = $form_provider_name;

		// Set integration.
		$this->integration = str_replace( '_', '-', $this->form_provider_slug );

		if ( 'fluentform' === $this->integration ) {
			$this->integration = 'fluent-forms';
		}

		add_action( "noptin_{$form_provider_slug}_form_submitted", array( $this, 'init_trigger' ), 10, 2 );
		add_filter( 'noptin_subscription_sources', array( $this, 'register_source' ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return sanitize_key( "{$this->form_provider_slug}_form_submitted" );
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Form Submitted', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		// translators: %s is the form provider.
		return sprintf( __( 'When a %s form is submitted', 'newsletter-optin-box' ), $this->category );
	}

	/**
	 * Registers subscription source.
	 *
	 * @param array $sources An array of sources.
	 * @return array
	 */
	public function register_source( $sources ) {
		$sources[ $this->category ] = $this->category;
		return $sources;
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_table_description( $rule ) {
		$settings = $rule->get_trigger_settings();

		// Ensure we have a form.
		if ( empty( $settings['trigger_form'] ) ) {
			return sprintf(
				'<span class="noptin-rule-error">%s</span>',
				esc_html__( 'Error: Form not found', 'newsletter-optin-box' )
			);
		}

		$forms = $this->get_forms();

		if ( ! isset( $forms[ $settings['trigger_form'] ] ) ) {
			return sprintf(
				'<span class="noptin-rule-error">%s</span>',
				esc_html__( 'Error: Form not found', 'newsletter-optin-box' )
			);
		}

		$meta  = array(
			esc_html__( 'Form', 'newsletter-optin-box' ) => $forms[ $settings['trigger_form'] ]['name'],
		);

		return $this->rule_trigger_meta( $meta, $rule ) . parent::get_rule_table_description( $rule );
	}

	/**
	 * Returns a list of forms.
	 *
	 * @return array
	 */
	public function get_forms() {
		return apply_filters( "noptin_{$this->form_provider_slug}_forms", array() );
	}

	/**
     * Returns an array of known smart tags.
     *
     * @since 1.10.1
     * @return array
     */
    public function get_known_smart_tags() {

		// Get the parent smart tags.
		$smart_tags      = array();
		$current_form_id = empty( $GLOBALS[ $this->get_id() ] ) ? null : $GLOBALS[ $this->get_id() ];

		// Loop through each form and add its fields.
		foreach ( $this->get_forms() as $form_id => $form ) {
			if ( ! empty( $current_form_id ) && strval( $current_form_id ) !== strval( $form_id ) ) {
				continue;
			}

			// Add the form fields.
			foreach ( $form['fields'] as $key => $field ) {

				// Sanitize the key.
				$prefixed = noptin_sanitize_merge_tag( "form_{$form_id}.{$key}" );
				$key      = noptin_sanitize_merge_tag( $key );

				$field['example']    = $prefixed;
				$field['group']      = sprintf( '%s - %s', $this->category, $form['name'] );
				$field['deprecated'] = $key;
				$field['conditions'] = array(
					array(
						'key'      => 'trigger_form',
						'operator' => 'is',
						'value'    => array( $form_id ),
					),
				);

				// Add the field as a smart tag.
				if ( ! isset( $smart_tags[ $prefixed ] ) ) {
					$smart_tags[ $prefixed ] = $field;
					continue;
				}

				// Merge the conditions.
				$smart_tags[ $prefixed ]['conditions'][0]['value'][] = $form_id;
			}
		}

		// Add source if not already added.
		if ( ! isset( $smart_tags['source'] ) ) {
			$smart_tags['source'] = array(
				'description'       => 'Source',
				'conditional_logic' => 'string',
			);
		}

		if ( ! isset( $smart_tags['form'] ) ) {
			$smart_tags['form'] = array(
				'description'       => 'Form ID',
				'conditional_logic' => 'string',
			);
		}

		// Add logged in user info.
		$smart_tags['current_user_email'] = array(
			'description'       => __( "Logged in user's email", 'newsletter-optin-box' ),
			'conditional_logic' => 'string',
		);

		$smart_tags['current_user_name'] = array(
			'description'       => __( "Logged in user's name", 'newsletter-optin-box' ),
			'conditional_logic' => 'string',
		);

		return array_replace( $smart_tags, parent::get_known_smart_tags() );
    }

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		$known_forms = $this->get_forms();

		$settings = array(

			'trigger_form' => array(
				'type'        => 'select',
				'el'          => 'select',
				'label'       => __( 'Form', 'newsletter-optin-box' ),
				'description' => __( 'Select the form that you want to trigger this automation rule for.', 'newsletter-optin-box' ),
				'options'     => wp_list_pluck( $known_forms, 'name' ),
				'default'     => current( array_keys( $known_forms ) ),
			),

		);

		return $settings;
	}

	/**
	 * Inits the trigger.
	 *
	 * @param int $form_id The form id.
	 * @param array $posted The posted data.
	 * @since 1.10.1
	 */
	public function init_trigger( $form_id, $posted ) {
		$GLOBALS[ $this->get_id() ] = $form_id;

		$posted = is_array( $posted ) ? $posted : array();

		// Prefix the form id.
		foreach ( $posted as $key => $value ) {
			$posted[ noptin_sanitize_merge_tag( "form_{$form_id}.{$key}" ) ] = $value;
			unset( $posted[ $key ] );
		}

		if ( empty( $posted['form'] ) ) {
			$posted['form'] = $form_id;
		}

		if ( empty( $posted['source'] ) ) {
			$posted['source'] = $this->category;
		}

		// Adds the current user info.
		$current_user                 = wp_get_current_user();
		$posted['current_user_email'] = $current_user->user_email;
		$posted['current_user_name']  = $current_user->display_name;

		$prepared = array();

		foreach ( $posted as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( ! is_scalar( current( $value ) ) ) {
					$value = wp_json_encode( $value );
				} else {
					$value = implode( ', ', $value );
				}
			}

			if ( "form_{$form_id}.email" === $key ) {
				$prepared['email'] = sanitize_email( $value );
			}

			if ( is_email( $value ) && empty( $prepared['email'] ) ) {
				$prepared['email'] = sanitize_email( $value );
			}

			$prepared[ $key ] = $value;
		}

		// Maybe set an email.
		if ( empty( $prepared['email'] ) ) {
			$prepared['email'] = $current_user->user_email;
		}

		// Abort if we don't have an email.
		if ( empty( $prepared['email'] ) ) {
			return;
		}

		$this->trigger( $prepared['email'], $prepared );
	}

	/**
	 * Checks if conditional logic if met.
	 *
	 * @since 1.2.8
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule The rule to check for.
	 * @param mixed $args Extra args for the action.
	 * @param mixed $subject The subject.
	 * @param Noptin_Abstract_Action $action The action to run.
	 * @return bool
	 */
	public function is_rule_valid_for_args( $rule, $args, $subject, $action ) {

		// Prepare the rule.
		$trigger_form = $rule->get_trigger_setting( 'trigger_form' );

		// Abort if the forms don't match.
		if ( empty( $args['form'] ) || empty( $trigger_form ) || strval( $trigger_form ) !== strval( $args['form'] ) ) {
			return false;
		}

		return parent::is_rule_valid_for_args( $rule, $args, $subject, $action );
	}
}
