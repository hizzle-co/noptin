<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Fires there is a new subscriber.
 *
 * @since       1.2.8
 */
class Noptin_New_Subscriber_Trigger extends Noptin_Abstract_Trigger {

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'new_subscriber';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'New Subscriber', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Fired when there is a new subscriber', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_image() {
        return 'https://cdn.noptin.com/templates/images/noptin-logo.png';
    }

    /**
     * @inheritdoc
     */
    public function get_keywords() {
        return array(
            'noptin',
            'subscriber',
            'new'
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {
        return array(); // subscription_method
    }

    /**
     * @inheritdoc
     */
    public function is_rule_valid_for_args( $rule, $args, $subscriber, $action ) {
        return true;
    }

}
