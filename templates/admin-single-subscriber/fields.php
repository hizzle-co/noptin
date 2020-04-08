<p class="description"><?php _e( 'Tip: To edit a custom field such as a phone number or company name, first add it to an opt-in form and it will appear hear.', 'newsletter-optin-box' );  ?></p>
<?php

    $forms = get_posts( array(
        'numberposts' => -1,
        'post_status' => array( 'draft', 'publish' ),
        'post_type'   => 'noptin-form',
        'fields'      => 'ids',
    ) );

    $fields = array();

    $to_ignore = array(
        'email',
        'first_name',
        'last_name',
        'name',
        'GDPR_consent'
    );

    foreach ( $forms as $form ) {

        // Retrieve steps.
        $state = get_post_meta( $form, '_noptin_state', true );
		if ( ! is_array( $state ) ) {
			continue;
        }

        if ( empty( $state['fields'] ) ||  ! is_array( $state['fields'] ) ) {
			continue;
        }

        foreach ( $state['fields'] as $field ) {
            $name  = $field['type']['name'];
            $type  = $field['type']['type'];
            $label = $field['type']['label'];

            if ( in_array( $name, $to_ignore ) || in_array( $type, $to_ignore ) ) {
                continue;
            }

            $fields[ $name ] = array(
                $type,
                $label
            );
        }

    }

?>

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
                    do_action( "noptin_single_subscriber_render_custom_{$type}_field" );
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