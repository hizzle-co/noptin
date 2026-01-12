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

	/* Client-specific Styles */
	#outlook a {
		padding:0;
	}

	body {
		padding: 0;
	}

	body,
	.wrapper-div {
		background-color: <?php echo esc_attr( $settings['background_color'] ); ?>;
		width: 100%;
		margin: 0;
		overflow: auto;
		box-sizing: border-box;
		color: <?php echo esc_attr( $settings['color'] ); ?>;
		font-family: <?php echo wp_kses_post( $settings['font_family'] ); ?>;
		font-size: <?php echo esc_attr( $settings['font_size'] ); ?>;
		line-height: <?php echo esc_attr( $settings['line_height'] ); ?>;
		font-weight: <?php echo esc_attr( $settings['font_weight'] ); ?>;
		font-style: <?php echo esc_attr( $settings['font_style'] ); ?>;
	}

	<?php if ( is_array( $settings['background_image'] ) && ! empty( $settings['background_image']['url'] ) ) : ?>
		.wrapper-div {
			background-image: url(<?php echo esc_url( $settings['background_image']['url'] ); ?>);
			background-size: cover;
			background-repeat: no-repeat;
		}
	<?php endif; ?>

	@media all {
		.ExternalClass {
			width: 100%;
  		}

		.ExternalClass,
		.ExternalClass p,
		.ExternalClass span,
		.ExternalClass font,
		.ExternalClass td,
		.ExternalClass div {
			line-height: 100%;
		}

		.apple-link a {
			color: inherit !important;
			font-family: inherit !important;
			font-size: inherit !important;
			font-weight: inherit !important;
			line-height: inherit !important;
			text-decoration: none !important;
		}

		#MessageViewBody a {
			color: inherit;
			text-decoration: none;
			font-size: inherit;
			font-family: inherit;
			font-weight: inherit;
			line-height: inherit;
		}
	}

	div,
	ol,
	ul,
	p {
		font-size: 1em;
	}

	#noptin-email-content .noptin-button-link__wrapper .noptin-button-link,
	#noptin-email-content .noptin-button-link__wrapper .noptin-button-link:hover,
	#noptin-email-content .noptin-button-link__wrapper .noptin-button-link:focus,
	#noptin-email-content .noptin-button-link__wrapper .noptin-button-link:active {
		color: <?php echo esc_attr( $settings['button_color'] ); ?>;
	}

	.noptin-button-link__wrapper {
		background-color: <?php echo esc_attr( $settings['button_background'] ); ?>;
		color: <?php echo esc_attr( $settings['button_color'] ); ?>;
		padding-top: 16px;
		padding-right: 40px;
		padding-bottom: 16px;
		padding-left: 40px;
	}

	table.noptin-button-block__wrapper {
		border-collapse: separate;
		width: 100%;
		margin: 0;
		line-height: 100%;
	}

	table.noptin-button-block__wrapper table {
		border-collapse: separate;
		border-spacing: 0;
	}

	table.noptin-image-block__wrapper {
		border-spacing: 0;
	}

	table.noptin-image-block__wrapper:not(.noptin-image-block__wrapper-is-aligned) {
		width: 100%;
	}

	table.noptin-image-block__wrapper img {
		vertical-align: bottom;
	}

	table.noptin-image-block__wrapper div.noptin-block__margin-wrapper {
		overflow: hidden;
	}

	.wp-block-noptin-table table,
	.wp-block-noptin-table td,
	.wp-block-noptin-advanced-table > table,
	.wp-block-noptin-table-content-row > td {
		border-collapse: collapse;
	}

	.wp-block-noptin-advanced-table {
		max-width: 100%;
		overflow: auto;
		box-sizing: border-box;
		text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
	}
	.wp-block-noptin-advanced-table > table {
		width: 100%;
	}

	tfoot.wp-block-noptin-table-section > .wp-block-noptin-table-row > .wp-block-noptin-table-cell:first-child {
		text-align: right;
	}

	.wp-block-noptin-table-cell {
		border-bottom: 1px solid #ddd;
	}

	table {
		border-collapse: separate;
	}

	p, h1, h2, h3, h4, h5, h6, .noptin-block__margin-wrapper, .wp-block-noptin-badge table {
		margin-top: 0px;
		margin-left: 10px;
		margin-right: 10px;
		margin-bottom: 16px;
	}

	.wp-block-noptin-badge {
		letter-spacing: 1px;
	}

	.wp-block-noptin-table-cell > h1:last-child,
	.wp-block-noptin-table-cell > a:last-child > h1:last-child,
	.wp-block-noptin-table-cell > h2:last-child,
	.wp-block-noptin-table-cell > a:last-child > h2:last-child,
	.wp-block-noptin-table-cell > h3:last-child,
	.wp-block-noptin-table-cell > a:last-child > h3:last-child,
	.wp-block-noptin-table-cell > h4:last-child,
	.wp-block-noptin-table-cell > a:last-child > h4:last-child,
	.wp-block-noptin-table-cell > h5:last-child,
	.wp-block-noptin-table-cell > a:last-child > h5:last-child,
	.wp-block-noptin-table-cell > h6:last-child,
	.wp-block-noptin-table-cell > a:last-child > h6:last-child,
	p:last-child,
	.noptin-columns:last-child,
	.wp-block-noptin-badge:last-child table,
	.noptin-image-block__wrapper:last-child .noptin-block__margin-wrapper {
		margin-bottom: 0px;
	}

	/** Backward compatibility */
	/** Overide the above for headings inside of links */
	.wp-block-noptin-table-cell > a:not(:last-child) h1:only-child,
	.wp-block-noptin-table-cell > a:not(:last-child) h2:only-child,
	.wp-block-noptin-table-cell > a:not(:last-child) h3:only-child,
	.wp-block-noptin-table-cell > a:not(:last-child) h4:only-child,
	.wp-block-noptin-table-cell > a:not(:last-child) h5:only-child,
	.wp-block-noptin-table-cell > a:not(:last-child) h6:only-child {
		margin-bottom: 16px;
	}

	.noptin-records__wrapper,
	.noptin-columns,
	.wp-block-noptin-separator {
		margin-bottom: 16px;
	}

	.wp-block-noptin-separator {
		border: 0;
	}

	.noptin-column__inner > .noptin-image-block__wrapper:first-child .noptin-block__margin-wrapper {
		margin-left: 0;
		margin-right: 0;
		margin-top: 0;
	}

	.noptin-column__inner > .noptin-image-block__wrapper:last-child .noptin-block__margin-wrapper {
		margin-bottom: 0;
	}

	.noptin-column__inner img {
		width: 100%;
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
		font-size: 1em;
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

	.wp-block-noptin-group:first-child {
		margin-top: 20px;
	}

	.wp-block-noptin-group:last-child {
		margin-bottom: 20px;
	}

	.wp-block-noptin-group > table {
		overflow: hidden;
	}

	.noptin-block-group__inner > table > tbody > tr > td {
		padding-top: 50px;
		padding-right: 30px;
		padding-bottom: 50px;
		padding-left: 30px;
	}

	.noptin-block-group__inner > table > tbody > tr > td .noptin-block-group__inner > table > tbody > tr > td {
		padding-top: 10px;
		padding-right: 10px;
		padding-bottom: 10px;
		padding-left: 10px;
	}

	.noptin-block-group__inner {
		overflow: hidden;
	}

	.noptin-record {
		margin-left: 10px;
		margin-right: 10px;
		margin-top: 10px;
		margin-bottom: 10px;
		padding-left: 0;
		padding-right: 0;
		padding-top: 0;
		padding-bottom: 0;
	}

	.noptin-columns {
		display: table;
		table-layout: fixed;
		width: 100%;
		overflow: hidden;
	}

	.noptin-column {
		display: table-cell;
		overflow: hidden;
	}

	.noptin-column > div {
		margin-left: 10px;
		margin-right: 10px;
		overflow: hidden;
	}

	@media only screen and (max-width: 575px) {
		.noptin-is-stacked-on-mobile {
			display: block!important;
		}

		.noptin-is-stacked-on-mobile.noptin-column {
			vertical-align: top !important;
			width: 100% !important;
			margin-left: auto !important;
			margin-right: auto !important;
		}

		.noptin-is-stacked-on-mobile.noptin-column > div {
			margin-left: 0 !important;
			margin-right: 0 !important;
		}

		.wp-block-noptin-group > table {
			width: 100%;
		}

		p {
			word-break: break-word;
		}
	}

	.wp-block-noptin-coupon-code {
		word-break: break-word;
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
