<h1>[[post_title]]</h1>
<p>[[post_excerpt]]</p>
<p>[[read_more_button]]<?php _e( 'Continue Reading', 'newsletter-optin-box' ); ?>[[/read_more_button]]</p>
<p><?php _e( "If that doesn't work, copy and paste the following link in your browser:", 'newsletter-optin-box' ); ?></p>
<p>[[post_url]]</p>
<p><?php

    echo sprintf( 
            __( 'Learn more about %show to set up new post notifications%s.', 'newsletter-optin-box' ),
            '<a href="https://noptin.com/guide/email-automations/new-post-notifications/">',
            '</a>'
    );

?></p>
<p>&nbsp;</p>
<p><?php _e( 'Cheers', 'newsletter-optin-box' ); ?></p>
<p>[[post_author]]</p>
