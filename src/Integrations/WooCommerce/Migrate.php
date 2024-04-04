<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Migrates WooCommerce emails to automation rules.
 *
 * @since 3.0.0
 */
class Migrate {

	/**
	 * Class constructor.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		add_action( 'noptin_register_post_type_objects', array( $this, 'maybe_migrate' ) );
	}

	/**
	 * Migrates WooCommerce emails to automation rules.
	 */
	public function maybe_migrate() {
		global $wpdb;

		$to_migrate = array( 'woocommerce_product_purchase', 'woocommerce_new_order', 'woocommerce_lifetime_value' );
		$hash       = md5( wp_json_encode( $to_migrate ) );

		// Check if we have already migrated the emails.
		if ( get_option( 'noptin_woocommerce_emails_migrated' ) === $hash ) {
			return;
		}

		update_option( 'noptin_woocommerce_emails_migrated', $hash );

		// Fetch all post ids from post_meta where meta_key is automation_type and meta value in $to_migrate.
		$emails = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'automation_type' AND meta_value IN ( " . implode( ',', array_fill( 0, count( $to_migrate ), '%s' ) ) . ' )',
				$to_migrate
			)
		);

		foreach ( $emails as $email_id ) {
			$email = noptin_get_email_campaign_object( $email_id );

			if ( ! $email->exists() || 'automation' !== $email->type || ! in_array( $email->get_sub_type(), $to_migrate, true ) ) {
				continue;
			}

			call_user_func( array( $this, $email->get_sub_type() ), $email );
		}
	}

	/**
	 * Migrate product purchase trigger.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $email
	 * @since 3.0.0
	 */
	public function woocommerce_product_purchase( $email ) {
		$product_action = $email->get( 'product_action' );
		$trigger_id     = 'refund' === $product_action ? 'woocommerce_product_refunded' : 'woocommerce_product_purchased';

		$email->options['automation_type'] = 'automation_rule_' . $trigger_id;
		update_post_meta( $email->id, 'automation_type', $email->options['automation_type'] );

		$settings = array( 'conditional_logic' => noptin_get_default_conditional_logic() );

		// Product.
		$product = $email->get( 'product' );
		if ( $product ) {
			$product = wc_get_product( $product );

			if ( $product ) {
				$settings['conditional_logic']['rules'][] = array(
					'type'      => $product->get_type() === 'variation' ? 'order_item.variation_id' : 'order_item.product_id',
					'condition' => 'is',
					'value'     => $product->get_id(),
				);
			}
		}

		// New customer.
		$new_customer = $email->get( 'new_customer' );
		if ( $new_customer ) {
			$settings['conditional_logic']['rules'][] = array(
				'type'      => 'customer.order_count',
				'condition' => 'is',
				'value'     => '1',
			);
		}

		$settings['conditional_logic']['enabled'] = ! empty( $settings['conditional_logic']['rules'] );

		// Sync.
		\Noptin_Automation_Rule_Email::sync_campaign_to_rule( $email, $settings );
	}

	/**
	 * Migrate new order trigger.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $email
	 * @since 3.0.0
	 */
	public function woocommerce_new_order( $email ) {
		$map    = array(
			'created'    => 'wc_new_order',
			'pending'    => 'wc_pending',
			'processing' => 'wc_processing',
			'held'       => 'wc_on-hold',
			'paid'       => 'wc_payment_complete',
			'completed'  => 'wc_completed',
			'refunded'   => 'wc_order_refunded',
			'cancelled'  => 'wc_cancelled',
			'failed'     => 'wc_failed',
			'deleted'    => 'wc_before_delete_order',
		);
		$status = $email->get( 'order_status' );
		$hook   = empty( $status ) || ! isset( $map[ $status ] ) ? 'wc_payment_complete' : $map[ $status ];

		$email->options['automation_type'] = 'automation_rule_' . $hook;
		update_post_meta( $email->id, 'automation_type', $email->options['automation_type'] );

		$settings = array( 'conditional_logic' => noptin_get_default_conditional_logic() );

		// New customer.
		$new_customer = $email->get( 'new_customer' );
		if ( $new_customer ) {
			$settings['conditional_logic']['rules'][] = array(
				'type'      => 'customer.order_count',
				'condition' => 'is',
				'value'     => '1',
			);
		}

		$settings['conditional_logic']['enabled'] = ! empty( $settings['conditional_logic']['rules'] );

		// Sync.
		\Noptin_Automation_Rule_Email::sync_campaign_to_rule( $email, $settings );
	}

	/**
	 * Migrate new order trigger.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $email
	 * @since 3.0.0
	 */
	public function woocommerce_lifetime_value( $email ) {

		$email->options['automation_type'] = 'automation_rule_woocommerce_lifetime_value';
		update_post_meta( $email->id, 'automation_type', 'automation_rule_woocommerce_lifetime_value' );

		$settings = array( 'conditional_logic' => noptin_get_default_conditional_logic() );

		// Lifetime value.
		$settings['lifetime_value'] = $email->get( 'lifetime_value' );

		// Sync.
		\Noptin_Automation_Rule_Email::sync_campaign_to_rule( $email, $settings );
	}
}
