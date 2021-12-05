<?php
/**
 * Emails API: Emails Manager.
 *
 * Contains the main class for Noptin emails
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main class for Noptin emails.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Emails_Manager {

	/** @var Noptin_Emails_Admin */
	public $admin;

	/**
	 * Class constructor.
	 *
	 */
	public function __construct() {

		// Load files.
		include plugin_dir_path( __FILE__ ) . 'class-emails-admin.php';

		// Init props.
		$this->admin = new Noptin_Emails_Admin();

		add_action( 'plugins_loaded', array( $this, 'add_hooks' ), 7 );

	}

	/**
	 * Add hooks
	 *
	 */
	public function add_hooks() {

		add_action( 'delete_post', array( $this, 'delete_stats' ) );
		add_action( 'transition_post_status', array( $this, 'maybe_send_campaign' ), 100, 3 );

		$this->admin->add_hooks();
	}

	/**
	 * Deletes campaign stats when the campaign is deleted.
	 *
	 * @param int $post_id the campaign whose stats should be deleted.
	 */
	public function delete_stats( $post_id ) {
		global $wpdb;

		$table = get_noptin_subscribers_meta_table_name();
		$wpdb->delete(
			$table,
			array(
				'meta_key' => "_campaign_$post_id",
			)
		);

	}

	/**
	 *  (Maybe) Sends a newsletter campaign.
	 *
	 * @param string  $new_status The new campaign status.
	 * @param string  $old_status The old campaign status.
	 * @param WP_Post $post The new campaign post object.
	 */
	public function maybe_send_campaign( $new_status, $old_status, $post ) {

		// Maybe abort early.
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Ensure this is a newsletter campaign.
		if ( 'noptin-campaign' === $post->post_type && 'newsletter' === get_post_meta( $post->ID, 'campaign_type', true ) ) {
			$this->send_campaign( $post );
		}

	}

	/**
	 * Sends a newsletter campaign.
	 *
	 * @param WP_Post $post The new campaign post object.
	 */
	public function send_campaign( $post ) {

		log_noptin_message(
			sprintf(
				__( 'Sending the campaign: "%s"', 'newsletter-optin-box' ),
				esc_html( $post->post_title )
			)
		);

		$noptin = noptin();

		$item = array(
			'campaign_id'       => $post->ID,
			'subscribers_query' => array(), // By default, send this to all active subscribers.
			'custom_merge_tags' => array(),
			'campaign_data'     => array(
				'campaign_id'   => $post->ID,
				'email_body'    => $post->post_content,
				'email_subject' => $post->post_title,
				'preview_text'  => get_post_meta( $post->ID, 'preview_text', true ),
			),
		);

		foreach ( array( 'custom_merge_tags', 'subscribers_query', 'recipients' ) as $key ) {

			$meta_value = get_post_meta( $post->ID, $key, true );
			if ( ! empty( $meta_value ) ) {
				$item[ $key ] = map_deep( $meta_value, 'wp_kses_post' );
			}

		}

		if ( apply_filters( 'noptin_should_send_campaign', true, $item ) ) {

			$sender = get_noptin_email_sender( $post->ID );

			if ( 'noptin' == $sender ) {
				$noptin->bg_mailer->push_to_queue( $item );
				$noptin->bg_mailer->save()->dispatch();
			} else {
				do_action( "handle_noptin_email_sender_$sender", $item, $post );
			}

		}

	}

}
