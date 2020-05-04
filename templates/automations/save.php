<div class="submitbox" id="submitpost">
    <div id="misc-pub-section curtime misc-pub-curtime" style="margin: 20px 0;">

        <label for="automation_status"><strong><?php _e( 'Automation Status', 'newsletter-optin-box' ); ?></strong></label>
        <select id="automation_status" name="status" style="width: 320px; max-width: 100%;">
            <option <?php selected( 'publish' === $campaign->post_status ) ?> value='publish'><?php _e( 'Active', 'newsletter-optin-box' ); ?></option>
            <option <?php selected('publish' !== $campaign->post_status ) ?> value='draft'><?php _e( 'In-Active', 'newsletter-optin-box' ); ?></option>
        </select>

        <div style="margin-top: 20px;">
        <input type="submit" name="save" class="button-primary" value="<?php _e( 'Save Changes', 'newsletter-optin-box' ); ?>"/>
        </div>

    </div>
</div>
