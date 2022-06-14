<?php

	defined( 'ABSPATH' ) || exit;

	printf(
		/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
		esc_html__( 'There is no campaign with that id. %1$sGo back to the campaigns overview page%2$s.', 'newsletter-optin-box' ),
		'<a href="' . esc_url( remove_query_arg( array( 'sub_section', 'campaign' ) ) ) . '">',
		'</a>'
	);
