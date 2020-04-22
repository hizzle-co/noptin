<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Fires when a subscriber clicks on link.
 *
 * @since       1.2.8
 */
class Noptin_Link_Click_Trigger extends Noptin_Abstract_Trigger {

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'link_click';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'Link Click', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Fired when a subscriber clicks on a link in an email', 'newsletter-optin-box' );
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
            'click',
            'link'
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
