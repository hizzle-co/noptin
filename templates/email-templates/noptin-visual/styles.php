<?php

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var array $settings
	 */
?>

<style type="text/css">

	/**
	 * Avoid browser level font resizing.
	 * 1. Windows Mobile
	 * 2. iOS / OSX
	 */
	body,
	table,
	td,
	div,
	p,
	a {
		-ms-text-size-adjust: 100%; /* 1 */
		-webkit-text-size-adjust: 100%; /* 2 */
	}

	body,
	.wrapper-div {
		background-color: <?php echo esc_attr( $settings['background_color'] ); ?>;
		width: 100% !important;
		height: 100% !important;
		padding: 0 !important;
		margin: 0 !important;
		overflow: auto;
		box-sizing: border-box;
		color: <?php echo esc_attr( $settings['color'] ); ?>;
		font-family: <?php echo esc_html( $settings['font_family'] ); ?>;
		font-size: <?php echo esc_attr( $settings['font_size'] ); ?>;
		line-height: <?php echo esc_attr( $settings['line_height'] ); ?>;
		font-weight: <?php echo esc_attr( $settings['font_weight'] ); ?>;
		font-style: <?php echo esc_attr( $settings['font_style'] ); ?>;
		word-wrap: break-word;
    	word-break: break-all;
	}

	div,
	ol,
	ul,
	p {
		font-size: 1em;
	}

	.noptin-button-link {
		color: <?php echo esc_attr( $settings['button_color'] ); ?>;
	}

	.noptin-button-link__wrapper {
		background-color: <?php echo esc_attr( $settings['button_background'] ); ?>;
		color: <?php echo esc_attr( $settings['button_color'] ); ?>;
	}

	/**
	 * Collapse table borders to avoid space between cells.
	 */
	table {
		border-collapse: collapse;
	}

	p, h1, h2, h3, h4, h5, h6, .noptin-block__margin-wrapper {
		margin: 0px 10px 16px;
	}

	.wp-block-noptin-separator {
		margin-bottom: 16px;
	}
	.wp-block-image {
		margin: 0 0 16px;
	}

	.noptin-button-link {
		padding-top: 10px;
		padding-right: 25px;
		padding-bottom: 10px;
		padding-left: 25px;
	}

	h1, h2, h3, h4, h5, h6 {
		font-weight: 700;
	}

	h1 {
		font-size: 2em;
		line-height: 48px;
	}

	h2{
		font-size: 1.75em;
		line-height: 36px;
	}

	h3 {
		font-size: 1.5em;
		line-height: 30px;
	}

	h4 {
		font-size: 1.25em;
		line-height: 26px;
	}

	h5 {
		font-size: 1.125em;
		line-height: 22px;
	}

	h6 {
		font-size: 16px;
		line-height: 20px;
	}

	img, figure {
		height: auto;
		line-height: 100%;
		text-decoration: none;
		border: 0;
		outline: none;
		max-width: 100%;
	}

	.wp-block-noptin-group {
		margin-left: auto;
		margin-right: auto;
		margin-top: 20px;
		margin-bottom: 20px;
	}

	.noptin-block-group__inner {
		padding-top: 20px;
		padding-bottom: 20px;
	}

	<?php
		if ( ! empty( $settings['block_css'] ) ) {

			foreach ( (array) $settings['block_css'] as $block_css ) {
				// Note that esc_html() cannot be used because `div &gt; span` is not interpreted properly.
				echo strip_tags( $block_css ); // phpcs:ignore
			}
		}
	?>
</style>
