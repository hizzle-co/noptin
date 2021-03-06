<?php
/**
 * Class Noptin_New_Post_Notify class.
 */

defined( 'ABSPATH' ) || exit;

/**
 * New posts notifications handler.
 */
class Noptin_New_Post_Notify {

	/**
	 *  Constructor function.
	 */
	function __construct() {}

	/**
	 *  Inits hooks
	 */
	function init() {

		// Default automation data.
		add_filter( 'noptin_email_automation_setup_data', array( $this, 'default_automation_data' ) );

		// Set up cb.
		add_filter( 'noptin_email_automation_triggers', array( $this, 'register_automation_settings' ) );

		// Notify subscribers.
		add_action( 'transition_post_status', array( $this, 'maybe_schedule_notification' ), 10, 3 );
		add_action( 'noptin_new_post_notification', array( $this, 'maybe_send_notification' ), 10, 2 );
		add_action( 'publish_noptin-campaign', array( $this, 'maybe_send_old_notification' ) );
		add_action( 'noptin_background_mailer_complete', array( $this, 'notification_complete' ) );

		// Automation details.
		add_filter( 'noptin_automation_table_about', array( $this, 'about_automation' ), 10, 3 );

		// Display a help text below the email body.
		add_action( 'noptin_automation_campaign_after_email_body', array( $this, 'show_help_text' ), 10, 2 );

		// Allow sending a test email for new post notifications.
		add_action( 'noptin_test_email_data', array( $this, 'filter_test_email_data' ), 10, 2 );
	}

	/**
	 * Filters default automation data.
	 *
	 * @param array $data The automation data.
	 */
	public function default_automation_data( $data ) {

		if ( 'post_notifications' === $data['automation_type'] ) {
			$data['email_body']   = noptin_ob_get_clean( locate_noptin_template( 'default-new-post-notification-body.php' ) );
			$data['subject']      = '[[post_title]]';
			$data['preview_text'] = __( 'New article published on [[blog_name]]', 'newsletter-optin-box' );
		}
		return $data;

	}

	/**
	 * Filters default automation data.
	 *
	 * @param array $data The automation data.
	 */
	public function show_help_text( $campaign, $automation_type ) {

		if ( 'post_notifications' === $automation_type ) {

			$url = add_query_arg(
				array(
					'utm_medium'   => 'plugin-dashboard',
					'utm_campaign' => 'new-post-notifications',
					'utm_source'   => esc_url( get_home_url() ),
				),
				'https://noptin.com/guide/email-automations/new-post-notifications/'
			);

			$help_text = sprintf(
				__( 'Learn more about %show to set up new post notifications%s.', 'newsletter-optin-box' ),
				"<a href='$url'>",
				'</a>'
			);

			echo "<p class='description'>$help_text</p>";
			echo '<input type="hidden" name="noptin_is_new_post_notification" value="1" />';

		}

	}

	/**
	 * Filters default automation data.
	 *
	 * @param $array $triggers Registered triggers.
	 */
	public function register_automation_settings( array $triggers ) {

		if ( isset( $triggers['post_notifications'] ) ) {
			$cb = array( $this, 'render_automation_settings' );
			$cb = apply_filters( 'noptin_post_notifications_setup_cb', $cb );
			$triggers['post_notifications']['setup_cb'] = $cb;
		}

		return $triggers;
	}

	/**
	 * Filters default automation data
	 */
	public function render_automation_settings( $campaign ) {

		$url = add_query_arg(
			array(
				'utm_medium'   => 'plugin-dashboard',
				'utm_campaign' => 'new-post-notifications',
				'utm_source'   => esc_url( get_home_url() ),
			),
			'https://noptin.com/product/ultimate-addons-pack'
		);

		echo '<p class="description">' . __( 'By default, this notification will be sent every time a new blog post is published.', 'newsletter-optin-box' ) . '</p>';

		echo "<div style='margin-top: 16px; font-size: 15px;'>";
		printf(
			__( 'Install the %s to send notifications for products and other post types or limit notifications to certain categories and tags.', 'newsletter-optin-box' ),
			"<a href='$url' target='_blank'>Ultimate Addons Pack</a>"
		);
		echo '</div>';

	}

	/**
	 * Notify subscribers when new content is published
	 */
	public function maybe_schedule_notification( $new_status, $old_status, $post ) {

		// Ensure the post is published.
		if ( 'publish' === $old_status || 'publish' !== $new_status ) {
			return;
		}

		// If a notification has already been send abort...
		if ( get_post_meta( $post->ID, 'noptin_associated_new_post_notification_campaign', true ) ) {
			return;
		}

		// Are there any new post automations.
		$automations = $this->get_automations();
		if ( empty( $automations ) ) {
			return;
		}

		foreach ( $automations as $automation ) {

			// Check if the automation applies here.
			if ( $this->is_automation_valid_for( $automation, $post ) ) {
				$this->schedule_notification( $post, $automation );
			}

		}

	}

	/**
	 * Returns an array of all published new post notifications
	 */
	public function get_automations() {

		$args = array(
			'numberposts' => -1,
			'post_type'   => 'noptin-campaign',
			'meta_query'  => array(
				array(
					'key'   => 'campaign_type',
					'value' => 'automation',
				),
				array(
					'key'   => 'automation_type',
					'value' => 'post_notifications',
				),
			),
		);
		return get_posts( $args );

	}

	/**
	 * Checks if a given notification is valid for a given post
	 */
	public function is_automation_valid_for( $automation, $post ) {

		$allowed_post_types = apply_filters( 'noptin_new_post_notification_allowed_post_types', array( 'post' ), $automation );

		if ( ! in_array( $post->post_type, $allowed_post_types, true ) ) {
			return false;
		}

		return apply_filters( 'noptin_new_post_notification_valid_for_post', true, $automation, $post );

	}

	/**
	 * Notifies subscribers of a new post
	 */
	public function schedule_notification( $post, $automation ) {

		$sends_after      = (int) get_post_meta( $automation->ID, 'noptin_sends_after', true );
		$sends_after_unit = get_post_meta( $automation->ID, 'noptin_sends_after_unit', true );

		if ( ! empty( $sends_after ) ) {

			$sends_after_unit = empty( $sends_after_unit ) ? 'minutes' : $sends_after_unit;
			$timestamp        = strtotime( "+ $sends_after $sends_after_unit", current_time( 'timestamp', true ) );
			return schedule_noptin_background_action( $timestamp, 'noptin_new_post_notification', $post->ID, $automation->ID );

		}

		return do_noptin_background_action( 'noptin_new_post_notification', $post->ID, $automation->ID );

	}

	/**
	 * (Maybe) Send out a new post notification
	 */
	public function maybe_send_notification( $post_id, $campaign_id, $key = '' ) {

		// If a notification has already been send abort...
		if ( get_post_meta( $post_id, 'noptin_associated_new_post_notification_campaign', true ) ) {
			return;
		}

		if ( empty( $key ) ) {
			$key = $post_id . '_' . $campaign_id;
		}

		$this->notify( $post_id, $campaign_id, $key );

	}

	/**
	 * (Maybe) Send out a new post notification.
	 *
	 * Handles those emails that were scheduled in older versions of the plugin.
	 */
	public function maybe_send_old_notification( $key ) {

		// Is it a bg_email?
		if ( 'bg_email' !== get_post_meta( $key, 'campaign_type', true ) ) {
			return;
		}

		// Ensure this is a new post notification.
		if ( 'new_post_notification' !== get_post_meta( $key, 'bg_email_type', true ) ) {
			return;
		}

		$campaign_id = get_post_meta( $key, 'associated_campaign', true );
		$post_id     = get_post_meta( $key, 'associated_post', true );

		$this->maybe_send_notification( $post_id, $campaign_id, $key );

	}

	/**
	 * Add post data to new post notification test email.
	 */
	public function filter_test_email_data( $data ) {

		if ( ! empty( $data['noptin_is_new_post_notification'] ) ) {
			$posts = get_posts('numberposts=1');

			if ( ! empty( $posts ) ) {
				$data['merge_tags'] = array_merge( $data['merge_tags'], $this->get_post_merge_tags( $posts[0] ) );
			}
		}

		return $data;

	}

	/**
	 * Retrieves merge tags for a given post.
	 * 
	 * @param WP_Post $post
	 */
	protected function get_post_merge_tags( $post ) {

		if ( empty( $post ) ) {
			return array();
		}

		$tags = $post->filter( 'display' )->to_array();

		// Prevent wp_rss_aggregator from appending the feed name to excerpts.
		$wp_rss_aggregator_fix = has_filter( 'get_the_excerpt', 'mdwp_MarkdownPost' );

		if ( false !== $wp_rss_aggregator_fix ) {
			remove_filter( 'get_the_excerpt', 'mdwp_MarkdownPost', $wp_rss_aggregator_fix );
		}

		add_filter( 'excerpt_more', array( $this, 'excerpt_more' ), 100000 );
		$tags['post_excerpt'] = get_the_excerpt( $post->ID );
		remove_filter( 'excerpt_more', array( $this, 'excerpt_more' ), 100000 );

		if ( false !== $wp_rss_aggregator_fix ) {
			add_filter( 'get_the_excerpt', 'mdwp_MarkdownPost', $wp_rss_aggregator_fix );
		}

		$tags['excerpt']        = $tags['post_excerpt'];
		$tags['post_content']   = apply_filters( 'the_content', $post->post_content );
		$tags['content']        = $tags['post_content'];
		$tags['post_title']     = get_the_title( $post->ID);
		$tags['title']          = $tags['post_title'];
		$tags['featured_image'] = get_the_post_thumbnail( $post );

		$author = get_userdata( $tags['post_author'] );

		// Author details.
		$tags['post_author']       = $author->display_name;
		$tags['post_author_email'] = $author->user_email;
		$tags['post_author_login'] = $author->user_login;
		$tags['post_author_id']    = $author->ID;

		// Date.
		$tags['post_date'] = get_the_date( '', $post->ID );

		// Link.
		$tags['post_url'] = get_the_permalink( $post->ID );

		unset( $tags['ID'] );
		$tags['post_id'] = $post->ID;

		// Read more button.
		$tags['read_more_button']  = $this->read_more_button( $tags['post_url'] );
		$tags['/read_more_button'] = '</a></div>';

		return $tags;
	}

	/**
	 * Send out a new post notification
	 */
	protected function notify( $post_id, $campaign_id, $key ) {

		// Ensure that both the campaign and post are published.
		if ( 'publish' !== get_post_status( $post_id ) || 'publish' !== get_post_status( $campaign_id ) ) {
			return;
		}

		update_post_meta( $post_id, 'noptin_associated_new_post_notification_campaign', $campaign_id );

		$noptin   = noptin();
		$campaign = get_post( $campaign_id );
		
		$item = array(
			'campaign_id'       => $campaign_id,
			'associated_post'   => $post_id,
			'subscribers_query' => array(),
			'key'               => $key,
			'automation_type'   => 'post_notifications',
			'campaign_type'     => 'automation',
			'campaign_data'     => array(
				'email_body'    => wp_kses_post( stripslashes_deep( $campaign->post_content ) ),
				'email_subject' => sanitize_text_field( stripslashes_deep( get_post_meta( $campaign_id, 'subject', true ) ) ),
				'preview_text'  => sanitize_text_field( stripslashes_deep( get_post_meta( $campaign_id, 'preview_text', true ) ) ),
				'merge_tags'    => $this->get_post_merge_tags( get_post( $post_id ) ),
			),
		);

		$content  = get_post_meta( $post_id, 'noptin_post_notify_content', true );
		if ( ! empty( $content ) ) {
			$item['campaign_data']['email_body'] = wp_kses_post( stripslashes_deep( $content ) );
		}

		$subject = get_post_meta( $post_id, 'noptin_post_notify_subject', true );
		if ( ! empty( $subject ) ) {
			$item['campaign_data']['email_subject'] = sanitize_text_field( stripslashes_deep( $subject ) );
		}

		$preview = get_post_meta( $post_id, 'noptin_post_notify_preview_text', true );
		if ( ! empty( $preview ) ) {
			$item['campaign_data']['preview_text'] = sanitize_text_field( stripslashes_deep( $preview ) );
		}

		$item = apply_filters( 'noptin_mailer_new_post_automation_details', $item, $post_id, $campaign_id );

		if ( apply_filters( 'noptin_should_send_new_post_notification', true, $item ) ) {
			$noptin->bg_mailer->push_to_queue( $item );
			$noptin->bg_mailer->save()->dispatch();
		}

	}

	/**
	 * Generates read more button markup
	 */
	public function read_more_button( $url ) {
		$url = esc_url( $url );
		return "<div style='text-align: left; padding: 20px;' align='left'> <a href='$url' class='noptin-round' style='background: #1a82e2; display: inline-block; padding: 16px 36px; font-size: 16px; color: #ffffff; text-decoration: none; border-radius: 6px;'>";
	}

	/**
	 * Removes the read more link in an excerpt
	 */
	public function excerpt_more() {
		return '';
	}

	/**
	 * Runs after a post notification has been sent
	 */
	public function notification_complete( $item ) {
		global $wpdb;

		if ( isset( $item['automation_type'] ) && 'post_notifications' === $item['automation_type'] && isset( $item['key'] ) ) {
			$wpdb->delete(
				get_noptin_subscribers_meta_table_name(),
				array( 'meta_key' => '_campaign_' . $item['key'] ),
				'%s'
			);
		}

		if ( ! is_array( $item ) || empty( $item['campaign_id'] ) || empty( $item['associated_post'] ) ) {
			return;
		}

		$post_ids = get_posts(
			array(
				'fields'      => 'ids',
				'numberposts' => -1,
				'post_type'   => 'noptin-campaign',
				'meta_query'  => array(
					array(
						'key'   => 'campaign_type',
						'value' => 'bg_email',
					),
					array(
						'key'   => 'bg_email_type',
						'value' => 'new_post_notification',
					),
					array(
						'key'   => 'associated_campaign',
						'value' => $item['campaign_id'],
					),
					array(
						'key'   => 'associated_post',
						'value' => $item['associated_post'],
					),
				),
			)
		);

		if ( is_array( $post_ids ) ) {
			foreach ( $post_ids as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}

	}

	/**
	 * Filters an automation's details
	 */
	public function about_automation( $about, $type, $automation ) {

		if ( 'post_notifications' !== $type ) {
			return $about;
		}

		$delay = 'immeadiately';

		$sends_after      = (int) get_post_meta( $automation->ID, 'noptin_sends_after', true );
		$sends_after_unit = sanitize_text_field( get_post_meta( $automation->ID, 'noptin_sends_after_unit', true ) );

		if ( $sends_after ) {
			$delay = "$sends_after $sends_after_unit after";
		}

		return sprintf(
				__( "Sends %s new content is published", 'newsletter-optin-box' ),
				"<em style='color: #607D8B;'>$delay</em>"
	 	);
	}

}
