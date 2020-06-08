<p class="description"><?php _e( 'Tip: To edit a custom field such as a phone number or company name, first add it to an opt-in form and it will appear hear.', 'newsletter-optin-box' );  ?></p>

<?php $fields = get_special_noptin_form_fields(); ?>

<?php if ( empty( $fields ) ) { ?>
    <div style="border-left: 2px solid #b71c1c;padding-left: 16px;">
        <p style="font-size: 15px; color: #d32f2f;"><?php _e( 'You have not set up any custom fields.', 'newsletter-optin-box' );  ?></p>
    </div>
<?php return; } ?>


<table class="form-table">
    <tbody>

        <?php
            foreach ( $fields as $name => $field ) {
                $id    = esc_attr( sanitize_html_class( $name ) );
                $type  = esc_attr( $field[0] );
                $label = wp_kses_post( $field[1] );
                $value = $subscriber->get( $name );

                if ( has_action( "noptin_single_subscriber_render_custom_{$type}_field" ) ) {
                    do_action( "noptin_single_subscriber_render_custom_{$type}_field", $name, $label, $subscriber );
                    continue;
                }

                $dir = plugin_dir_path( __FILE__ );

                if ( file_exists( "$dir/$type.php" ) ) {
                    include "$dir/$type.php";
                } else {
                    include "$dir/text.php";
                }
        } ?>
    </tbody>
</table>
