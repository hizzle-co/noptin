<?php

$paragraphs   = array();
$paragraphs[] = sprintf(
	/* Translators: %s Subscriber name. */
	__( 'Hi %s,', 'newsletter-optin-box' ),
	'[[first_name]]'
);

$paragraphs[] = sprintf(
	/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
	__( 'This is the email body. You can use %1$semail tags%2$s to personalize your email.', 'newsletter-optin-box' ),
	'<a href="https://noptin.com/guide/sending-emails/email-tags/">',
	'</a>'
);

$paragraphs[] = __( 'The final email will include a logo and your address details. You can change these on the settings page...', 'newsletter-optin-box' );

$paragraphs[] = sprintf(
	/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
	__( '... or read our guide on %1$show to use a different email templates%2$s.', 'newsletter-optin-box' ),
	'<a href="https://noptin.com/guide/sending-emails/changing-email-templates/">',
	'</a>'
);

$paragraphs[] = __( 'Cheers,', 'newsletter-optin-box' );

$paragraphs[] = __( 'The Noptin Team.', 'newsletter-optin-box' );

$msg          = '';

foreach ( $paragraphs as $paragraph ) {
	$msg .= "<p>$paragraph</p>\n\n";
}

return $msg;
