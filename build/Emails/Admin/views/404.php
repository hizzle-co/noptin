<?php

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var \Hizzle\Noptin\Emails\Email $edited_campaign
	 * @var array $query_args
	 */

	// If we expect a parent ID, check if it exists.
	$back_url = remove_query_arg( array( 'noptin_campaign' ) );
	if ( ! empty( $query_args['noptin_email_type'] ) && empty( $query_args['noptin_campaign'] ) ) {
		$email_type = \Hizzle\Noptin\Emails\Main::get_email_type( sanitize_text_field( $query_args['noptin_email_type'] ) );

		if ( $email_type && $email_type->parent_type ) {
			$back_url = add_query_arg(
				array(
					'page'              => 'noptin-email-campaigns',
					'noptin_email_type' => rawurlencode( $email_type->parent_type ),
				),
				admin_url( '/admin.php' )
			);
		}
	}

	printf(
		'<div class="wrap"><div class="notice notice-error"><p>%s</p></div></div>',
		sprintf(
			/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
			esc_html__( 'There is no campaign with that id. %1$sGo back to the campaigns overview page%2$s.', 'newsletter-optin-box' ),
			'<a href="' . esc_url( $back_url ) . '">',
			'</a>'
		)
	);
