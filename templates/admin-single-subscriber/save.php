<?php
    $delete_url  = esc_url(
        wp_nonce_url(
            add_query_arg( 'delete-subscriber', $subscriber->id, urldecode( $_GET['return'] ) ),
            'noptin-subscriber'
        )
    );

?>
<div class="misc-pub-section curtime misc-pub-curtime">
	<span id="timestamp">
        <?php _e( 'Subscribed on:', 'newsletter-optin-box' ); ?>&nbsp;<b><?php echo esc_html( $subscriber->date_created); ?></b>
    </span>
</div>

<?php if ( 1 === (int) $subscriber->confirmed && ! empty( $subscriber->confirmed_on ) ) : ?>

    <div class="misc-pub-section misc-pub-noptin-unsubscribed-on">
        <span id="subscriber-unsubscribed-on">
            <span class="dashicons dashicons-controls-pause" style="padding-right: 3px; color: #607d8b"></span>
            <?php _e( 'Confirmed On:', 'newsletter-optin-box' ); ?>&nbsp;<b><?php echo esc_html( $subscriber->confirmed_on ); ?></b>
        </span>
    </div>

<?php endif; ?>

<?php if ( 0 !== (int) $subscriber->active && ! empty( $subscriber->unsubscribed_on ) ) : ?>

<div class="misc-pub-section misc-pub-noptin-unsubscribed-on">
    <span id="subscriber-unsubscribed-on">
        <span class="dashicons dashicons-controls-pause" style="padding-right: 3px; color: #607d8b"></span>
        <?php _e( 'Unsubscribed On:', 'newsletter-optin-box' ); ?>&nbsp;<b><?php echo esc_html( $subscriber->unsubscribed_on ); ?></b>
    </span>
</div>

<?php endif; ?>

<div class="misc-pub-section misc-pub-noptin-subscriber-id">
	<span id="subscriber-id">
        <span class="dashicons dashicons-admin-users" style="padding-right: 3px; color: #607d8b"></span>
        <?php _e( 'Subscriber ID:', 'newsletter-optin-box' ); ?>&nbsp;<b><?php echo esc_html( $subscriber->id); ?></b>
    </span>
</div>

<?php if ( $subscriber->is_wp_user() ) { ?>
    <div class="misc-pub-section misc-pub-noptin-subscriber-wp-user">
	    <span id="subscriber-wp-user">
            <span class="dashicons dashicons-yes" style="padding-right: 3px;color: #607d8b"></span>
            <a href="<?php echo esc_url( add_query_arg( 'user_id', $subscriber->get_wp_user(), self_admin_url( 'user-edit.php' ) ) ); ?>"><?php _e( 'View WordPress profile', 'newsletter-optin-box' );?></a>
        </span>
    </div>
<?php } else { ?>

<div class="misc-pub-section misc-pub-noptin-subscriber-wp-user">
	<span id="subscriber-wp-user">
        <span class="dashicons dashicons-no" style="padding-right: 3px;color: #607d8b"></span>
        <?php _e( 'Not a registered user', 'newsletter-optin-box' );?>
    </span>
</div>
<?php } ?>


<div class="misc-pub-section misc-pub-noptin-subscriber-gdpr-consent">
	<span id="gdpr-consent">
        <span class="dashicons dashicons-email" style="padding-right: 3px; color: #607d8b;"></span>
        <?php 
            _e( 'GDPR Consent:', 'newsletter-optin-box' );
            $consent = $subscriber->get( 'GDPR_consent' );

            // Yes/No etc.
            if ( ! empty( $consent ) && ! is_numeric( $consent ) ) {
                $consent = sanitize_text_field( $consent );
            } else if ( ! empty( $consent ) ) {
                $consent = "<span style='color: #2e7d32;' class='dashicons dashicons-yes'></span>";
            } else {
                $consent = '<span style="color: #f44336;" class="dashicons dashicons-no"></span>';
            }
        ?>
        &nbsp;<b><?php echo $consent; ?></b>
    </span>
</div>

<?php if ( $subscriber->ip_address ) { ?>
    <div class="misc-pub-section misc-pub-noptin-subscriber-ip-address">
	    <span id="subscriber-ip-address">
            <span class="dashicons dashicons-admin-site" style="padding-right: 3px;color: #607d8b"></span>
            <?php _e( 'IP Address', 'newsletter-optin-box' );?>&nbsp;<b><?php echo esc_html( $subscriber->ip_address ); ?></b>
        </span>
    </div>
<?php }?>

<?php if ( $subscriber->_subscriber_via ) { ?>
    <div class="misc-pub-section misc-pub-noptin-subscriber-subscribed-via">
	    <span id="subscriber-subscribed-via">
            <span class="dashicons dashicons-art" style="padding-right: 3px;color: #607d8b"></span>
            <?php _e( 'Subscribed Via', 'newsletter-optin-box' );?>
            <b>
                <?php
                    $source = $subscriber->_subscriber_via;

                    if ( is_numeric( $source ) ) {
						$form  = noptin_get_optin_form( $source );
                        $url   = get_noptin_edit_form_url( $source );
                        
                        if ( empty( $form->id ) ) {

                            $source = absint( $source );

                        } else {

                            $source = sprintf(
                                '<a href="%s">%s</a>',
                                esc_url( $url ),
                                esc_html( $form->optinName )
                            );

                        }
						
                    } else {
                        $source = esc_html( $source );
                    }

                    echo $source;
                ?>
            </b>
        </span>
    </div>
<?php }?>

<?php if ( $subscriber->conversion_page ) { ?>
    <div class="misc-pub-section misc-pub-noptin-subscriber-conversion-page">
	    <span id="subscriber-conversion-page">
            <span class="dashicons dashicons-admin-links" style="padding-right: 3px;color: #607d8b"></span>
            <?php _e( 'Conversion Page', 'newsletter-optin-box' );?>&nbsp;
            <b>
                <?php

                    $url = $subscriber->conversion_page;
                    if( ! empty( $url ) ) {
                        $url = esc_url( $url );
                        echo "<a style='display: block; max-height: 20px; overflow: hidden; font-weight: 400;' target='_blank' href='$url' title='$url'><small>$url</small></a>";
                    } else {
                        echo __( 'Unknown', 'newsletter-optin-box' );
                    }

                ?>
            </b>
        </span>
    </div>
<?php }?>

<div id="major-publishing-actions" style="margin: 10px -12px -12px;">

	<div id="delete-action">
	    <a class='noptin-delete-single-subscriber' data-email='<?php echo esc_attr( $subscriber->email ); ?>' style="color: #a00;" href="<?php echo $delete_url ?>"><?php _e( 'Delete', 'newsletter-optin-box' ); ?></a>
	</div>

    <div id="publishing-action">
		<input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Update', 'newsletter-optin-box' ); ?>">
	</div>
    <div class="clear"></div>
</div>
