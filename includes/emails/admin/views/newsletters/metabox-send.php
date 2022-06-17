<?php
    /**
     * @var Noptin_Newsletter_Email $campaign
     */

    $email_status = 'draft';
    $date_time    = '';
    $date         = current_time( 'Y-m-d' );
    $time         = current_time( 'H:i' );

    if ( is_object( $campaign ) && 'future' === $campaign->status ) {
        $email_status = 'scheduled';
        $date_time    = gmdate( 'Y-m-d H:i', strtotime( $campaign->created ) );
        $date         = gmdate( 'Y-m-d', strtotime( $campaign->created ) );
        $time         = gmdate( 'H:i', strtotime( $campaign->created ) );
    }

?>
<div class="submitbox" id="submitpost">

    <div id="misc-pub-section curtime misc-pub-curtime">

        <div class="noptin-newsletter-schedule-control" data-time-format="<?php echo esc_attr( get_option( 'time_format' ) ); ?>" data-date-format="<?php echo esc_attr( get_option( 'date_format' ) ); ?>" data-schedules="noptin_save_campaign" data-status="<?php echo esc_attr( $email_status ); ?>">
            <span id="timestamp">
                <span class="not-scheduled">Send this email <b>immediately</b></span>
                <span class="scheduled" style="display: none;">Scheduled for: <b><span class="scheduled-date"><?php echo esc_html( $date_time ); ?></span></b></span>
            </span>
            <a href="#edit_timestamp" class="edit-schedule" role="button">
                <span aria-hidden="true"><?php esc_html_e( 'Edit', 'newsletter-optin-box' ); ?></span>
                <span class="screen-reader-text"><?php esc_html_e( 'Edit date and time', 'newsletter-optin-box' ); ?></span>
            </a>

            <div class="noptin-schedule">
                <input class="noptin-schedule-input-date" type="date" value="<?php echo esc_attr( $date ); ?>" placeholder="Y-m-d">
                <span><?php esc_html_x( 'at', 'Time', 'newsletter-optin-box' ); ?></span>
                <input class="noptin-schedule-input-time" type="time" value="<?php echo esc_attr( $time ); ?>" placeholder="H:i">
                <p>
                    <a href="#edit_timestamp" class="save-timestamp button"><?php esc_html_x( 'OK', 'Accept', 'newsletter-optin-box' ); ?></a>
                    <a href="#edit_timestamp" class="cancel-timestamp button-cancel"><?php esc_html_e( 'Cancel', 'newsletter-optin-box' ); ?></a>
                </p>
            </div>

            <input type="hidden" value="<?php echo esc_attr( $date_time ); ?>" name="schedule-date" class="noptin-schedule-selected-date">
        </div>

        <input type="submit" name="publish" id="noptin_save_campaign" data-scheduled="<?php esc_attr_e( 'Schedule', 'newsletter-optin-box' ); ?>" data-not-scheduled="<?php esc_attr_e( 'Send', 'newsletter-optin-box' ); ?>" class="button button-primary button-large" value="<?php esc_attr_e( 'Send', 'newsletter-optin-box' ); ?>">
        <input type="submit" name="draft" class="button button-link button-large" value="<?php esc_attr_e( 'Save Draft', 'newsletter-optin-box' ); ?>">

    </div>
</div>
