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
		$this->form_provider_name = $form_provider_name;

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
			$this->form_provider_name
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		// translators: %s is the form provider.
		return sprintf( __( 'When a %s form is submitted', 'newsletter-optin-box' ), $this->form_provider_name );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_description( $rule ) {
		$settings = $rule->trigger_settings;

		if ( empty( $settings['trigger_form'] ) ) {
			return $this->get_description();
		}

		$forms = $this->get_forms();

		if ( ! isset( $forms[ $settings['trigger_form'] ] ) ) {
			return $this->get_description();
		}

		$form_title = $forms[ $settings['trigger_form'] ]['name'];

		return sprintf(
			// Translators: %1 is the form provider, %2 is the title.
			__( 'When the %1$s form %2$s is submitted', 'newsletter-optin-box' ),
			$this->form_provider_name,
			'<code>' . esc_html( $form_title ) . '</code>'
		);
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

		return array(

			'trigger_form'    => array(
				'type'        => 'select',
				'el'          => 'select',
				'label'       => __( 'Form', 'newsletter-optin-box' ),
				'description' => __( 'Select the form that you want to trigger this automation rule for.', 'newsletter-optin-box' ),
				'options'     => wp_list_pluck( $known_forms, 'name' ),
				'default'     => current( array_keys( $known_forms ) ),
			),

			'trigger_subject' => array(
				'type'        => 'text',
				'el'          => 'input',
				'label'       => __( 'Trigger Subject', 'newsletter-optin-box' ),
				'description' => sprintf(
					'%s %s',
					__( 'This trigger will fire for the email address that you specify here. ', 'newsletter-optin-box' ),
					sprintf(
						/* translators: %1: Opening link, %2 closing link tag. */
						esc_html__( 'You can use %1$s smart tags %2$s to provide a dynamic value.', 'newsletter-optin-box' ),
						'<a href="#TB_inline?width=0&height=550&inlineId=noptin-automation-rule-smart-tags" class="thickbox">',
						'</a>'
					)
				),
				'default'     => '[[current_user_email]]',
			),

		);

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
				$value = implode( ', ', $value );
			}

			$prepared[ $key ] = $value;
		}

		$this->trigger( $current_user, $posted );
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

			// Abort if no valid trigger subject.
			if ( empty( $rule->trigger_settings['trigger_subject'] ) ) {
				continue;
			}

			// Maybe process merge tags.
			$trigger_subject = $args['smart_tags']->replace_in_email( $rule->trigger_settings['trigger_subject'] );

			// Abort if not an email.
			if ( ! is_email( $trigger_subject ) ) {
				continue;
			}

			$args['email'] = $trigger_subject;

            // Ensure that the rule is valid for the provided args.
            if ( $this->is_rule_valid_for_args( $rule, $args, $args['email'], $action ) ) {
                $action->maybe_run( $args['email'], $rule, $args );
            }
        }

    }
}
