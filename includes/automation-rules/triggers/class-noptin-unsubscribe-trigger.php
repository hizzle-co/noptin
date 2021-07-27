<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Fired when there is a a subscriber is deactivated.
 *
 * @since       1.3.1
 */
class Noptin_Unsubscribe_Trigger extends Noptin_Abstract_Trigger {

    /**
     * Constructor.
     *
     * @since 1.3.1
     * @return string
     */
    public function __construct() {
        add_action( 'noptin_before_deactivate_subscriber', array( $this, 'maybe_trigger' ) );
    }

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
        return __( 'Unsubscribed', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Fired when someone unsubscribes', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_rule_description( $rule ) {
        return __( 'When someone unsubscribes from the newsletter', 'newsletter-optin-box' );
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
            'subscriber',
            'unsubscribe'
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {
        return array();
    }

    /**
     * Called when someone unsubscribes from the newsletter.
     *
     * @param int $subscriber The subscriber in question.
     */
    public function maybe_trigger ( $subscriber ) {
        $subscriber = new Noptin_Subscriber( $subscriber );

        // Only trigger if a subscriber is active.
        if ( $subscriber->is_active() ) {
            $this->trigger( $subscriber, $subscriber->to_array() );
        }

    }

}
