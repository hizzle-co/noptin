<style type="text/css">
	/**
     * Google webfonts. Recommended to include the .woff version for cross-client compatibility.
     */
    @media screen {
    	@font-face {
    		font-family: 'Merriweather';
    		font-style: normal;
    		font-weight: 400;
    		src: local('Merriweather'), local('Merriweather'), url(http://fonts.gstatic.com/s/merriweather/v8/ZvcMqxEwPfh2qDWBPxn6nmB7wJ9CoPCp9n30ZBThZ1I.woff) format('woff');
    	}

    	@font-face {
    		font-family: 'Merriweather Bold';
    		font-style: normal;
    		font-weight: 700;
    		src: local('Merriweather Bold'), local('Merriweather-Bold'), url(http://fonts.gstatic.com/s/merriweather/v8/ZvcMqxEwPfh2qDWBPxn6nhAPw1J91axKNXP_-QX9CC8.woff) format('woff');
    	}
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
	p,
	a {
		-ms-text-size-adjust: 100%; /* 1 */
		-webkit-text-size-adjust: 100%; /* 2 */
		font-family: 'Merriweather', sans-serif;
	}

	h1,
	h2,
	h3,
	h4,
	h5,
	h6 {
		font-family: 'Merriweather Bold', serif;
	}

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
	img {
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

	/**
	* Fix centering issues in Android 4.4.
	*/
	div[style*="margin: 16px 0;"] {
		margin: 0 !important;
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

	a {
		color: #CC7953;
	}

	img {
		height: auto;
		line-height: 100%;
		text-decoration: none;
		border: 0;
		outline: none;
	}

	p{
		margin: 1em 0;
		padding: 5px 0px 5px 0px;
	}

	.footer p{
		margin: 0;
		padding: 0;
	}

	.margin-none p {
		margin: 0;
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

	.cta-link {
		display: inline-block !important;
	}

	.attachment-post-thumbnail {
		width: 100% !important;
	}
</style>
