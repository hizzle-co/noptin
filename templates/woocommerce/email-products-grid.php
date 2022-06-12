<?php
/**
 * Displays products in a list.
 *
 * Override this template by copying it to yourtheme/noptin/woocommerce/email-products-grid.php
 *
 * @var WC_Product[] $products
 */

defined( 'ABSPATH' ) || exit;

$n = 1;

?>

	<?php if ( is_array( $products ) ) : ?>

		<style>
			.noptin-wc-product-grid .noptin-wc-product-grid-item-col img {
				height: auto !important;
			}

			@media (max-width: 480px) {
				.noptin-wc-product-grid .noptin-wc-product-grid-item-col {
					width:100% !important;
					margin-right: 0 !important;
					display: block !important;
    				text-align: left !important;
				}
			}

		</style>

		<table cellspacing="0" cellpadding="0" class="noptin-wc-product-grid">
			<tbody><tr><td style="padding: 0;"><div class="noptin-wc-product-grid-container">

				<?php foreach ( $products as $product ) : ?>

					<div class="noptin-wc-product-grid-item-col" style="<?php echo ( $n % 3 ? '' : 'margin-right: 0;' ); ?>">

						<a href="<?php echo esc_url( $product->get_permalink() ); ?>" style="display: block;"><?php echo wp_kses_post( Noptin_WooCommerce_Automated_Email_Type::get_product_image( $product ) ); ?></a>
						<h3 style="text-align: center;"><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
						<p class="price" style="text-align: center;"><strong><?php echo wp_kses_post( $product->get_price_html() ); ?></strong></p>

					</div>

					<?php $n++; ?>
				<?php endforeach; ?>

			</div></td></tr></tbody>
		</table>

	<?php endif; ?>
