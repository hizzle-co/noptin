<?php

    $senders = get_noptin_email_senders();
    $sender  = get_noptin_email_sender( $campaign->ID );

?>
<div class="submitbox" id="submitpost">
    <div id="misc-pub-section curtime misc-pub-curtime" style="margin: 20px 0;">

        <label for="automation_status"><strong><?php _e( 'Automation Status', 'newsletter-optin-box' ); ?></strong></label>
        <select id="automation_status" name="status" style="width: 320px; max-width: 100%;">
            <option <?php selected( 'publish' === $campaign->post_status ) ?> value='publish'><?php _e( 'Active', 'newsletter-optin-box' ); ?></option>
            <option <?php selected('publish' !== $campaign->post_status ) ?> value='draft'><?php _e( 'In-Active', 'newsletter-optin-box' ); ?></option>
        </select>

        <?php if ( 'post_notifications' === get_post_meta( $campaign->ID, 'automation_type', true ) ) : ?>
            <div class="noptin-select-email-sender senders-<?php echo count( $senders ); ?>">
                <label style="display:<?php echo 1 < count( $senders ) ? 'block' : 'none'; ?>; width:100%;" class="noptin-margin-y noptin-email-senders-label">
                    <strong><?php _e( 'Send To', 'newsletter-optin-box' ); ?></strong>
                    <select name="email_sender" class="noptin-email_sender" style="display:block; width:100%;">
                        <?php foreach ( $senders as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $sender ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php foreach ( array_keys( $senders ) as $_sender ) : ?>
                <div class="noptin-sender-options noptin-margin-y sender-<?php echo esc_attr( $_sender ); ?>" style="display:<?php echo $_sender == $sender ? 'block' : 'none'; ?>;">
                    <?php echo do_action( "noptin_sender_options_$_sender", $campaign ); ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px;">
        <input type="submit" name="save" class="button-primary" value="<?php _e( 'Save Changes', 'newsletter-optin-box' ); ?>"/>
        </div>

    </div>
</div>
