<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Sends a coupon to subscribers.
 *
 * @since       1.2.8
 */
class Noptin_WooCommerce_Coupon_Action extends Noptin_Abstract_Action {

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'woocommerce_coupon';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'WooCommerce Coupon', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Emails a WooCommerce coupon to the subscriber', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_rule_description( $rule ) {
        return __( 'send them a WooCommerce coupon', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_image() {
        return 'https://cdn.noptin.com/templates/images/woocommerce-icon.png';
    }

    /**
     * @inheritdoc
     */
    public function get_keywords() {
        return array(
            'woocommerce',
            'coupon',
            'ecommerce'
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {
        return array();
    }

    /**
     * @inheritdoc
     */
    public function can_run() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Create a new coupon and send it to the subscriber.
     *
     * @since 1.2.8
     * @param Noptin_Subscriber $subscriber The subscriber.
     * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
     * @param array $args Extra arguments passed to the action.
     * @return void
     */
    public function run( $subscriber, $rule, $args ) {

        $coupon_code   = $rule->action_settings['coupon_code'];
        $email_content = $rule->action_settings['email_content'];
        $email_subject = $rule->action_settings['email_subject'];
        $email_preview = $rule->action_settings['email_preview'];

        // Abort if we do not have a coupon code or an email.
        if ( empty( $coupon_code ) || empty( $email_content ) ) {
            return;
        }

        if ( ! empty( $subscriber->active ) ) {
			return;
		}

		$merge_tags     = get_noptin_subscriber_merge_fields(  $subscriber->id  );

		$item  = array(
			'subscriber_id' 	=> $subscriber->id,
			'email' 			=> $subscriber->email,
			'email_body'	    => wp_kses_post( stripslashes_deep( $email_content ) ),
			'email_subject' 	=> sanitize_text_field( stripslashes_deep( $email_subject ) ),
			'preview_text'  	=> sanitize_text_field( stripslashes_deep( $email_preview ) ),
			'merge_tags'		=> $merge_tags,
		);

		$item = apply_filters( 'noptin_woocommerce_coupon_email_details', $item, $subscriber, $rule, $args );

        return noptin()->mailer->prepare_then_send( $item );

    }

}
