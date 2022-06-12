
<div class="noptin-subscriber-double-optin-box">
    <p><?php esc_html_e( "This will send a double opt-in confirmation email to the subscriber's email address.", 'newsletter-optin-box' );?></p>
    <p><a data-email='<?php echo esc_attr( $subscriber->email ); ?>' href="#" class="button button-secondary send-noptin-subscriber-double-optin-email"><?php esc_html_e( 'Send confirmation email', 'newsletter-optin-box' );?></a></p>  
</div>
