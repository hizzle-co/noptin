<?php

	defined( 'ABSPATH' ) || exit;

	printf(
		'<div class="wrap"><div class="notice notice-error"><p>%s</p></div></div>',
		sprintf(
			/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
			esc_html__( 'You do not have permission to edit this campaign. %1$sGo back to the campaigns overview page%2$s.', 'newsletter-optin-box' ),
			'<a href="' . esc_url( remove_query_arg( array( 'noptin_campaign' ) ) ) . '">',
			'</a>'
		)
	);
