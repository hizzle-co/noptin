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

$col_1_products = array();
$col_2_products = array();

foreach ( $products as $i => $product ) {
	if ( noptin_is_even( $i ) ) {
		$col_1_products[] = $product;
	} else {
		$col_2_products[] = $product;
	}
}

?>

<div style="display:table;width:100%;max-width:100%;">
	<div class="product-digest-grid-one" style="display:table-cell;vertical-align: top;width:50%;padding-right: 20px;">
		<?php foreach ( $col_1_products as $i => $product ) : ?>
			<div class="digest-grid-product digest-grid-product-type-<?php echo esc_attr( sanitize_html_class( $product->get_type() ) ); ?>">

				<p class="digest-grid-product-image-container">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>" style="display: block;" target="_blank">
						<?php echo wp_kses_post( $product->get_image( 'medium' ) ); ?>
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
		<?php endforeach; ?>

	</div>
	<div class="product-digest-grid-two" style="display:table-cell;vertical-align: top;width:50%">

		<?php foreach ( $col_2_products as $i => $product ) : ?>
			<div class="digest-grid-product digest-grid-product-type-<?php echo esc_attr( sanitize_html_class( $product->get_type() ) ); ?>">

				<p class="digest-grid-product-image-container">
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>" style="display: block;" target="_blank">
						<?php echo wp_kses_post( $product->get_image( 'medium' ) ); ?>
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
		<?php endforeach; ?>

</div>
</div>
