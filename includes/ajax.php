<?php
/**
 * Ajax handlers go here
 *
 *
 * @since 1.0.5
 *
 */

/**
 * Handles ajax requests to add new email subscribers
 *
 * @since       1.0.5
 * @return      void
 */
function noptin_add_ajax_subscriber() {
    global $wpdb;

    // Check nonce
    $nonce = $_POST['noptin_subscribe'];
    if ( ! wp_verify_nonce( $nonce, 'noptin-subscribe-nonce' )) {
        wp_send_json( array(
            'result' => '0',
            'msg'    => esc_html__('Error: Please reload the page and try again.', 'noptin'),
        ));
        exit;
    }

    //Check email address
    $email = sanitize_email($_POST['email']);
    if ( empty($email) || !is_email($email)) {
        wp_send_json( array(
            'result' => '0',
            'msg'    => esc_html__('Error: Please provide a valid email address.', 'noptin'),
        ));
        exit;
    }

    do_action('noptin_before_add_ajax_subscriber');

    //Add the user to the database 
    $table = $wpdb->prefix . 'noptin_subscribers';
    $key   = $wpdb->prepare("(%s)", md5($email));
    $email = $wpdb->prepare("(%s)", $email);

    $wpdb->query("INSERT IGNORE INTO $table (email, confirm_key)
        VALUES ($email, $key)");

    do_action('noptin_after_after_ajax_subscriber');

    //We made it
    wp_send_json( array(
        'result' => '1',
        'msg'    => esc_html__('Success!', 'noptin'),
    ));
    exit;
}
add_action( 'wp_ajax_noptin_new_user', 'noptin_add_ajax_subscriber' );
add_action( 'wp_ajax_nopriv_noptin_new_user', 'noptin_add_ajax_subscriber' );
