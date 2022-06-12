<?php
/**
 * Displays products in a list.
 *
 * Override this template by copying it to yourtheme/noptin/woocommerce/email-products-list.php
 *
 * @var WC_Product[] $products
 */

defined( 'ABSPATH' ) || exit;

?>

<?php if ( is_array( $products ) ) : ?>

	<table cellspacing="0" cellpadding="0" style="width: 100%;" class="noptin-wc-product-list"><tbody>

		<?php foreach ( $products as $product ) : ?>
			<tr>

				<td class="image" width="25%">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo wp_kses_post( Noptin_WooCommerce_Automated_Email_Type::get_product_image( $product ) ); ?></a>
				</td>

				<td>
					<h3><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
				</td>

				<td align="right" class="last" width="35%">
					<p class="price" style="color: #000; font-size: 20px;"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
				</td>

			</tr>
		<?php endforeach; ?>

	</tbody></table>

<?php endif; ?>
