<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base triggers class.
 *
 * @since       1.2.8
 */
abstract class Noptin_Abstract_Trigger {

    /**
     * @var array
     */
    protected $rules = null;

    /**
     * Whether or not this trigger deals with a subscriber.
     *
     * @var bool
     */
    public $is_subscriber_based = false;

    /**
     * Retrieve the trigger's unique id.
     *
     * Only alphanumerics, dashes and underscrores are allowed.
     *
     * @since 1.2.8
     * @return string
     */
    abstract public function get_id();

    /**
     * Retrieve the trigger's name.
     *
     * @since 1.2.8
     * @return string
     */
    abstract public function get_name();

    /**
     * Retrieve the trigger's description.
     *
     * @since 1.2.8
     * @return string
     */
    abstract public function get_description();

    /**
     * Retrieve the trigger's image.
     *
     * @since 1.2.8
     * @return string
     */
    public function get_image() {
        return '';
    }

    /**
     * Retrieve the trigger's keywords.
     *
     * @since 1.2.8
     * @return array
     */
    public function get_keywords() {
        return array();
    }

    /**
     * Retrieve the trigger's rule description.
     *
     * @since 1.3.0
     * @param Noptin_Automation_Rule $rule
     * @return array
     */
    public function get_rule_description( $rule ) {
        return $this->get_description();
    }

    /**
     * Retrieve the trigger's settings.
     *
     * @since 1.2.8
     * @return array
     */
	public function get_settings() {
        return array();
    }

    /**
     * Retrieves conditional logic filters.
     *
     * @since 1.8.0
     * @return array
     */
    public function get_conditional_logic_filters() {
        $filters = array();

        if ( $this->is_subscriber_based ) {
            $filters = get_noptin_subscriber_filters();
        }

        return $filters;
    }

    /**
     * Prepares the conditional logic for display.
     *
     * @since 1.8.0
     * @param Noptin_Automation_Rule $rule The rule to check for.
     * @return string
     */
    public function prepare_conditional_logic( $rule ) {

        $filters = $this->get_conditional_logic_filters();
        if ( ! empty( $filters ) ) {
            return noptin_prepare_conditional_logic_for_display( $rule->conditional_logic, $filters );
        }

        return '';
    }

    /**
     * Returns an array of known smart tags.
     *
     * @since 1.8.1
     * @return array
     */
    public function get_known_smart_tags() {
        $smart_tags = array(

            'cookie' => array(
                'description'       => __( 'Data from a cookie.', 'newsletter-optin-box' ),
                'callback'          => 'Noptin_Dynamic_Content_Tags::get_cookie',
                'example'           => "cookie name='my_cookie' default='Default Value'",
                'conditional_logic' => 'string',
            ),

            'date'   => array(
                // translators: %s is the current date.
                'description'       => sprintf( __( 'The current date. Example: %s.', 'newsletter-optin-box' ), '<strong>' . date_i18n( get_option( 'date_format' ) ) . '</strong>' ),
                'replacement'       => date_i18n( get_option( 'date_format' ) ),
                'example'           => 'date',
                'conditional_logic' => 'date',
            ),

            'time'   => array(
                // translators: %s is the current time.
                'description' => sprintf( __( 'The current time. Example: %s.', 'newsletter-optin-box' ), '<strong>' . date_i18n( get_option( 'time_format' ) ) . '</strong>' ),
                'replacement' => date_i18n( get_option( 'time_format' ) ),
                'example'     => 'time',
            ),

        );

        if ( $this->is_subscriber_based ) {
            $smart_tags = get_noptin_subscriber_smart_tags();
        }

        return apply_filters( 'noptin_automation_trigger_known_smart_tags', $smart_tags, $this );
    }

    /**
     * Prepare smart tags.
     *
     * @param Noptin_Subscriber|WP_User|WC_Customer $subject
     * @since 1.8.1
     * @return array
     */
    public function prepare_known_smart_tags( $subject ) {
        $smart_tags = array();

        if ( $this->is_subscriber_based && $subject instanceof Noptin_Subscriber ) {

            foreach ( get_noptin_subscriber_smart_tags() as $merge_tag => $data ) {

                if ( ! isset( $data['type'] ) ) {
                    $smart_tags[ $merge_tag ] = $subject->get( $merge_tag );
                } else {
                    $value = sanitize_noptin_custom_field_value( $subject->get( $merge_tag ), $data['type'], $subject );

                    if ( is_array( $value ) ) {
                        $value = format_noptin_custom_field_value( $subject->get( $merge_tag ), $data['type'], $subject );
                    }

                    if ( is_scalar( $value ) ) {
                        $smart_tags[ $merge_tag ] = $value;
                    }
                }
            }
        }

        return $smart_tags;
    }

    /**
     * Returns all active rules attached to this trigger.
     *
     * @since 1.2.8
     * @return array
     */
    public function get_rules() {
        global $wpdb;

        if ( is_array( $this->rules ) ) {
            return $this->rules;
        }

        $this->rules = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}noptin_automation_rules WHERE `trigger_id`=%s AND `status`='1'",
                $this->get_id()
            )
        );

        return $this->rules;
    }

    /**
     * Checks if there are rules for this trigger.
     *
     * @since 1.2.8
     * @return array
     */
    public function has_rules() {
        $rules = $this->get_rules;
        return ! empty( $rules );
    }

    /**
     * Checks if this rule is valid for the above parameters.
     *
     * @since 1.2.8
     * @param Noptin_Automation_Rule $rule The rule to check for.
     * @param mixed $args Extra args for the action.
     * @param mixed $subject The subject.
     * @param Noptin_Abstract_Action $action The action to run.
     * @return bool
     */
    public function is_rule_valid_for_args( $rule, $args, $subject, $action ) {
        return $this->is_conditional_logic_met( $rule, $subject );
    }

    /**
     * Checks if conditional logic is met.
     *
     * @since 1.8.0
     * @param Noptin_Automation_Rule $rule The rule to check for.
     * @param Noptin_Subscriber $subscriber The subscriber that this rule was triggered for.
     * @return bool
     */
    public function is_conditional_logic_met( $rule, $subscriber ) {
        if ( $this->is_subscriber_based ) {
            return noptin_subscriber_meets_conditional_logic( $rule->conditional_logic, $subscriber );
        }

        return true;
    }

    /**
     * Triggers action callbacks.
     *
     * @since 1.2.8
     * @param mixed $subject The subject.
     * @param array $args Extra arguments passed to the action.
     * @return void
     */
    public function trigger( $subject, $args ) {

        if ( ! is_array( $args ) ) {
            $args = array();
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

            // Ensure that the rule is valid for the provided args.
            if ( $this->is_rule_valid_for_args( $rule, $args, $subject, $action ) ) {
                $action->maybe_run( $subject, $rule, $args );
            }
        }

    }

}
