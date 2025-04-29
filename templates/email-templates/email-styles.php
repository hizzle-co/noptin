<?php

	/**
	 * Email Styles template.
	 *
	 * @var Noptin_Email_Generator $generator
	 */

	defined( 'ABSPATH' ) || exit;

	$brand_color = get_noptin_option( 'brand_color' );
	$brand_color = empty( $brand_color ) ? '#1a82e2' : $brand_color;

	if ( ! empty( $generator->campaign ) ) {
		$override = $generator->campaign->get( 'link_color' );

		if ( ! empty( $override ) ) {
			$brand_color = $override;
		}
	}

?>

<style type="text/css">

	/**
	 * Fix centering issues in Android 4.4.
	 */
	div[style*="margin: 16px 0;"] {
		margin: 0 !important;
	}

	.ExternalClass {width: 100%;}

	/**
	* Remove extra space added to tables and cells in Outlook.
	*/
	table,
	td {
		mso-table-rspace: 0pt;
		mso-table-lspace: 0pt;
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

	h1, h2, h3, h4, h5, h6 {
		mso-line-height-rule: exactly;
	}

	a {
		color: <?php echo esc_html( $brand_color ); ?>;
		text-decoration: none;
	}

	a.noptin-raw-link {
		word-break: break-all;
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

	ul,
	ol {
		padding-left: 16px;
	}

	ul li,
	ol li {
		margin-left: 5px;
		margin-bottom: 5px;
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
		font-size: 0.9em;
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
		padding-top: 0 !important;
	}

	.digest-list-product-excerpt {
		line-height: 1.33;
		font-size: 15px;
		margin: 0 0 10px !important;
		padding-top: 0 !important;
	}

	.digest-list-product-meta {
		font-size:13px;
		color:#757575;
		margin: 0 !important;
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
	}

	.digest-grid-product-excerpt {
		line-height:1.33;
		font-size:14px;
		font-weight: 400;
		margin: 16px 10px 8px !important;
	}

	.digest-grid-product-meta {
		font-size:13px;
		color:#757575;
		margin: 0 10px 30px !important;
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

	.digest-list-title {
		font-size: 24px;
		line-height: 1.25;
		font-weight: 700;
		margin: 0 0 10px !important;
		padding-top: 0 !important;
	}

	.digest-list-description {
		font-size: 15px;
		line-height: 1.33;
		margin: 0 0 30px !important;
		padding-top: 0 !important;
	}

	.digest-list-post {
		min-width:100%;
		width:100%;
		margin-bottom:30px;
		border-spacing:0;
	}

	.digest-grid-post {
		word-wrap: break-word;
		margin-right: 0;
		font-size: 14px;
		box-sizing: border-box;
		margin-bottom:24px;
		border-spacing:0;
		border: 1px solid #e0dede;
		border-radius: 4px;
	}

	.digest-list-post a {
		text-decoration:none;
		color:#333333;
	}

	.digest-list-post-title {
		font-size:18px;
		line-height:1.22;
		font-weight:700;
		margin: 0 0 10px !important;
		padding-top: 0 !important;
	}

	.digest-list-post-excerpt {
		line-height: 1.33;
		font-size: 15px;
		margin: 0 0 10px !important;
		padding-top: 0 !important;
	}

	.digest-list-post-meta {
		font-size:13px;
		color:#757575;
		margin: 0 !important;
	}

	.digest-list-post-meta a {
		color:#757575;
	}

	.digest-grid-post-image-container {
		margin-top: 0;
		padding-top: 0;
	}

	.digest-grid-post img {
		max-width: 100%;
		height: auto !important;
	}

	.digest-grid-post a {
		text-decoration:none;
		color:#333333;
	}

	.digest-grid-post-title {
		font-size:18px;
		line-height:1.22;
		font-weight:700;
		margin: 30px 10px 4px !important;
	}

	.digest-grid-post-excerpt {
		line-height:1.33;
		font-size:15px;
		margin: 16px 10px 8px !important;
	}

	.digest-grid-post-meta {
		font-size:13px;
		color:#757575;
		margin: 0 10px 30px !important;
	}

	.digest-grid-post-meta a {
		color:#757575;
	}

	@media (max-width: 480px) {
		.post-digest-grid-one,
		.post-digest-grid-two {
			display:block !important;
  			width:100% !important;
			margin-right: 0 !important;
		}
	}

	.margin-none p {
		margin: 0;
	}

	.noptin-round {
		border-radius: 6px;
	}

	.noptin-columns {
		display: table;
		width: 100%;
	}

	.noptin-column {
		display: table-cell;
	}

	@media only screen and (max-width: 360px) {
		.noptin-is-stacked-on-mobile {
			display: block !important;
		}

		.noptin-is-stacked-on-mobile.noptin-column {
			vertical-align: top !important;
			width: 100% !important;
		}

		.noptin-block-group__inner {
			width: 99%;
		}
	}

	.screen-reader-text {
		display: none !important;
	}

	ins {
		text-decoration: none;
	}

	.email-order-details,
	.email-order-details table {
		border-collapse: collapse;
	}

	.email-order-details tfoot th {
		text-align: right;
	}

	table .email-order-details td:first-child,
	table .email-order-details th:first-child {
		padding-<?php echo is_rtl() ? 'right' : 'left'; ?>: 0;
	}

	table .email-order-details td:last-child,
	table .email-order-details th:last-child {
		padding-<?php echo is_rtl() ? 'left' : 'right'; ?>: 0;
	}

	.email-order-details tbody tr:last-child td {
		border-bottom: 1px solid #333;
	}

	.order-item-data td {
		border: 0 !important;
		padding: 0 !important;
		vertical-align: middle;
	}

	.email-order-details .order-totals td,
	.email-order-details .order-totals th {
		font-weight: normal;
		padding-bottom: 5px;
		padding-top: 5px;
	}

	.email-order-details .order-totals-last td,
	.email-order-details .order-totals-last th {
		border-bottom: none;
	}

	.email-order-details .order-customer-note td {
		border-bottom: 1px solid #333;
		padding-bottom: 24px;
		padding-top: 24px;
	}

	<?php
		// Note that esc_html() cannot be used because `div &gt; span` is not interpreted properly.
		echo strip_tags( get_noptin_option( 'custom_css', '' ) ); // phpcs:ignore

		if ( ! empty( $generator->campaign ) ) {
			$custom_css = $generator->campaign->get( 'custom_css' );

			if ( ! empty( $custom_css ) ) {
				echo strip_tags( $custom_css ); // phpcs:ignore
			}
		}

		do_action( 'noptin_email_styles', $generator );
	?>
</style>
