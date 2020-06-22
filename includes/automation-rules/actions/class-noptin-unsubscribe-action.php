<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * De-activates a subscriber's custom field.
 *
 * @since       1.3.1
 */
class Noptin_Unsubscribe_Action extends Noptin_Abstract_Action {

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'unsubscribe';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'Unsubscribe', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( "Unsubscribe from the newsletter", 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_rule_description( $rule ) {
        return __( 'unsubscribe them', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_keywords() {
        return array(
            'noptin',
            'deactivate',
            'subscriber'
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {
        return array();
    }

    /**
     * Deactivates the subscriber.
     *
     * @since 1.3.1
     * @param Noptin_Subscriber $subscriber The subscriber.
     * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
     * @param array $args Extra arguments passed to the action.
     * @return void
     */
    public function run( $subscriber, $rule, $args ) {
        return unsubscribe_noptin_subscriber( $subscriber );
    }

}
