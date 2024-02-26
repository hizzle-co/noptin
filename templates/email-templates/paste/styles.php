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
	table.body-wrap {
		overflow: auto;
		box-sizing: border-box;
		color: <?php echo esc_attr( $settings['color'] ); ?>;
		font-family: <?php echo wp_kses_post( $settings['font_family'] ); ?>;
		font-size: <?php echo esc_attr( $settings['font_size'] ); ?>;
		line-height: <?php echo esc_attr( $settings['line_height'] ); ?>;
		font-weight: <?php echo esc_attr( $settings['font_weight'] ); ?>;
		font-style: <?php echo esc_attr( $settings['font_style'] ); ?>;
		background-color: <?php echo esc_attr( $settings['background_color'] ); ?>;
	}

	div,
	ol,
	ul,
	p {
		font-size: 1em;
	}

	p, ul, ol, h1, h2, h3, h4, h5, h6  {
		margin: 1em 0;
	}

	.footer p{
		margin: 0;
		padding: 0;
	}

	body {
		width: 100% !important;
		height: 100% !important;
		padding: 0 !important;
		margin: 0 !important;
	}

	/**
	 * Collapse table borders to avoid space between cells.
	 */
	table {
		border-collapse: collapse !important;
	}

	h1, h2, h3, h4, h5, h6 {
		font-weight: 700;
	}

	h1 {
		font-size: 32px;
		line-height: 48px;
	}

	h2{
		font-size: 28px;
		line-height: 36px;
	}

	h3 {
		font-size: 24px;
		line-height: 30px;
	}

	h4 {
		font-size: 20px;
		line-height: 26px;
	}

	h5 {
		font-size: 18px;
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

</style>