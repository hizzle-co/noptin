<table class="form-table">
    <tbody>
        <tr class="form-field-row-email">
            <th scope="row"><label for="field_email"><?php _e( 'Email Address', 'newsletter-optin-box' ); ?></label></th>
            <td>
                <div>
                    <input type="email" class="regular-text" name="email" id="field_email" value="<?php echo esc_attr( $subscriber->email ); ?>">
                </div>
            </td>
        </tr>
        <tr class="form-field-row-first_name">
            <th scope="row"><label for="field_first_name"><?php _e( 'First name', 'newsletter-optin-box' ); ?></label></th>
            <td>
                <div>
                    <input type="text" class="regular-text" name="first_name" id="field_first_name" value="<?php echo esc_attr( $subscriber->first_name ); ?>">
                </div>
            </td>
        </tr>
        <tr class="form-field-row-last_name">
            <th scope="row"><label for="field_last_name"><?php _e( 'Last name', 'newsletter-optin-box' ); ?></label></th>
            <td>
                <div>
                    <input type="text" class="regular-text" name="last_name" id="field_last_name" value="<?php echo esc_attr( $subscriber->second_name ); ?>">
                </div>
            </td>
        </tr>
        <tr class="form-field-row-status">
            <th scope="row"><label for="field_status"><?php _e( 'Subscription Status', 'newsletter-optin-box' ); ?></label></th>
            <td>
                <div>
                    <select name="status" id="field_status" style="min-width: 25em;">
                        <option <?php selected( 0 === (int) $subscriber->active ) ?> value="0"><?php _e( 'Active', 'newsletter-optin-box' ); ?></option>
                        <option <?php selected( 0 !== (int) $subscriber->active ) ?> value="1"><?php _e( 'Inactive', 'newsletter-optin-box' ); ?></option>
                    </select>
                </div>
            </td>
        </tr>

        <tr class="form-field-row-email-status">
            <th scope="row"><label for="field_status"><?php _e( 'Email Status', 'newsletter-optin-box' ); ?></label></th>
            <td>
                <div>
                    <select name="confirmed" id="field_email_status" style="min-width: 25em;">
                        <option <?php selected( 1 === (int) $subscriber->confirmed ) ?> value="1"><?php _e( 'Confirmed', 'newsletter-optin-box' ); ?></option>
                        <option <?php selected( 1 !== (int) $subscriber->confirmed ) ?> value="0"><?php _e( 'Not Confirmed', 'newsletter-optin-box' ); ?></option>
                    </select>
                </div>
            </td>
        </tr>

        <tr class="form-field-row-key">
            <th scope="row"><label for="field_status"><?php _e( 'Confirmation Key', 'newsletter-optin-box' ); ?></label></th>
            <td>
                <div>
                    <input type="text" class="regular-text" name="confirm_key" id="field_confirm_key" value="<?php echo esc_attr( $subscriber->confirm_key ); ?>" disabled="disabled">
                </div>
            </td>
        </tr>
    </tbody>
</table>
