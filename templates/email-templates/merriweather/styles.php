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

	h1,
	h2,
	h3,
	h4,
	h5,
	h6 {
		font-family: 'Merriweather Bold', serif;
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

	img, figure {
		height: auto;
		line-height: 100%;
		text-decoration: none;
		border: 0;
		outline: none;
		max-width: 100%;
	}

	p, ul, ol, h1, h2, h3, h4, h5, h6  {
		margin: 1em 0;
	}

	.footer p{
		margin: 0;
		padding: 0;
	}

	.cta-link {
		display: inline-block !important;
	}

</style>
