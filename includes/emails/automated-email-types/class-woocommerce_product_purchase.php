<?php
/**
 * Emails API: WooCommerce Product Purchase.
 *
 * Send an email to a customer when they make a new order.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Send an email to a customer when they make a new order.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_WooCommerce_Product_Purchase_Email extends Noptin_WooCommerce_Automated_Email_Type {

	/**
	 * @var string
	 */
	public $type = 'woocommerce_product_purchase';

	/**
	 * @var string
	 */
	public $notification_hook = 'noptin_woocommerce_product_purchase_notify';

	/**
	 * Registers hooks.
	 *
	 */
	public function add_hooks() {
		parent::add_hooks();

		// Notify customers.
		add_action( 'noptin_woocommerce_product_refund', array( $this, 'maybe_schedule_refund_notification' ), 100, 4 );
        add_action( 'noptin_woocommerce_product_buy', array( $this, 'maybe_schedule_buy_notification' ), 100, 4 );
	}

	/**
	 * Retrieves the automated email type name.
	 *
	 */
	public function get_name() {
		return __( 'Product Purchase', 'newsletter-optin-box' );
	}

	/**
	 * Retrieves the automated email type description.
	 *
	 */
	public function get_description() {
		return __( 'Send an email to your customers when they purchase a specific product. Optionally limit the email to first-time customers.', 'newsletter-optin-box' );
	}

	/**
	 * Returns the default action.
	 *
	 */
	public function default_action() {
		return 'buy';
	}

	/**
	 * Returns the default subject.
	 *
	 */
	public function default_subject() {
		return __( '[[customer.first_name]], how would you rate the products!', 'newsletter-optin-box' );
	}

	/**
	 * Returns the default heading.
	 *
	 */
	public function default_heading() {
		return $this->default_subject();
	}

	/**
	 * Returns the default content.
	 *
	 */
	public function default_content_normal() {
		ob_start();
		?>
		<p><?php esc_html_e( 'Hi [[customer.first_name]],', 'newsletter-optin-box' ); ?></p>
		<p><?php esc_html_e( 'Thanks for purchasing [[product.name]]. Please reply to this email and let us know what you think of the product.', 'newsletter-optin-box' ); ?></p>
		<p><?php esc_html_e( 'Cheers!', 'newsletter-optin-box' ); ?></p>
		<p>[[blog_name]]</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the default plain text content.
	 *
	 */
	public function default_content_plain_text() {
		return noptin_convert_html_to_text( $this->default_content_normal() );
	}

	/**
	 * Returns the default recipient.
	 *
	 */
	public function get_default_recipient() {
		return '[[customer.email]]';
	}

	public function default_new_customer() {
		return false;
	}

	public function default_product_action() {
		return 'buy';
	}

	/**
	 * Displays a metabox.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 */
	public function campaign_options( $options ) {

		// Fetch all products.
		$integrations = noptin()->integrations->integrations;

		if ( empty( $integrations['woocommerce'] ) ) {
			return;
		}

		$products = $integrations['woocommerce']->get_products();
		$prepared = array();

		foreach ( $products as $product ) {

			if ( empty( $product['variations'] ) ) {
				$prepared[ $product['id'] ] = $product['name'];
			} else {
				foreach ( $product['variations'] as $variation ) {
					$prepared[ $variation['id'] ] = $product['name'] . ' -- ' . $variation['name'];
				}
			}
		}

		return array_merge(
			$options,
			array(
				'product_action' => array(
					'el'          => 'select',
					'label'       => __( 'Send this email when a product...', 'newsletter-optin-box' ),
					'description' => __( 'This email is automatically sent whenever a customer\'s lifetime value surpases the specified amount.', 'newsletter-optin-box' ),
					'options'     => array(
						'buy'    => __( 'is bought', 'newsletter-optin-box' ),
						'refund' => __( 'is refunded', 'newsletter-optin-box' ),
					),
				),
				'product'        => array(
					'el'      => 'combobox',
					'options' => $prepared,
				),
				'new_customer'   => array(
					'el'    => 'input',
					'type'  => 'checkbox',
					'label' => __( 'Only send the first time someone buys this product?', 'newsletter-optin-box' ),
				),
			)
		);
	}

	/**
	 * Retrieves an array of supported merge tags.
	 *
	 * @return array
	 */
	public function get_merge_tags() {

		return array(
			__( 'Order', 'newsletter-optin-box' )      => $this->get_order_merge_tags(),
			__( 'Customer', 'newsletter-optin-box' )   => $this->get_customer_merge_tags(),
			__( 'Product', 'newsletter-optin-box' )    => $this->get_product_merge_tags(),
			__( 'Order Item', 'newsletter-optin-box' ) => $this->get_order_item_merge_tags(),
		);

	}

	/**
	 * Notify customers when a product is bought.
	 *
     * @param int $product_id The product being bought.
     * @param array $item The order item being bought.
     * @param int $order_id The order being acted on.
     * @param Noptin_WooCommerce $woocommerce The Noptin and WC integration bridge.
	 */
	public function maybe_schedule_buy_notification( $product_id, $item, $order_id, $woocommerce ) {

		$order = wc_get_order( $order_id );

		// Ensure the order exists.
		if ( empty( $order ) ) {
			return;
		}

		// Are there any automations.
		$automations = $this->get_automations();
		if ( empty( $automations ) ) {
			return;
		}

		foreach ( $automations as $automation ) {

			// Check if the automation applies here.
			if ( $automation->can_send() && $this->is_automation_valid_for( $automation, $order, $product_id, 'buy', $woocommerce ) ) {
				$this->schedule_notification( $item['item_id'], $automation );
			}
		}

	}

	/**
	 * Notify customers when a product is refunded.
	 *
     * @param int $product_id The product being refunded.
     * @param array $item The order item being refunded.
     * @param int $order_id The order being acted on.
     * @param Noptin_WooCommerce $woocommerce The Noptin and WC integration bridge.
	 */
	public function maybe_schedule_refund_notification( $product_id, $item, $order_id, $woocommerce ) {

		$order = wc_get_order( $order_id );

		// Ensure the order exists.
		if ( empty( $order ) ) {
			return;
		}

		// Are there any automations.
		$automations = $this->get_automations();
		if ( empty( $automations ) ) {
			return;
		}

		foreach ( $automations as $automation ) {

			// Check if the automation applies here.
			if ( $automation->can_send() && $this->is_automation_valid_for( $automation, $order, $product_id, 'refund', $woocommerce ) ) {
				$this->schedule_notification( $item['item_id'], $automation );
			}
		}
	}

	/**
	 * Checks if a given notification is valid for a given order
	 *
	 * @param \Hizzle\Noptin\Emails\Email $automation
	 * @param WC_Order $order
	 * @param int $product_id
	 * @param string $action
	 * @param Noptin_WooCommerce $woocommerce
	 */
	public function is_automation_valid_for( $automation, $order, $product_id, $action, $woocommerce ) {

		// Abort if no product selected ...
		if ( (int) $automation->get( 'product' ) !== $product_id ) {
			return false;
		}

		// ... or actions do not match.
		if ( $automation->get( 'product_action' ) !== $action ) {
			return false;
		}

		// Are we firering for new buyers only?
		$new_customer = $automation->get( 'new_customer' );
        if ( ! empty( $new_customer ) ) {

            // Fetch the user associated with the order.
            $user = $woocommerce->get_order_customer_user_id( $order->get_id() );
            if ( empty( $user ) ) {
                $user = $woocommerce->get_order_customer_email( $order->get_id() );
            }

            return $woocommerce->get_product_purchase_count( $user, $product_id ) === 1;

        }

		return true;

	}

	/**
	 * (Maybe) Send out a new order notification
	 *
	 * @param int $item_id
	 * @param int $campaign_id
	 * @param string $key
	 */
	public function maybe_send_notification( $item_id, $campaign_id ) {

		$order    = wc_get_order( wc_get_order_id_by_order_item_id( $item_id ) );
		$campaign = noptin_get_email_campaign_object( $campaign_id );
		$key      = $item_id . '_' . $campaign_id;

		// Ensure the order exists and the campaign is active.
		if ( empty( $order ) || ! $campaign->can_send() ) {
			return;
		}

		// Abort if the item nolonger exists on the order.
		/**@var WC_Order_Item_Product $item */
		$item = $order->get_item( $item_id );
		if ( empty( $item ) ) {
			return;
		}

		// ... Abort if the product was deleted.
		$product = $item->get_product();

		if ( empty( $product ) ) {
			return;
		}

		// Send the email.
		$this->order      = $order;
		$this->order_item = $item;
		$this->product    = $product;
		$this->sending    = true;

		// Set current customer.
		$customer_id = $order->get_customer_id();

		if ( $customer_id > 0 ) {
			$this->customer = new WC_Customer( $customer_id );
		}

		$this->prepare_and_send( $campaign, $key );
	}

	/**
	 * Prepares test data.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $email
	 */
	public function prepare_test_data( $email ) {
		parent::prepare_test_data( $email );

		// Maybe use selected product.
		$product = wc_get_product( (int) $email->get( 'product' ) );
		if ( $product ) {
			$this->product = $product;
		}
	}
}
