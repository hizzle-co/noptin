<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Container for a product.
 */
class Products extends \Hizzle\Noptin\Objects\Generic_Post_Type {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function __construct() {
		$this->record_class      = __NAMESPACE__ . '\Product';
		$this->integration       = 'woocommerce';
		$this->title_field       = 'name';
		$this->description_field = 'short_description';
		$this->image_field       = 'image';
		$this->url_field         = 'add_to_cart_url';
		$this->meta_field        = 'price_html';
		$this->icon              = array(
			'icon' => 'products',
			'fill' => '#674399',
		);
		parent::__construct( 'product' );

		// Refund.
		add_action( 'woocommerce_order_refunded', array( $this, 'on_refund' ) );

		// Purchase.
		add_action( 'woocommerce_payment_complete', array( $this, 'on_purchase' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_purchase' ) );
	}

	/**
	 * Retrieves available filters.
	 *
	 * @return array
	 */
	public function get_filters() {

		$types      = wc_get_product_types();
		$tags       = get_terms(
			array(
				'hide_empty'      => false,
				'taxonomy'        => 'product_tag',
				'suppress_filter' => true,
			)
		);
		$categories = get_terms(
			array(
				'hide_empty'      => false,
				'taxonomy'        => 'product_cat',
				'suppress_filter' => true,
			)
		);
		return array(
			'type'              => array(
				'label'       => __( 'Product type', 'newsletter-optin-box' ),
				'el'          => 'select',
				'multiple'    => true,
				'options'     => $types,
				'description' => __( 'Filter by product type.', 'newsletter-optin-box' ),
				'placeholder' => __( 'Filter by product type.', 'newsletter-optin-box' ),
			),
			'include'           => array(
				'label'       => __( 'Product IDs', 'newsletter-optin-box' ),
				'el'          => 'form_token',
				'placeholder' => __( 'Comma-separated list of product IDs to include.', 'newsletter-optin-box' ),
			),
			'exclude'           => array(
				'label'       => __( 'Exclude Product IDs', 'newsletter-optin-box' ),
				'el'          => 'form_token',
				'placeholder' => __( 'Comma-separated list of product IDs to exclude.', 'newsletter-optin-box' ),
			),
			'sku'               => array(
				'label'       => __( 'SKU', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'text',
				'description' => __( 'Does partial matching on the SKU.', 'newsletter-optin-box' ),
				'placeholder' => sprintf(
					// translators: %s: The Example
					__( 'For example, %s', 'newsletter-optin-box' ),
					'PROD-'
				),
			),
			'name'              => array(
				'label'       => __( 'Name', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'text',
				'description' => __( 'The product name (sometimes referred to as the product title) to match on.', 'newsletter-optin-box' ),
			),
			'tag'               => array(
				'label'    => __( 'Tag', 'newsletter-optin-box' ),
				'el'       => 'select',
				'multiple' => true,
				'options'  => wp_list_pluck( $tags, 'name', 'slug' ),
			),
			'category'          => array(
				'label'    => __( 'Category', 'newsletter-optin-box' ),
				'el'       => 'select',
				'multiple' => true,
				'options'  => wp_list_pluck( $categories, 'name', 'slug' ),
			),
			'weight'            => array(
				'label'            => __( 'Weight', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'step' => 'any',
				),
			),
			'length'            => array(
				'label'            => __( 'Length', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'step' => 'any',
				),
			),
			'width'             => array(
				'label'            => __( 'Width', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'step' => 'any',
				),
			),
			'height'            => array(
				'label'            => __( 'Height', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'step' => 'any',
				),
			),
			'price'             => array(
				'label'            => __( 'Price', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'prefix' => get_woocommerce_currency(),
					'step'   => 'any',
				),
			),
			'regular_price'     => array(
				'label'            => __( 'Regular price', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'prefix' => get_woocommerce_currency(),
					'step'   => 'any',
				),
			),
			'sale_price'        => array(
				'label'            => __( 'Sale price', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'prefix' => get_woocommerce_currency(),
					'step'   => 'any',
				),
			),
			'total_sales'       => array(
				'label'            => __( 'Total sales', 'newsletter-optin-box' ),
				'description'      => __( 'Gets products with that many sales.', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'step' => 1,
				),
			),
			'virtual'           => array(
				'label'       => __( 'Virtual', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'checkbox',
				'description' => __( 'Whether or not the product is virtual.', 'newsletter-optin-box' ),
			),
			'downloadable'      => array(
				'label'       => __( 'Downloadable', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'checkbox',
				'description' => __( 'Whether or not the product is downloadable.', 'newsletter-optin-box' ),
			),
			'featured'          => array(
				'label'       => __( 'Featured', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'checkbox',
				'description' => __( 'Whether or not the product is featured.', 'newsletter-optin-box' ),
			),
			'sold_individually' => array(
				'label'       => __( 'Sold individually', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'checkbox',
				'description' => __( 'Whether or not the product is sold individually.', 'newsletter-optin-box' ),
			),
			'manage_stock'      => array(
				'label'       => __( 'Manage stock', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'checkbox',
				'description' => __( 'Whether or not the product is stock managed.', 'newsletter-optin-box' ),
			),
			'reviews_allowed'   => array(
				'label' => __( 'Reviews allowed', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'checkbox',
			),
			'backorders'        => array(
				'label'       => __( 'Backorders', 'newsletter-optin-box' ),
				'el'          => 'select',
				'options'     => array(
					'no'     => __( 'No', 'newsletter-optin-box' ),
					'notify' => __( 'Notify', 'newsletter-optin-box' ),
					'yes'    => __( 'Yes', 'newsletter-optin-box' ),
				),
				'description' => __( 'Whether or not the product allows backorders.', 'newsletter-optin-box' ),
			),
			'visibility'        => array(
				'label'   => __( 'Visibility', 'newsletter-optin-box' ),
				'el'      => 'select',
				'options' => array(
					'visible' => __( 'Visible', 'newsletter-optin-box' ),
					'catalog' => __( 'Catalog', 'newsletter-optin-box' ),
					'search'  => __( 'Search', 'newsletter-optin-box' ),
					'hidden'  => __( 'Hidden', 'newsletter-optin-box' ),
				),
			),
			'stock_quantity'    => array(
				'label'            => __( 'Stock quantity', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'step' => 1,
				),
			),
			'stock_status'      => array(
				'label'   => __( 'Stock status', 'newsletter-optin-box' ),
				'el'      => 'select',
				'options' => array(
					'instock'    => __( 'In stock', 'newsletter-optin-box' ),
					'outofstock' => __( 'Out of Stock', 'newsletter-optin-box' ),
				),
			),
			'tax_status'        => array(
				'label'   => __( 'Tax status', 'newsletter-optin-box' ),
				'el'      => 'select',
				'options' => array(
					'taxable'  => __( 'Taxable', 'newsletter-optin-box' ),
					'shipping' => __( 'Shipping', 'newsletter-optin-box' ),
					'none'     => __( 'None', 'newsletter-optin-box' ),
				),
			),
			'tax_class'         => array(
				'label' => __( 'Tax class', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'shipping_class'    => array(
				'label' => __( 'Shipping class', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'download_limit'    => array(
				'label'            => __( 'Download limit', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'step' => 1,
				),
			),
			'download_expiry'   => array(
				'label'            => __( 'Download expiry', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'step' => 1,
				),
			),
			'average_rating'    => array(
				'label'            => __( 'Average rating', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'step' => 'any',
				),
			),
			'review_count'      => array(
				'label'            => __( 'Review count', 'newsletter-optin-box' ),
				'description'      => __( 'Gets products with that many reviews.', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'customAttributes' => array(
					'step' => 1,
				),
			),
			'date_created'      => array(
				'label'       => __( 'Date created', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'string',
				'placeholder' => sprintf(
					'%s <br /> %s',
					__( 'Date product was created, in the site\'s timezone, with an optional comparison operator.', 'newsletter-optin-box' ),
					sprintf(
						'For example, %1$s, >%1$s, or %1$s...%2$s',
						gmdate( 'Y-m-d' ),
						gmdate( 'Y-m-d', strtotime( '+1 week' ) )
					)
				),
			),
			'date_on_sale_from' => array(
				'label'       => __( 'Date on sale from', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'string',
				'placeholder' => sprintf(
					'%s <br /> %s',
					__( 'Start date of sale price, in the site\'s timezone, with an optional comparison operator.', 'newsletter-optin-box' ),
					sprintf(
						'For example, %1$s, >%1$s, or %1$s...%2$s',
						gmdate( 'Y-m-d' ),
						gmdate( 'Y-m-d', strtotime( '+1 week' ) )
					)
				),
			),
			'date_on_sale_to'   => array(
				'label'       => __( 'Date on sale to', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'string',
				'placeholder' => sprintf(
					'%s <br /> %s',
					__( 'End date of sale price, in the site\'s timezone, with an optional comparison operator.', 'newsletter-optin-box' ),
					sprintf(
						'For example, %1$s, >%1$s, or %1$s...%2$s',
						gmdate( 'Y-m-d' ),
						gmdate( 'Y-m-d', strtotime( '+1 week' ) )
					)
				),
			),
		);
	}

	/**
	 * Retrieves matching posts.
	 *
	 * @param array $filters The available filters.
	 * @return int[] $users The user IDs.
	 */
	public function get_all( $filters ) {

		$filters = array_merge(
			array(
				'status'  => 'publish',
				'number'  => 10,
				'order'   => 'DESC',
				'orderby' => 'date',
				'return'  => 'ids',
			),
			$filters
		);

		// If order by is title, use name instead.
		if ( 'title' === $filters['orderby'] ) {
			$filters['orderby'] = 'name';
		}

		// Convert number to numberposts.
		if ( isset( $filters['number'] ) ) {
			$filters['limit'] = $filters['number'];
			unset( $filters['number'] );
		}

		// Ensure include and exclude are arrays.
		foreach ( array( 'include', 'exclude' ) as $key ) {
			if ( isset( $filters[ $key ] ) && ! is_array( $filters[ $key ] ) ) {
				$filters[ $key ] = wp_parse_id_list( $filters[ $key ] );
			}
		}

		// Parse dates.
		foreach ( array( 'date_created', 'date_on_sale_from', 'date_on_sale_to' ) as $key ) {
			if ( isset( $filters[ $key ] ) ) {
				$filters[ $key ] = Orders::parse_wc_date_query( $filters[ $key ] );
			}
		}

		return wc_get_products( array_filter( $filters ) );
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		$action = 'create_or_update_' . $this->type;
		$fields = array(
			'name'                    => array(
				'label'        => __( 'Name', 'newsletter-optin-box' ),
				'type'         => 'string',
				'block'        => array(
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
				'actions'      => array( $action ),
				'show_in_meta' => true,
			),
			'slug'                    => array(
				'label' => __( 'Slug', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'image'                   => array(
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
							'default'     => 'woocommerce_thumbnail',
						),
					),
				),
			),
			'price'                   => array(
				'label'        => __( 'Price', 'newsletter-optin-box' ),
				'type'         => 'number',
				'show_in_meta' => true,
			),
			'price_html'              => array(
				'label' => __( 'Price HTML', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'url'                     => array(
				'label' => __( 'URL', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
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
			'add_to_cart_url'         => array(
				'label' => __( 'Add to cart URL', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Add to cart', 'newsletter-optin-box' ),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays a button link to add the %s to the cart.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'cart',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'text' => $this->field_to_merge_tag( 'single_add_to_cart_text' ),
						'url'  => $this->field_to_merge_tag( 'add_to_cart_url' ),
					),
					'element'     => 'button',
				),
			),
			'single_add_to_cart_text' => array(
				'label' => __( 'Add to cart text', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'short_description'       => array(
				'label'   => __( 'Short description', 'newsletter-optin-box' ),
				'type'    => 'string',
				'actions' => array( $action ),
				'block'   => array(
					'title'       => __( 'Product description', 'newsletter-optin-box' ),
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
			'categories'              => $this->taxonomy_tag_config(
				__( 'Categories', 'newsletter-optin-box' ),
				sprintf(
					/* translators: %s: Object type label. */
					__( 'Displays the %1$s %2$s.', 'newsletter-optin-box' ),
					strtolower( $this->singular_label ),
					strtolower( __( 'Categories', 'newsletter-optin-box' ) )
				),
				'category'
			),
			'tags'                    => $this->taxonomy_tag_config(
				__( 'Tags', 'newsletter-optin-box' ),
				sprintf(
					/* translators: %s: Object type label. */
					__( 'Displays the %1$s %2$s.', 'newsletter-optin-box' ),
					strtolower( $this->singular_label ),
					strtolower( __( 'Tags', 'newsletter-optin-box' ) )
				),
				'tag'
			),
			'id'                      => array(
				'label'        => __( 'ID', 'newsletter-optin-box' ),
				'type'         => 'number',
				'actions'      => array( $action ),
				'action_props' => array(
					$action                 => array(
						'label'        => __( 'Product ID', 'newsletter-optin-box' ),
						'description'  => __( 'Leave blank to create a new product.', 'newsletter-optin-box' ),
						'show_in_meta' => true,
					),
					'delete_' . $this->type => array(
						'label'       => __( 'Post ID or SKU', 'newsletter-optin-box' ),
						'description' => __( 'Specify a post ID or sku', 'newsletter-optin-box' ),
						'required'    => true,
					),
				),
			),
			'parent_id'               => array(
				'label'        => __( 'Parent ID', 'newsletter-optin-box' ),
				'type'         => 'number',
				'actions'      => array( $action ),
				'show_in_meta' => true,
			),
			'date_created'            => array(
				'label' => __( 'Date created', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'date_modified'           => array(
				'label' => __( 'Date modified', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'status'                  => array(
				'label'   => __( 'Status', 'newsletter-optin-box' ),
				'type'    => 'string',
				'actions' => array( $action ),
				'options' => get_post_statuses(),
			),
			'featured'                => array(
				'label'   => __( 'Featured', 'newsletter-optin-box' ),
				'type'    => 'boolean',
				'actions' => array( $action ),
			),
			'catalog_visibility'      => array(
				'label'   => __( 'Catalog visibility', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => array(
					'visible' => __( 'Visible', 'newsletter-optin-box' ),
					'catalog' => __( 'Catalog', 'newsletter-optin-box' ),
					'search'  => __( 'Search', 'newsletter-optin-box' ),
					'hidden'  => __( 'Hidden', 'newsletter-optin-box' ),
				),
				'actions' => array( $action ),
			),
			'description'             => array(
				'label'   => __( 'Description', 'newsletter-optin-box' ),
				'type'    => 'string',
				'actions' => array( $action ),
			),
			'sku'                     => array(
				'label'        => __( 'SKU', 'newsletter-optin-box' ),
				'type'         => 'string',
				'actions'      => array( $action ),
				'action_props' => array(
					$action => array(
						'description' => __( 'If provided and a product with the same sku exists, the product will be updated.', 'newsletter-optin-box' ),
					),
				),
			),
			'regular_price'           => array(
				'label'   => __( 'Regular price', 'newsletter-optin-box' ),
				'type'    => 'number',
				'actions' => array( $action ),
			),
			'sale_price'              => array(
				'label'   => __( 'Sale price', 'newsletter-optin-box' ),
				'type'    => 'number',
				'actions' => array( $action ),
			),
			'date_on_sale_from'       => array(
				'label'   => __( 'Date on sale from', 'newsletter-optin-box' ),
				'type'    => 'string',
				'actions' => array( $action ),
			),
			'date_on_sale_to'         => array(
				'label'   => __( 'Date on sale to', 'newsletter-optin-box' ),
				'type'    => 'string',
				'actions' => array( $action ),
			),
			'total_sales'             => array(
				'label' => __( 'Total sales', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'type'                    => array(
				'label'    => __( 'Type', 'newsletter-optin-box' ),
				'type'     => 'string',
				'options'  => wc_get_product_types(),
				'actions'  => array( $action ),
				'required' => true,
			),
			'tax_status'              => array(
				'label'   => __( 'Tax status', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => array(
					'taxable'  => __( 'Taxable', 'newsletter-optin-box' ),
					'shipping' => __( 'Shipping', 'newsletter-optin-box' ),
					'none'     => __( 'None', 'newsletter-optin-box' ),
				),
				'actions' => array( $action ),
			),
			'tax_class'               => array(
				'label'   => __( 'Tax class', 'newsletter-optin-box' ),
				'type'    => 'string',
				'actions' => array( $action ),
			),
			'manage_stock'            => array(
				'label'   => __( 'Manage stock', 'newsletter-optin-box' ),
				'type'    => 'boolean',
				'actions' => array( $action ),
			),
			'stock_quantity'          => array(
				'label'   => __( 'Stock quantity', 'newsletter-optin-box' ),
				'type'    => 'number',
				'actions' => array( $action ),
			),
			'stock_status'            => array(
				'label'   => __( 'Stock status', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => array(
					'instock'    => __( 'In stock', 'newsletter-optin-box' ),
					'outofstock' => __( 'Out of Stock', 'newsletter-optin-box' ),
				),
			),
			'backorders'              => array(
				'label'   => __( 'Backorders', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => array(
					'no'     => __( 'No', 'newsletter-optin-box' ),
					'notify' => __( 'Notify', 'newsletter-optin-box' ),
					'yes'    => __( 'Yes', 'newsletter-optin-box' ),
				),
				'actions' => array( $action ),
			),
			'low_stock_amount'        => array(
				'label'   => __( 'Low stock amount', 'newsletter-optin-box' ),
				'type'    => 'number',
				'actions' => array( $action ),
			),
			'sold_individually'       => array(
				'label'   => __( 'Sold individually', 'newsletter-optin-box' ),
				'type'    => 'boolean',
				'actions' => array( $action ),
			),
			'dimensions'              => array(
				'label' => __( 'Dimensions', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'weight'                  => array(
				'label'   => __( 'Weight', 'newsletter-optin-box' ),
				'type'    => 'number',
				'actions' => array( $action ),
			),
			'length'                  => array(
				'label'   => __( 'Length', 'newsletter-optin-box' ),
				'type'    => 'number',
				'actions' => array( $action ),
			),
			'width'                   => array(
				'label'   => __( 'Width', 'newsletter-optin-box' ),
				'type'    => 'number',
				'actions' => array( $action ),
			),
			'height'                  => array(
				'label'   => __( 'Height', 'newsletter-optin-box' ),
				'type'    => 'number',
				'actions' => array( $action ),
			),
			'upsell_ids'              => array(
				'label' => __( 'Upsell IDs', 'newsletter-optin-box' ),
				'type'  => 'array',
			),
			'cross_sell_ids'          => array(
				'label' => __( 'Cross sell IDs', 'newsletter-optin-box' ),
				'type'  => 'array',
			),
			'reviews_allowed'         => array(
				'label'   => __( 'Reviews allowed', 'newsletter-optin-box' ),
				'type'    => 'boolean',
				'actions' => array( $action ),
			),
			'purchase_note'           => array(
				'label'   => __( 'Purchase note', 'newsletter-optin-box' ),
				'type'    => 'string',
				'actions' => array( $action ),
			),
			'downloadable'            => array(
				'label'   => __( 'Downloadable', 'newsletter-optin-box' ),
				'type'    => 'boolean',
				'actions' => array( $action ),
			),
			'virtual'                 => array(
				'label'   => __( 'Virtual', 'newsletter-optin-box' ),
				'type'    => 'boolean',
				'actions' => array( $action ),
			),
			'shipping_class_id'       => array(
				'label' => __( 'Shipping class ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'download_limit'          => array(
				'label'   => __( 'Download limit', 'newsletter-optin-box' ),
				'type'    => 'number',
				'actions' => array( $action ),
			),
			'download_expiry'         => array(
				'label'   => __( 'Download expiry', 'newsletter-optin-box' ),
				'type'    => 'number',
				'actions' => array( $action ),
			),
			'average_rating'          => array(
				'label' => __( 'Average rating', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'review_count'            => array(
				'label' => __( 'Review count', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'related'                 => array(
				'label' => __( 'Related Products', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Related Products', 'newsletter-optin-box' ),
					'description' => __( 'Displays related products.', 'newsletter-optin-box' ),
					'icon'        => 'products',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'product',
				),
			),
			'cross_sells'             => array(
				'label' => __( 'Cross sells', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Product Cross sells', 'newsletter-optin-box' ),
					'description' => __( 'Displays the cross sells for the product.', 'newsletter-optin-box' ),
					'icon'        => 'products',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'product',
				),
			),
			'upsells'                 => array(
				'label' => __( 'Upsells', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Product Upsells', 'newsletter-optin-box' ),
					'description' => __( 'Displays the upsells for the product.', 'newsletter-optin-box' ),
					'icon'        => 'products',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'product',
				),
			),
			'attribute'               => array(
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
			'meta'                    => $this->meta_key_tag_config(),
		);

		$fields['categories']['actions']      = array( $action );
		$fields['categories']['action_props'] = array(
			$action => array(
				'description'  => sprintf(
					/* translators: %s: Object type label. */
					__( 'Enter a comma-separated list of %1$s %2$s.', 'newsletter-optin-box' ),
					strtolower( $this->singular_label ),
					strtolower( $fields['categories']['label'] )
				),
				'show_in_meta' => true,
			),
		);

		$fields['tags']['actions']      = array( $action );
		$fields['tags']['action_props'] = array(
			$action => array(
				'description'  => sprintf(
					/* translators: %s: Object type label. */
					__( 'Enter a comma-separated list of %1$s %2$s.', 'newsletter-optin-box' ),
					strtolower( $this->singular_label ),
					strtolower( $fields['tags']['label'] )
				),
				'show_in_meta' => true,
			),
		);
		return apply_filters( 'noptin_post_type_known_custom_fields', $fields, $this->type );
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
				'woocommerce_' . $this->type . '_purchased' => array(
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
					'subject'     => 'customer',
					'provides'    => array( 'shop_order', 'order_item' ),
				),
				'woocommerce_' . $this->type . '_refunded' => array(
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
					'subject'     => 'customer',
					'provides'    => array( 'shop_order', 'order_item' ),
				),
			)
		);
	}

	/**
	 * Fired when a product is purchased.
	 *
	 * @param int|\WC_Order $order_id The order being acted on.
	 */
	public function on_purchase( $order_id ) {
		if ( ! Orders::is_complete() ) {
			return;
		}

		$this->on_purchase_or_refund( $order_id, 'purchased' );
	}

	/**
	 * Fired when a product is refunded.
	 *
	 * @param int|\WC_Order $order_id The order being acted on.
	 */
	public function on_refund( $order_id ) {
		$this->on_purchase_or_refund( $order_id, 'refunded' );
	}

	/**
	 * Fired when a product is purchased or refunded.
	 *
	 * @param int|\WC_Order $order_id The order being acted on.
	 * @param string        $action   The action being performed.
	 */
	public function on_purchase_or_refund( $order_id, $action ) {

		if ( is_numeric( $order_id ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = $order_id;
		}

		// Ensure we have an order.
		if ( empty( $order ) || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		// Prepare the order customer.
		$customer = Orders::get_order_customer( $order );

		// Loop through the order items.
		foreach ( $order->get_items() as $item ) {

			// Ensure we have a product.
			/** @var \WC_Order_Item_Product $item */
			$product = $item->get_product();
			if ( empty( $product ) ) {
				continue;
			}

			// Ensure we have a product id.
			$product_id = $product->get_id();
			if ( empty( $product_id ) ) {
				continue;
			}

			// Trigger the event.
			$this->trigger(
				'woocommerce_' . $this->type . '_' . $action,
				array(
					'email'       => $order->get_billing_email(),
					'object_id'   => $product_id,
					'subject_id'  => $customer,
					'unserialize' => array(
						'order.status' => $order->get_status(),
					),
					'provides'    => array(
						'shop_order' => $order->get_id(),
						'order_item' => $item->get_id(),
					),
				)
			);
		}
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

		if ( 'woocommerce_' . $this->type . '_purchased' === $rule->get_trigger_id() || 'woocommerce_' . $this->type . '_refunded' === $rule->get_trigger_id() ) {

			$args = array();

			if ( 'woocommerce_' . $this->type . '_refunded' === $rule->get_trigger_id() ) {
				$args = array( 'status' => array( 'wc-refunded' ) );
			}

			// Fetch latest order.
			$order = Orders::get_test_order( $args );
			$items = $order->get_items();

			if ( empty( $items ) ) {
				throw new \Exception( 'No order item found.' );
			}

			/** @var \WC_Order_Item_Product $item */
			$item = current( $items );
			return array(
				'email'      => $order->get_billing_email(),
				'object_id'  => $item->get_product_id(),
				'subject_id' => Orders::get_order_customer( $order ),
				'provides'   => array(
					'shop_order' => $order->get_id(),
					'order_item' => $item->get_id(),
				),
			);
		}

		return parent::get_test_args( $rule );
	}

	/**
	 * Creates or updates a post.
	 *
	 * @param array $args
	 */
	public function create_post( $args ) {

		if ( ! empty( $args['id'] ) ) {
			$product = wc_get_product( (int) $args['id'] );

			if ( empty( $product ) ) {
				return 'Invalid product ID.';
			}

			unset( $args['id'] );
		} elseif ( ! empty( $args['sku'] ) ) {
			$product_id = wc_get_product_id_by_sku( $args['sku'] );

			if ( ! empty( $product_id ) ) {
				$product = wc_get_product( $product_id );
				unset( $args['sku'] );
			}
		}

		if ( empty( $product ) ) {
			$product = wc_get_product_object( $args['type'] );
		}

		foreach ( array( 'tags', 'categories' ) as $tax ) {
			if ( empty( $args[ $tax ] ) ) {
				continue;
			}

			$terms    = noptin_parse_list( $args[ $tax ], true );
			$prepared = array();
			$taxonomy = 'tags' === $tax ? 'product_tag' : 'product_cat';

			foreach ( $terms as $term ) {
				if ( is_numeric( $term ) ) {
					$prepared[] = (int) $term;
				} else {
					$term = get_term_by( 'name', sanitize_text_field( $term ), $taxonomy );

					if ( $term ) {
						$prepared[] = (int) $term->term_id;
					}
				}
			}

			if ( 'categories' === $tax ) {
				$product->set_category_ids( $prepared );
			} else {
				$product->set_tag_ids( $prepared );
			}

			unset( $args[ $tax ] );
		}

		foreach ( $args as $key => $value ) {
			if ( is_callable( array( $product, "set_$key" ) ) ) {
				try {
					$product->{"set_$key"}( $value );
				} catch ( \Exception $e ) {
					noptin_error_log( $e->getMessage() );
				}
			}
		}

		$product->save();
	}

	/**
	 * Deletes a post.
	 *
	 * @param array $args
	 */
	public function delete_post( $args ) {

		if ( empty( $args['id'] ) ) {
			return new \WP_Error( 'noptin_invalid_post_id', 'Post ID is required.' );
		}

		if ( is_numeric( $args['id'] ) ) {
			$product_id = $args['id'];
		} else {
			$product_id = wc_get_product_id_by_sku( $args['id'] );
		}

		if ( empty( $product_id ) ) {
			return new \WP_Error( 'noptin_invalid_post_id', 'Post ID is required.' );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return new \WP_Error( 'noptin_post_not_found', 'Post not found.' );
		}

		return $product->delete( ! empty( $args['force_delete'] ) );
	}
}
