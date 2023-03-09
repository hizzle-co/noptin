<?php defined( 'ABSPATH' ) || exit; ?>
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
		font-size: 15px;
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

	/* Yahoo paragraph fix */
	p {
		margin: 1em 0;
	}

	h1, h2, h3, h4, h5, h6 {
		font-weight: 700;
		color: #cecece;
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
		font-family: Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
		color: #454545;
	}

	body {
		-webkit-text-size-adjust: 100%;
		-ms-text-size-adjust: 100%;
		width: 100%!important;
		height: 100%;
		font-weight: 400;
		font-size: 100%;
		line-height: 1.6;
	}

	h1, h2, h3, h4, h5, h6 {
		margin: 20px 0 10px;
		color: #000;
		line-height: 1.2;
	}

	div, p, ul, ol {
		margin-bottom: 10px;
		font-weight: normal;
		line-height: 1.4;
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
	}

	.footer-wrap .container p {
		font-size: 12px;
		color: #666;
	}

	table.footer-wrap a {
		color: #999;
	}


	/* Give it some responsive love */
	.container {
		display: block!important;
		max-width: 600px!important;
		margin: 0 auto!important; /* makes it centered */
		clear: both!important;
	}

	/* Set the padding on the td rather than the div for Outlook compatibility */
	.body-wrap .container {
		padding: 30px;
	}

	/* This should also be a block element, so that it will fill 100% of the .container */
	.content {
		max-width: 600px;
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
