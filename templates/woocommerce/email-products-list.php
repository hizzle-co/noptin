<?php
/**
 * Displays products in a list.
 *
 * Override this template by copying it to yourtheme/noptin/woocommerce/email-products-list.php
 *
 * @var WC_Product[] $products
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_array( $products ) ) {
	return;
}

?>

<?php foreach ( $products as $i => $product ) : ?>

	<table cellspacing="0" cellpadding="0" class="digest-list-product digest-list-product-type-<?php echo esc_attr( sanitize_html_class( $product->get_type() ) ); ?>">
		<tbody>
			<tr style="vertical-align:top;">

				<td class="d-xs-block" width="150" style="width:150px; padding: 0;">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>" style="display: block;" target="_blank">
						<?php echo wp_kses_post( $product->get_image( 'thumbnail' ) ); ?>
					</a>
				</td>

				<td class="pl-xs-0 d-xs-block digest-list-product-content" valign="middle" style="padding-left: 20px; vertical-align: middle;">

					<p class="digest-list-product-title">
						<a href="<?php echo esc_url( $product->get_permalink() ); ?>" target="_blank">
							<?php echo wp_kses_post( $product->get_name() ); ?>
						</a>
					</p>

					<p class="digest-list-product-excerpt">
						<?php echo wp_kses_post( $product->get_short_description() ); ?>
					</p>

					<p class="digest-list-product-meta">
						<strong><?php echo wp_kses_post( $product->get_price_html() ); ?></strong>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<hr style="border-width: 0; background: #cec7c7; color: #cec7c7; height:1px; width:100%; margin:30px auto;">
<?php endforeach; ?>
