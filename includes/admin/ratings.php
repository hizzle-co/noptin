<?php
/**
 * Helps us get more ratings
 *
 *
 * @since             1.0.0
 *
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    die;
}

//When a new subscriber registers, check count. Trigger on 1, 10,100,1000
//Leave a review, maybe later, i already did
add_action('noptin_after_after_ajax_subscriber', 'noptin_maybe_trigger_value_rating');
function noptin_maybe_trigger_value_rating() {

    global $wpdb;

    //Do not show update nag
    update_option('noptin_show_update_nag', 'no');

    $status = get_option('noptin_review_status', 'later');
    if ('did' == $status) {
        return;
    }

    $table = $wpdb->prefix . 'noptin_subscribers';
    $count = $wpdb->get_var( "SELECT COUNT(email) FROM $table" );
    $msg   = 'Congratulations on your first %s subscribers – that’s awesome! Your next target is %s. 
    You can do it and we are glad to be helping. 
    If you have 5 minutes, could you please do us a BIG favor and give the plugin a 5-star rating on WordPress? Just to help us spread the word and boost our motivation.';

    if($count > 1000){

        //If the user has already reacted to this nag, abort
        if($status == 1000){
            return;
        }
        update_option('noptin_status_will_update_to', 1000);
        $msg = sprintf($msg, '1000', '10,000');

    } else if($count > 100){

        if($status == 100){
            return;
        }
        update_option('noptin_status_will_update_to', 100);
        $msg = sprintf($msg, '100', '1000');

    } else if($count > 10){

        if($status == 10){
            return;
        }
        update_option('noptin_status_will_update_to', 10);
        $msg = sprintf($msg, '10', '100');

    } else {

        if($status == 1){
            return;
        }
        update_option('noptin_status_will_update_to', 1);
        $msg   = 'Congratulations on your first subscriber – that’s awesome! 
        Your next target is 10. You can do it and we are glad to be helping. 
        If you have 5 minutes, could you please do us a BIG favor and give the plugin a 5-star rating on WordPress? Just to help us spread the word and boost our motivation.';
    
    }

    //If we are here, we should display an update nag
    update_option('noptin_show_update_nag', 'yes');
    update_option('noptin_update_nag_msg', $msg);
}


add_action('admin_notices', 'noptin_maybe_show_rating_msg');
function noptin_maybe_show_rating_msg() {
    
    if(get_option( 'noptin_show_update_nag', 'no' ) != 'yes'){
        return;
    }

    //Print the nag
    $class = 'notice notice-success is-dismissible';
    $message = get_option('noptin_update_nag_msg', '');
    $link = '
        <ul class="noptin-nag">
            <li class="noptin-nag-item"><a href="https://wordpress.org/support/plugin/newsletter-optin-box/reviews/?filter=5" class="button button-primary">Leave a review</a></li>
            <li class="noptin-nag-item"><a href="' . add_query_arg( 'noptin_rate_status', 'later') .'" class="button">Maybe Later</a></li>
            <li class="noptin-nag-item"><a href="' . add_query_arg( 'noptin_rate_status', 'did') .'" class="button">I already did</a></li>
        </ul>';

    printf( '<div class="%1$s"><p>%2$s</p>%3$s</div>', esc_attr( $class ), esc_html( $message ), $link ); 
}


add_action('admin_init', 'noptin_update_status');
function noptin_update_status(){

    if(! isset($_GET['noptin_rate_status'])){
        return;
    }

    update_option('noptin_show_update_nag', 'no');
    $status = $_GET['noptin_rate_status'];
    if( 'did' == $status){
        update_option('noptin_review_status', 'did');
        return;
    }

    if( 'later' == $status){
        $update_to = get_option( 'noptin_status_will_update_to', 1 );
        update_option('noptin_review_status', $update_to);
        return;
    }
}