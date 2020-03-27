<?php 

$paragraphs   = array();
$paragraphs[] = sprintf( 
    __( 'Hi %s,', 'newsletter-optin-box' ),
    '[[first_name]]'
);

$paragraphs[] = sprintf( 
    __( 'This is the email body. You can use %semail tags%s to personalize your email.', 'newsletter-optin-box' ),
    '<a href="https://noptin.com/guide/sending-emails/email-tags/">',
    '</a>'
);

$paragraphs[] = __( 'The final email will include a logo and your address details. You can change these on the settings page...', 'newsletter-optin-box' );

$paragraphs[] = sprintf( 
    __( '... or read our guide on %show to use a different email templates%s.', 'newsletter-optin-box' ),
    '<a href="https://noptin.com/guide/sending-emails/changing-email-templates/">',
    '</a>'
);

$paragraphs[] = __( 'After you are done editing the email, you can send a test email below then click on the publish button to publish it.', 'newsletter-optin-box' );

$paragraphs[] = __( 'Cheers,', 'newsletter-optin-box' );

$paragraphs[] = __( 'The Noptin Team.', 'newsletter-optin-box' );

$msg          = '';

foreach ( $paragraphs as $paragraph ) {
    $msg .= "<p>$paragraph</p>\n\n";
}

return $msg;
