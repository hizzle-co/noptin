<?php
/**
 * Displays products in a grid.
 *
 * Override this template by copying it to yourtheme/noptin/woocommerce/email-products-grid.php
 *
 * @var WC_Product[] $products
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_array( $products ) ) {
	return;
}

?>

<style type="text/css">

	.digest-grid-product {
		word-wrap: break-word;
		margin-right: 0;
		font-size: 14px;
		box-sizing: border-box;
		margin-bottom:24px;
		border-spacing:0;
		border: 1px solid #e0dede;
		border-radius: 4px;
		text-align: center;
	}

	.digest-grid-product-image-container {
		margin-top: 0;
		padding-top: 0;
	}

	.digest-grid-product img {
		max-width: 100%;
		height: auto !important;
	}

	.digest-grid-product a {
		text-decoration:none;
		color:#333333;
	}

	.digest-grid-product-title {
		font-size:18px;
		line-height:1.22;
		font-weight:700;
		margin: 30px 10px 4px !important;
		word-break: break-word;
	}

	.digest-grid-product-excerpt {
		line-height:1.33;
		font-size:14px;
		font-weight: 400;
		margin: 16px 10px 8px !important;
		word-break: break-word;
	}

	.digest-grid-product-meta {
		font-size:13px;
		color:#757575;
		margin: 0 10px 30px !important;
		word-break: break-word;
	}

	.digest-grid-product-meta a {
		color:#757575;
	}

	@media (max-width: 480px) {
		.product-digest-grid-one,
		.product-digest-grid-two {
			display:block !important;
  			width:100% !important;
			margin-right: 0 !important;
		}
	}

</style>

<!--[if true]>
<table role="presentation" width="100%" style="all:unset;opacity:0;">
	<tr>
<![endif]-->

<!--[if false]></td></tr></table><![endif]-->
<div style="display:table;width:100%;max-width:100%;">
	<!--[if true]>
	<td width="50%" v-align="top">
	<![endif]-->
	<!--[if !true]><!-->
	<div class="product-digest-grid-one" style="display:table-cell;vertical-align: top;width:50%;padding-right: 20px;">
	<!--<![endif]-->
		<?php foreach ( $products as $i => $product ) : ?>
			<?php if ( noptin_is_even( $i ) ) : ?>
			<div class="digest-grid-product digest-grid-product-type-<?php echo esc_attr( sanitize_html_class( $product->get_type() ) ); ?>">

				<p class="digest-grid-product-image-container">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>" style="display: block;" target="_blank">
						<?php echo wp_kses_post( Noptin_WooCommerce_Automated_Email_Type::get_product_image( $product, 'medium' ) ); ?>
					</a>
				</p>

				<p class="digest-grid-product-title">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>" target="_blank">
						<?php echo wp_kses_post( $product->get_name() ); ?>
					</a>
				</p>

				<p class="digest-grid-product-excerpt">
					<?php echo wp_kses_post( $product->get_short_description() ); ?>
				</p>

				<p class="digest-grid-product-meta">
					<strong><?php echo wp_kses_post( $product->get_price_html() ); ?></strong>
				</p>
			</div>
			<?php endif; ?>
		<?php endforeach; ?>
	<!--[if !true]><!-->
	</div>
	<!--<![endif]-->

	<!--[if true]>
    </td>
	<![endif]-->
	<!--[if true]>
	<td width="50%" v-align="top">
	<![endif]-->
	<!--[if !true]><!-->
    <div class="product-digest-grid-two" style="display:table-cell;vertical-align: top;width:50%">
	<!--<![endif]-->
	<?php foreach ( $products as $i => $product ) : ?>
		<?php if ( ! noptin_is_even( $i ) ) : ?>
			<div class="digest-grid-product digest-grid-product-type-<?php echo esc_attr( sanitize_html_class( $product->get_type() ) ); ?>">

				<p class="digest-grid-product-image-container">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>" style="display: block;" target="_blank">
						<?php echo wp_kses_post( Noptin_WooCommerce_Automated_Email_Type::get_product_image( $product, 'medium' ) ); ?>
					</a>
				</p>

				<p class="digest-grid-product-title">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>" target="_blank">
						<?php echo wp_kses_post( $product->get_name() ); ?>
					</a>
				</p>

				<p class="digest-grid-product-excerpt">
					<?php echo wp_kses_post( $product->get_short_description() ); ?>
				</p>

				<p class="digest-grid-product-meta">
					<strong><?php echo wp_kses_post( $product->get_price_html() ); ?></strong>
				</p>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
	<!--[if !true]><!-->
	</div>
	<!--<![endif]-->
	<!--[if true]>
	</td>
	<![endif]-->
</div>
<!--[if true]>
	</tr>
</table>
<![endif]-->
