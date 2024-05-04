<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for WooCommerce order items.
 *
 * @since 3.0.0
 */
class Order_Items extends \Hizzle\Noptin\Objects\Collection {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->can_list       = true;
		$this->integration    = 'woocommerce';
		$this->type           = 'order_item';
		$this->provides       = array( 'product' );
		$this->label          = __( 'Order Items', 'newsletter-optin-box' );
		$this->singular_label = __( 'Order Item', 'newsletter-optin-box' );
		$this->record_class   = __NAMESPACE__ . '\Order_Item';
		$this->is_stand_alone = false;
		$this->icon           = array(
			'icon' => 'cart',
			'fill' => '#674399',
		);

		parent::__construct();
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		return array(
			'id'                => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'product_id'        => array(
				'label' => __( 'Product ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'variation_id'      => array(
				'label' => __( 'Variation ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'name'              => array(
				'label' => __( 'Name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'quantity'          => array(
				'label' => __( 'Quantity', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'subtotal'          => array(
				'label' => __( 'Subtotal', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'total'             => array(
				'label' => __( 'Total', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'total_tax'         => array(
				'label' => __( 'Total tax', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'attribute'         => array(
				'label'          => __( 'Attribute', 'newsletter-optin-box' ),
				'type'           => 'string',
				'example'        => 'key="my_key"',
				'skip_smart_tag' => true,
				'block'          => array(
					'title'       => sprintf(
						/* translators: %s: object type label */
						__( '%s Attribute', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => __( 'Displays an attribute value.', 'newsletter-optin-box' ),
					'icon'        => 'nametag',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'div',
					'settings'    => array(
						'key'     => array(
							'label'       => __( 'Attribute Key', 'newsletter-optin-box' ),
							'el'          => 'input',
							'type'        => 'text',
							'description' => __( 'The attribute key to display.', 'newsletter-optin-box' ),
						),
						'default' => array(
							'label'       => __( 'Default Value', 'newsletter-optin-box' ),
							'el'          => 'input',
							'type'        => 'text',
							'description' => __( 'The default value to display if not set.', 'newsletter-optin-box' ),
						),
					),
				),
			),
			'meta'              => $this->meta_key_tag_config(),
		);
	}

	/**
	 * Returns the template for the list shortcode.
	 */
	protected function get_list_shortcode_template() {
		$template = array(
			'meta'    => $this->field_to_merge_tag( 'total', 'format="price"' ),
			'heading' => $this->field_to_merge_tag( 'name' ),
		);
		$products = \Hizzle\Noptin\Objects\Store::get( 'product' );

		if ( ! empty( $products ) ) {
			$template['button']      = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $products->field_to_merge_tag( 'url' ) );
			$template['image']       = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $products->field_to_merge_tag( $products->image_field ) );
			$template['description'] = $products->field_to_merge_tag( $products->description_field );
		}

		return $template;
	}
}
