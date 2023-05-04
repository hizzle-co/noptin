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
	 * @var string The form provider name.
	 */
	protected $form_provider_name;

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
		return sprintf(
			// translators: %s is the form provider.
			__( '%s > Form Submitted', 'newsletter-optin-box' ),
			$this->category
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		// translators: %s is the form provider.
		return sprintf( __( 'When a %s form is submitted', 'newsletter-optin-box' ), $this->category );
	}

	/**
	 * Retrieve the trigger's rule table description.
	 *
	 * @since 1.11.9
	 * @param Noptin_Automation_Rule $rule
	 * @return array
	 */
	public function get_rule_table_description( $rule ) {
		$settings = $rule->trigger_settings;

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
		$smart_tags  = parent::get_known_smart_tags();

		// Add logged in user info.
		$smart_tags['current_user_email'] = array(
			'description'       => __( "Logged in user's email", 'newsletter-optin-box' ),
			'conditional_logic' => 'string',
		);

		$smart_tags['current_user_name'] = array(
			'description'       => __( "Logged in user's name", 'newsletter-optin-box' ),
			'conditional_logic' => 'string',
		);

		// Loop through each form and add its fields.
		foreach ( $this->get_forms() as $form_id => $form ) {

			// Add the form fields.
			foreach ( $form['fields'] as $key => $field ) {

				// Sanitize the key.
				$key = noptin_sanitize_merge_tag( $key );

				$field['example']    = $key;
				$field['conditions'] = array(
					array(
						'key'      => 'trigger_form',
						'operator' => 'is',
						'value'    => array( $form_id ),
					),
				);

				// Add the field as a smart tag.
				if ( ! isset( $smart_tags[ $key ] ) ) {
					$smart_tags[ $key ] = $field;
					continue;
				}

				// Merge the conditions.
				$smart_tags[ $key ]['conditions'][0]['value'][] = $form_id;
			}
		}

		return $smart_tags;
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

		$posted         = is_array( $posted ) ? $posted : array();
		$posted['form'] = $form_id;

		// Adds the current user info.
		$current_user                 = wp_get_current_user();
		$posted['current_user_email'] = $current_user->user_email;
		$posted['current_user_name']  = $current_user->display_name;

		// Sanitize the array keys.
		$posted = array_combine(
			array_map( 'noptin_sanitize_merge_tag', array_keys( $posted ) ),
			array_values( $posted )
		);

		$prepared = array();

		foreach ( $posted as $key => $value ) {

			if ( is_array( $value ) ) {

				if ( ! is_scalar( current( $value ) ) ) {
					$value = wp_json_encode( $value );
				} else {
					$value = implode( ', ', $value );
				}
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

		$this->trigger( $current_user, $prepared );
	}

	/**
     * Triggers action callbacks.
     *
     * @since 1.10.1
     * @param mixed $subject The subject.
     * @param array $args Extra arguments passed to the action.
     * @return void
     */
    public function trigger( $subject, $args ) {

		if ( isset( $args['subject'] ) ) {
			$args['subject_orig'] = $args['subject'];
		}

        $args['subject'] = $subject;

        $args = apply_filters( 'noptin_automation_trigger_args', $args, $this );

        $args['smart_tags'] = new Noptin_Automation_Rules_Smart_Tags( $this, $subject, $args );

        foreach ( $this->get_rules() as $rule ) {

            // Retrieve the action.
            $action = noptin()->automation_rules->get_action( $rule->action_id );
            if ( empty( $action ) ) {
                continue;
            }

            // Prepare the rule.
            $rule = noptin()->automation_rules->prepare_rule( $rule );

			// Abort if the forms don't match.
			if ( empty( $rule->trigger_settings['trigger_form'] ) || absint( $rule->trigger_settings['trigger_form'] ) !== absint( $args['form'] ) ) {
				continue;
			}

			// Set the current email.
			$GLOBALS['current_noptin_email'] = $this->get_subject_email( $subject, $rule, $args );

			// Are we delaying the action?
			$delay = $rule->get_delay();

			if ( $delay > 0 ) {
				do_action( 'noptin_delay_automation_rule_execution', $rule, $args, $delay );
				continue;
			}

            // Ensure that the rule is valid for the provided args.
            if ( $this->is_rule_valid_for_args( $rule, $args, $args['email'], $action ) ) {
                $action->maybe_run( $args['email'], $rule, $args );
            }
        }

    }
}
