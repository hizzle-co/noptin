<?php

	/**
	 * Email Styles template.
	 *
	 * @var Noptin_Email_Generator $generator
	 */

	defined( 'ABSPATH' ) || exit;

	$brand_color = get_noptin_option( 'brand_color' );
	$brand_color = empty( $brand_color ) ? '#1a82e2' : $brand_color;
?>

<style type="text/css">

	/**
	 * Fix centering issues in Android 4.4.
	 */
	div[style*="margin: 16px 0;"] {
		margin: 0 !important;
	}

	/**
	* Remove extra space added to tables and cells in Outlook.
	*/
	table,
	td {
		mso-table-rspace: 0pt;
		mso-table-lspace: 0pt;
	}

	/* Outlook 07, 10 Padding issue fix */
	table td {
		border-collapse: collapse;
	}

	/**
	 * Better fluid images in Internet Explorer.
	 */
	img, figure {
		outline: none;
		text-decoration: none;
		max-width: 100%;
		height: auto;
		-ms-interpolation-mode: bicubic;
	}

	/**
	* Remove blue links for iOS devices.
	*/
	a[x-apple-data-detectors] {
		font-family: inherit !important;
		font-size: inherit !important;
		font-weight: inherit !important;
		line-height: inherit !important;
		color: inherit !important;
		text-decoration: none !important;
	}

	a {
		color: <?php echo esc_html( $brand_color ); ?>;
	}

	.bg-brand {
		background-color: <?php echo esc_html( $brand_color ); ?>;
	}

	a img {
		border: none;
	}

	.image_fix {
		display: block;
	}

	p, ul, ol {
		margin-bottom: 10px;
		margin-top: 10px;
		font-weight: normal;
		line-height: 1.4;
	}

	ul li,
	ol li {
		margin-left: 5px;
		list-style-position: inside;
	}

	img {
		height: auto;
		line-height: 100%;
		text-decoration: none;
		border: 0;
		outline: none;
	}

	.wp-caption {
		margin-bottom: 1.5em;
		max-width: 100%;
	}

	.wp-caption img[class*="wp-image-"] {
		display: block;
		margin-left: auto;
		margin-right: auto;
	}

	.wp-caption .wp-caption-text {
		margin: 0.8075em 0;
	}

	.wp-caption-text {
		text-align: center;
	}

	.gallery {
		margin-bottom: 1.5em;
	}

	.gallery-item {
		display: inline-block;
		text-align: center;
		vertical-align: top;
		width: 100%;
	}

	.gallery-columns-2 .gallery-item {
		max-width: 50%;
	}

	.gallery-columns-3 .gallery-item {
		max-width: 33.33%;
	}

	.gallery-columns-4 .gallery-item {
		max-width: 25%;
	}

	.gallery-columns-5 .gallery-item {
		max-width: 20%;
	}

	.gallery-columns-6 .gallery-item {
		max-width: 16.66%;
	}

	.gallery-columns-7 .gallery-item {
		max-width: 14.28%;
	}

	.gallery-columns-8 .gallery-item {
		max-width: 12.5%;
	}

	.gallery-columns-9 .gallery-item {
		max-width: 11.11%;
	}

	.gallery-caption {
		display: block;
	}

	.alignleft {
		float: left;
		margin-right: 1.5em;
	}

	.alignright {
		float: right;
		margin-left: 1.5em;
	}

	.aligncenter {
		clear: both;
		display: block;
		margin-left: auto;
		margin-right: auto;
	}

	.noptin-round {
		border-radius: 6px;
	}

	.attachment-post-thumbnail {
		width: 100% !important;
	}

    table.noptin-wc-product-grid {
		width: 100%;
	}

	.noptin-wc-product-grid-container {
		font-size: 0px;
		margin: 10px 0 10px;
	}

	.noptin-wc-product-grid-item-col {
		width: 30.5%;
		display: inline-block;
		text-align:center;
		padding: 0 0 30px;
		vertical-align:top;
		word-wrap:break-word;
		margin-right: 4%;
		font-size: 14px;
	}

	table.noptin-wc-product-list  {
		margin: 10px 0;
		border-top: 1px solid #dddddd;
	}

	table.noptin-wc-product-list td {
		padding: 13px;
		border-bottom: 1px solid #dddddd;
	}

	table.noptin-wc-product-list td.image {
		padding-left: 0 !important;
	}

	table.noptin-wc-product-list td.last {
		padding-right: 0 !important;
	}

	table.noptin-wc-order-table img,
	table.noptin-wc-product-grid img,
	table.noptin-wc-product-list td img {
		max-width: 100%;
		height: auto !important;
	}

	table.noptin-wc-product-list h3,
	table.noptin-wc-product-list p {
		margin: 5px 0 !important;
	}

	.margin-none p {
		margin: 0;
	}

	.noptin-round {
		border-radius: 6px;
	}

	<?php
		// Note that esc_html() cannot be used because `div &gt; span` is not interpreted properly.
		echo strip_tags( get_noptin_option( 'custom_css', '' ) ); // phpcs:ignore
		do_action( 'noptin_email_styles', $generator );
	?>
</style>
