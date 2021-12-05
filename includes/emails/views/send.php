<?php
    /**
     * @var WP_Post $campaign
     */

    $senders     = get_noptin_email_senders();
    $sender      = 'noptin';
    $status      = 'draft';
    $date_time   = '';
    $date        = date( 'Y-m-d', current_time( 'timestamp' ) );
    $time        = date( 'H:i', current_time( 'timestamp' ) );
    $date_format = esc_attr( get_option( 'date_format' ) );
    $time_format = esc_attr( get_option( 'time_format' ) );

    if ( is_object( $campaign ) ) {
        $sender = get_noptin_email_sender( $campaign->ID );
    }

    if ( is_object( $campaign ) && 'future' === $campaign->post_status ) {
        $status    = 'scheduled';
        $date_time = date( 'Y-m-d H:i', strtotime( $campaign->post_date ) );
        $date      = date( 'Y-m-d', strtotime( $campaign->post_date ) );
        $time      = date( 'H:i', strtotime( $campaign->post_date ) );
    }

?>
<div class="submitbox" id="submitpost">
    <div id="misc-pub-section curtime misc-pub-curtime">

        <div class="noptin-newsletter-schedule-control" data-time-format="<?php echo $time_format; ?>" data-date-format="<?php echo $date_format; ?>" data-schedules="noptin_save_campaign" data-status="<?php echo $status; ?>">
            <span id="timestamp">
                <span class="not-scheduled">Send this email <b>immediately</b></span>
                <span class="scheduled" style="display: none;">Scheduled for: <b><span class="scheduled-date"><?php echo $date_time; ?></span></b></span>
            </span>
            <a href="#edit_timestamp" class="edit-schedule" role="button">
                <span aria-hidden="true">Edit</span>
                <span class="screen-reader-text">Edit date and time</span>
            </a>

            <div class="noptin-schedule">
                <input class="noptin-schedule-input-date" type="date" value="<?php echo $date; ?>" placeholder="Y-m-d">
                <span>at</span>
                <input class="noptin-schedule-input-time" type="time" value="<?php echo $time; ?>" placeholder="H:i">
                <p>
                    <a href="#edit_timestamp" class="save-timestamp button">OK</a>
                    <a href="#edit_timestamp" class="cancel-timestamp button-cancel">Cancel</a>
                </p>
            </div>

            <input type="hidden" value="<?php echo $date_time; ?>" name="schedule-date" class="noptin-schedule-selected-date">
        </div>

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

        <input type="submit" name="publish" id="noptin_save_campaign" data-scheduled="Schedule" data-not-scheduled="Send" class="button button-primary button-large" value="Send">
        <input type="submit" name="draft" class="button button-link button-large" value="Save Draft">

    </div>
</div>
