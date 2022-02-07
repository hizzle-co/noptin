<?php
/**
 * Displays products in a list.
 *
 * Override this template by copying it to yourtheme/noptin/woocommerce/email-products-grid.php
 *
 * @var WC_Product[] $products
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$n = 1;

?>

	<?php if ( is_array( $products ) ): ?>

		<style>
			.noptin-wc-product-grid .noptin-wc-product-grid-item img {
				height: auto !important;
			}
		</style>

		<table cellspacing="0" cellpadding="0" class="noptin-wc-product-grid">
			<tbody><tr><td style="padding: 0;"><div class="noptin-wc-product-grid-container">

				<?php foreach ( $products as $product ): ?>

					<div class="noptin-wc-product-grid-item" style="<?php echo ( $n % 3 ? '' : 'margin-right: 0;' ) ?>">

						<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo Noptin_WooCommerce_Automated_Email_Type::get_product_image( $product ) ?></a>
						<h3><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
						<p class="price"><strong><?php echo $product->get_price_html(); ?></strong></p>

					</div>

				<?php $n++; endforeach; ?>

			</div></td></tr></tbody>
		</table>

	<?php endif; ?>