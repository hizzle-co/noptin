<?php

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var array $settings
	 */
?>

<style type="text/css">
	/* Based on The MailChimp Reset INLINE: Yes. */
	/* Client-specific Styles */
	#outlook a {
		padding: 0;
	}

	/* Force Outlook to provide a "view in browser" menu link. */
	body {
		width: 100% !important;
		margin: 0;
		padding: 0;
		-webkit-text-size-adjust: 100%;
		-ms-text-size-adjust: 100%;
	}

	/* Forces Hotmail to display normal line spacing.  More on that: http://www.emailonacid.com/forum/viewthread/43/ */
	#backgroundTable {
		margin: 0;
		padding: 0;
		width: 100% !important;
		line-height: 100% !important;
	}
	/* End reset */

	/* Some sensible defaults for images */

	img {
		outline: none;
		text-decoration: none;
		-ms-interpolation-mode: bicubic;
	}

	a img {
		border: none;
	}

	.image_fix {
		display: block;
	}

	p, ul, ol, h1, h2, h3, h4, h5, h6  {
		margin: 1em 0;
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

	/* Global */
	* {
		margin: 0;
		padding: 0;
	}

	/**
	 * Avoid browser level font resizing.
	 * 1. Windows Mobile
	 * 2. iOS / OSX
	 */
	body,
	table,
	td,
	div,
	ol,
	ul,
	p,
	a {
		-ms-text-size-adjust: 100%; /* 1 */
		-webkit-text-size-adjust: 100%; /* 2 */
	}

	div,
	ol,
	ul,
	p {
		font-size: 1em;
	}

	body,
	#backgroundTable,
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

	body {
		width: 100%!important;
		height: 100%;
	}

	h1, h2, h3, h4, h5, h6 {
		margin: 20px 0 10px;
		line-height: 1.2;
	}

	div, p, ul, ol {
		margin-bottom: 10px;
	}

	ul li,
	ol li {
		margin-left: 5px;
		list-style-position: inside;
	}

	/* Body */
	table.body-wrap {
		width: 100%;
		padding: 30px;
	}


	/* Footer */
	table.footer-wrap {
		width: 100%;
		clear: both!important;
		background-color: <?php echo esc_attr( $settings['background_color'] ); ?>
	}

	.footer-wrap .container p {
		font-size: 0.8em;
		color: <?php echo esc_attr( $settings['footer_text_color'] ); ?>;
	}

	table.footer-wrap a {
		color: #999;
	}

	/* Give it some responsive love */
	.container {
		display: block!important;
		max-width: <?php echo esc_attr( $settings['width'] ); ?>!important;
		margin: 0 auto!important; /* makes it centered */
		clear: both!important;
	}

	/* Set the padding on the td rather than the div for Outlook compatibility */
	.body-wrap .container {
		padding: 30px;
	}

	/* This should also be a block element, so that it will fill 100% of the .container */
	.content {
		max-width: <?php echo esc_attr( $settings['width'] ); ?>;
		margin: 0 auto;
		display: block;
	}

	/* Let's make sure tables in the content area are 100% wide */
	.content table {
		width: 100%;
	}

</style>

<!--[if gte mso 9]>
	<style>
		table,
			td,
			div,
			p,
			a {
				font-family: Arial, sans-serif;
			}
	</style>
<![endif]-->
