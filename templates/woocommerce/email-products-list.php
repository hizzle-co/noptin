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

<style type="text/css">

	.digest-list-product {
		min-width:100%;
		width:100%;
		margin-bottom:0;
		border-spacing:0;
	}

	.digest-list-product a {
		text-decoration:none;
		color:#333333;
	}

	.digest-list-product-title {
		font-size:18px;
		line-height:1.22;
		font-weight:700;
		margin: 0 0 10px !important;
		word-break: break-word;
		padding-top: 0 !important;
	}

	.digest-list-product-excerpt {
		line-height: 1.33;
		font-size: 15px;
		margin: 0 0 10px !important;
		padding-top: 0 !important;
		word-break: break-word;
	}

	.digest-list-product-meta {
		font-size:13px;
		color:#757575;
		margin: 0 !important;
		word-break: break-word;
	}

	.digest-list-product-meta a {
		color:#757575;
	}

    @media only screen and (max-width: 480px){

        .d-xs-block {
            display:block !important;
            width:100% !important;
        }

        .pl-xs-0 {
			padding-left: 0 !important;
        }

    }
</style>

<?php foreach ( $products as $i => $product ) : ?>

	<table cellspacing="0" cellpadding="0" class="digest-list-product digest-list-product-type-<?php echo esc_attr( sanitize_html_class( $product->get_type() ) ); ?>">
		<tbody>
			<tr style="vertical-align:top;">

				<td class="d-xs-block" width="150" style="width:150px; padding: 0;">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>" style="display: block;" target="_blank">
						<?php echo wp_kses_post( Noptin_WooCommerce_Automated_Email_Type::get_product_image( $product, 'thumbnail' ) ); ?>
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
