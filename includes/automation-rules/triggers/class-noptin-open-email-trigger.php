<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Fires when a subscriber opens an email address.
 *
 * @since       1.2.8
 */
class Noptin_Open_Email_Trigger extends Noptin_Abstract_Trigger {

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'open_email';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'Open Email', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Fired when a subscriber opens an email', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_image() {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function get_keywords() {
        return array(
            'noptin',
            'open',
            'email'
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {
        return array(); // link
    }

    /**
     * @inheritdoc
     */
    public function is_rule_valid_for_args( $rule, $args, $subscriber, $action ) {
        return true;
    }

}
