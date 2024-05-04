<?php

namespace Hizzle\Noptin\Integrations\EDD;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for EDD order items.
 *
 * @since 3.0.0
 */
class Order_Items extends \Hizzle\Noptin\Objects\Collection {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->can_list       = true;
		$this->integration    = 'edd';
		$this->type           = 'edd_order_item';
		$this->provides       = array( 'download' );
		$this->label          = __( 'Order Items', 'newsletter-optin-box' );
		$this->singular_label = __( 'Order Item', 'newsletter-optin-box' );
		$this->record_class   = __NAMESPACE__ . '\Order_Item';
		$this->is_stand_alone = false;
		$this->icon           = array(
			'icon' => 'cart',
			'fill' => '#1d2428',
		);

		parent::__construct();
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		return array(
			'id'           => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'order_id'     => array(
				'label' => __( 'Order ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'price_id'     => array(
				'label' => __( 'Price ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'product_id'   => array(
				'label' => __( 'Product ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'product_name' => array(
				'label' => __( 'Name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'quantity'     => array(
				'label' => __( 'Quantity', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'amount'       => array(
				'label' => __( 'Amount', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'subtotal'     => array(
				'label' => __( 'Subtotal', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'tax'          => array(
				'label' => __( 'Tax', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'discount'     => array(
				'label' => __( 'Discount', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'total'        => array(
				'label' => __( 'Total', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'meta'         => $this->meta_key_tag_config(),
		);
	}

	/**
	 * Returns the template for the list shortcode.
	 */
	protected function get_list_shortcode_template() {
		$template = array(
			'meta'    => $this->field_to_merge_tag( 'total', 'format="price"' ),
			'heading' => $this->field_to_merge_tag( 'product_name' ),
		);

		$downloads = \Hizzle\Noptin\Objects\Store::get( 'download' );

		if ( ! empty( $downloads ) ) {
			$template['button']      = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $downloads->field_to_merge_tag( 'url' ) );
			$template['image']       = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $downloads->field_to_merge_tag( $downloads->image_field ) );
			$template['description'] = $downloads->field_to_merge_tag( $downloads->description_field );
		}

		return $template;
	}
}
