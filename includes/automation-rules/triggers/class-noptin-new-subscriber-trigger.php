<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Fired when there is a new subscriber.
 *
 * @since       1.2.8
 */
class Noptin_New_Subscriber_Trigger extends Noptin_Abstract_Trigger {

    /**
     * Constructor.
     *
     * @since 1.3.0
     * @return string
     */
    public function __construct() {
        add_action( 'noptin_insert_subscriber', array( $this, 'maybe_trigger' ) );
        add_action( 'noptin_subscriber_confirmed', array( $this, 'maybe_trigger' ) );
    }

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
    public function get_rule_description( $rule ) {
        return __( 'When someone subscribes to the newsletter', 'newsletter-optin-box' );
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
            'new'
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {

        $methods = array(
            '-1' => __( 'Fire for all subscription methods', 'newsletter-optin-box' ),
        );

        return array(

            'subscribed_via'  => array(
				'el'          => 'select',
				'label'       => __( 'Subscription Method', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select a subscription method', 'newsletter-optin-box' ),
				'options'     => $methods + $this->get_subscription_methods(),
				'description' => __( 'Select a subscription method if you would like to limit this trigger to a specific subscription method.', 'newsletter-optin-box' ),
            ),

        );

    }

    /**
     * Returns an array of available subscription methods.
     */
    public function get_subscription_methods() {

        $args = array(
			'numberposts' => -1,
			'post_type'   => 'noptin-form',
            'post_status' => array( 'draft', 'publish' ),
            'orderby'     => 'post_title',
            'order'       => 'ASC',
        );

        $forms  = get_posts( $args );
        $return = array();

        foreach ( $forms as $form ){
            $return[ $form->ID ] = get_the_title( $form );
        }

        $return['import'] = __( 'Imported', 'newsletter-optin-box' );
        $return['manual'] = __( 'Manually Added', 'newsletter-optin-box' );

        return apply_filters( 'noptin-subscription-methods', $return );

    }

    /**
     * @inheritdoc
     */
    public function is_rule_valid_for_args( $rule, $args, $subscriber, $action ) {

        $settings = $rule->trigger_settings;

        // Are we filtering by subscription method?
        $subscribed_via = $args['subscribed_via'];
        if ( ! empty( $settings['subscribed_via'] ) && $subscriber->_subscriber_via !== $subscribed_via ) {
            return false;
        }

        return true;

    }

    /**
     * Called when a subscriber is activated.
     *
     * @param int $subscriber The subscriber in question.
     */
    public function maybe_trigger ( $subscriber ) {
        $subscriber = new Noptin_Subscriber( $subscriber );
        $this->trigger( $subscriber->id, $subscriber->to_array() );
    }

}
