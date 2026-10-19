<?php

namespace Hizzle\Noptin\Integrations\EDD;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Container for EDD downloads.
 *
 * @since 2.2.0
 */
class Downloads extends \Hizzle\Noptin\Objects\Generic_Post_Type {

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->record_class      = __NAMESPACE__ . '\Download';
		$this->integration       = 'edd';
		$this->title_field       = 'name';
		$this->description_field = 'short_description';
		$this->image_field       = 'image';
		$this->url_field         = 'url';
		$this->icon              = array(
			'icon' => 'download',
			'fill' => '#1d2428',
		);
		parent::__construct( 'download' );

		$this->meta_field = $this->field_to_merge_tag( 'price', 'format="price"' );

		// Init payment triggers.
		add_action( 'edd_transition_order_item_status', array( $this, 'on_change_status' ), 10, 3 );
	}

	/**
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {
		return array_merge(
			parent::get_triggers(),
			array(
				'edd_' . $this->type . '_purchase' => array(
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Purchased', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'When a %s is purchased', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'subject'     => 'edd_customer',
					'provides'    => array( 'edd_order', 'edd_order_item' ),
				),
				'edd_' . $this->type . '_refunded' => array(
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Refunded', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'When a %s is refunded', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'subject'     => 'edd_customer',
					'provides'    => array( 'edd_order', 'edd_order_item' ),
				),
			)
		);
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {
		$product_types = edd_get_download_types();

		$product_types['default'] = $product_types[''];
		unset( $product_types[''] );

		return array(
			'id'                   => array(
				'label'      => __( 'ID', 'newsletter-optin-box' ),
				'type'       => 'number',
				'deprecated' => 'download_id',
			),
			'name'                 => array(
				'label' => __( 'Name', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s Name', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays the %s name.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'heading',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'heading',
					'linksTo'     => $this->field_to_merge_tag( 'url' ),
				),
			),
			'sku'                  => array(
				'label'      => __( 'SKU', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'download_sku',
			),
			'image'                => array(
				'label' => __( 'Image', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Featured Image', 'newsletter-optin-box' ),
					'description' => __( 'Displays the featured image.', 'newsletter-optin-box' ),
					'icon'        => 'camera',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'alt'  => $this->field_to_merge_tag( 'name' ),
						'href' => $this->field_to_merge_tag( 'url' ),
					),
					'element'     => 'image',
					'settings'    => array(
						'size' => array(
							'label'       => __( 'Resolution', 'newsletter-optin-box' ),
							'el'          => 'image_size_select',
							'description' => __( 'Select the image size to display.', 'newsletter-optin-box' ),
							'placeholder' => __( 'Select image size', 'newsletter-optin-box' ),
							'default'     => 'large',
						),
					),
				),
			),
			'url'                  => array(
				'description' => __( 'URL', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => __( 'Read More', 'newsletter-optin-box' ),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays a button link to the %s.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'welcome-view-site',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'text' => __( 'Read More', 'newsletter-optin-box' ),
						'url'  => $this->field_to_merge_tag( 'url' ),
					),
					'element'     => 'button',
				),
			),
			'price'                => array(
				'label' => __( 'Price', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'sales'                => array(
				'label' => __( 'Sales', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'earnings'             => array(
				'label' => __( 'Earnings', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'short_description'    => array(
				'description' => __( 'Excerpt', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => __( 'Excerpt', 'newsletter-optin-box' ),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays the %s short description.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'editor-alignleft',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'categories'           => $this->taxonomy_tag_config(
				__( 'Categories', 'newsletter-optin-box' ),
				sprintf(
					/* translators: %s: Object type label. */
					__( 'Displays the %1$s %2$s.', 'newsletter-optin-box' ),
					strtolower( $this->singular_label ),
					strtolower( __( 'Categories', 'newsletter-optin-box' ) )
				),
				'download_category'
			),
			'tags'                 => $this->taxonomy_tag_config(
				__( 'Tags', 'newsletter-optin-box' ),
				sprintf(
					/* translators: %s: Object type label. */
					__( 'Displays the %1$s %2$s.', 'newsletter-optin-box' ),
					strtolower( $this->singular_label ),
					strtolower( __( 'Tags', 'newsletter-optin-box' ) )
				),
				'download_tag'
			),
			'is_free'              => array(
				'label' => __( 'Is Free', 'newsletter-optin-box' ),
				'type'  => 'boolean',
			),
			'is_single_price_mode' => array(
				'label' => __( 'Single Price Mode', 'newsletter-optin-box' ),
				'type'  => 'boolean',
			),
			'has_variable_prices'  => array(
				'label' => __( 'Variable Prices', 'newsletter-optin-box' ),
				'type'  => 'boolean',
			),
			'quantities_disabled'  => array(
				'label' => __( 'Quantities Disabled', 'newsletter-optin-box' ),
				'type'  => 'boolean',
			),
			'file_download_limit'  => array(
				'label' => __( 'File Download Limit', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'refund_window'        => array(
				'label' => __( 'Refund Window', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'refundability'        => array(
				'label'   => __( 'Refundability', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => array(
					'refundable'    => __( 'Refundable', 'newsletter-optin-box' ),
					'nonrefundable' => __( 'Non Refundable', 'newsletter-optin-box' ),
				),
			),
			'type'                 => array(
				'label'   => __( 'Type', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => $product_types,
			),
		);
	}

	/**
	 * Fired after an order status changes.
	 *
	 */
	public function on_change_status( $old_status, $new_status, $item_id ) {

		if ( $new_status === $old_status ) {
			return;
		}

		if ( in_array( $new_status, array( 'publish', 'complete', 'completed' ), true ) ) {
			$action = 'edd_' . $this->type . '_purchase';
		} elseif ( 'refunded' === $new_status || 'partially_refunded' === $new_status ) {
			$action = 'edd_' . $this->type . '_refunded';
		} else {
			return;
		}

		$item = edd_get_order_item( $item_id );

		if ( ! $item ) {
			return;
		}

		$order = edd_get_order( $item->order_id );

		if ( empty( $order ) ) {
			return;
		}

		$customer = edd_get_customer( $order->customer_id );

		if ( empty( $customer ) ) {
			return;
		}

		$this->trigger(
			$action,
			array(
				'email'       => $customer->email,
				'object_id'   => $item->product_id,
				'subject_id'  => $customer->id,
				'unserialize' => array(
					'edd_order.status' => $new_status,
				),
				'provides'    => array(
					'edd_order'      => $item->order_id,
					'edd_order_item' => $item->id,
				),
			)
		);
	}

	/**
	 * Retrieves a test object args.
	 *
	 * @since 2.2.0
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule
	 * @throws \Exception
	 * @return array
	 */
	public function get_test_args( $rule ) {

		if ( 'edd_' . $this->type . '_purchase' === $rule->get_trigger_id() || 'edd_' . $this->type . '_refunded' === $rule->get_trigger_id() ) {

			$args = array();

			if ( 'edd_' . $this->type . '_refunded' === $rule->get_trigger_id() ) {
				$args = array( 'status__in' => array( 'refunded', 'partially_refunded' ) );
			}

			// Fetch latest order.
			$order = Orders::get_test_order( $args );
			$items = $order->get_items();

			if ( empty( $items ) ) {
				throw new \Exception( 'No order item found.' );
			}

			$item = current( $items );
			return array(
				'email'      => $order->email,
				'object_id'  => $item->product_id,
				'subject_id' => $order->customer_id,
				'provides'   => array(
					'edd_order'      => $item->order_id,
					'edd_order_item' => $item->id,
				),
			);
		}

		return parent::get_test_args( $rule );
	}
}
