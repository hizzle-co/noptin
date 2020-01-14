<?php
/**
 * class Noptin_New_Post_Notify class.
 *
 */

defined( 'ABSPATH' ) || exit;

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
		add_action('transition_post_status', array( $this, 'maybe_schedule_notification' ), 10, 3 );
		add_action('publish_noptin-campaign', array( $this, 'maybe_send_notification' ) );
		add_action('noptin_background_mailer_complete', array( $this, 'notification_complete' ) );

		// Automation details.
		add_filter( 'noptin_automation_table_about', array( $this, 'about_automation' ), 10, 3 );


	}

	/**
	 * Filters default automation data
	 */
	public function default_automation_data( $data ) {

		if( 'post_notifications' == $data['automation_type'] ) {
			$data[ 'email_body' ]   = noptin_ob_get_clean( get_noptin_include_dir( 'admin/templates/default-new-post-notification-body.php' ) );
			$data[ 'subject' ]      = '[[post_title]]';
			$data[ 'preview_text' ] = __( 'New article published on [[blog_name]]' );
		}
		return $data;

	}

	/**
	 * Filters default automation data
	 */
	public function register_automation_settings( array $triggers ) {

		if( isset( $triggers['post_notifications'] ) ) {
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

		?>
		<tr>

			<th>
				<label><b><?php _e( 'Post Types', 'newsletter-optin-box' ); ?></b></label>
			</th>

			<td>
				<?php $text = __( 'Blog posts', 'newsletter-optin-box' ); ?>
				<p class="description"><?php echo $text; ?> &mdash; <a href="#" class="noptin-filter-post-notifications-post-types">Change</a></p>
			</td>

		</tr>

		<tr>

			<th>
				<label><b><?php _e( 'Terms', 'newsletter-optin-box' ); ?></b></label>
			</th>

			<td>
				<?php $text = __( 'All terms', 'newsletter-optin-box' ); ?>
				<p class="description"><?php echo $text; ?> &mdash; <a href="#" class="noptin-filter-post-notifications-taxonomies">Change</a></p>
			</td>

		</tr>
		<?php

	}

	/**
	 * Notify subscribers when new content is published
	 */
	public function maybe_schedule_notification( $new_status, $old_status, $post ) {

		// Ensure the post is published.
		if( 'publish' != $new_status ) {
			return;
		}

		// If a notification has already been send abort...
		if( get_post_meta( $post->ID, 'noptin_associated_new_post_notification_campaign', true ) ) {
			return;
		}

		// Are there any new post automations.
		$automations = $this->get_automations();
		if( empty( $automations ) ) {
			return;
		}

		foreach( $automations as $automation ) {

			// Check if the automation applies here.
			if( $this->is_automation_valid_for( $automation, $post ) ) {
				$this->schedule_notification( $post, $automation );
			}
		}

	}

	/**
	 * Returns an array of all published new post notifications
	 */
	public function get_automations() {

		$args = array(
			'numberposts'	=> -1,
			'post_type'		=> 'noptin-campaign',
			'meta_query'    => array(
				array(
					'key'   => 'campaign_type',
					'value' => 'automation',
				),
				array(
					'key'   => 'automation_type',
					'value' => 'post_notifications',
				)
			)
		);
		return get_posts( $args );

	}

	/**
	 * Checks if a given notification is valid for a given post
	 */
	public function is_automation_valid_for( $automation, $post ) {

		$allowed_post_types = apply_filters( 'noptin_new_post_notification_allowed_post_types', array( 'post' ), $automation );

		if( ! in_array( $post->post_type, $allowed_post_types ) ) {
			return false;
		}

		return apply_filters( 'noptin_new_post_notification_valid_for_post', true, $automation, $post );

	}

	/**
	 * Notifies subscribers of a new post
	 */
	public function schedule_notification( $post, $automation ) {

		// Prepare post args.
		$post_args = array(
			'post_status'      => 'publish',
			'post_type'        => 'noptin-campaign',
			'post_date_gmt'    => current_time( 'mysql', true ),
			'post_date'        => current_time( 'mysql' ),
			'edit_date'        => 'true',
			'post_title'	   => 'noptin_post_notification_schedule_' . wp_generate_password( 32, false ),
			'meta_input'	   => array(
				'campaign_type'           => 'bg_email',
				'bg_email_type'           => 'new_post_notification',
				'associated_campaign'     => $automation->ID,
				'associated_post'      	  => $post->ID
			),
		);

		$sends_after      = (int) get_post_meta( $automation->ID, 'noptin_sends_after', true );
		$sends_after_unit = get_post_meta( $automation->ID, 'noptin_sends_after_unit', true );

		if( ! empty( $sends_after ) ) {

			$sends_after_unit = empty( $sends_after_unit ) ? 'minutes' : $sends_after_unit;
			$time       	  = current_time( 'mysql' );
			$time_gmt   	  = current_time( 'mysql', true );

			$post_args['post_status']   = 'future';
			$post_args['post_date']     = gmdate( 'Y-m-d H:i:s', strtotime("$time +$sends_after $sends_after_unit") );
			$post_args['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', strtotime("$time_gmt +$sends_after $sends_after_unit") );
		}

		$post_args = apply_filters( 'noptin_schedule_new_post_automation_details', $post_args );
		wp_insert_post( $post_args, true );

	}

	/**
	 * (Maybe) Send out a new post notification
	 */
	public function maybe_send_notification( $key ) {

		// Is it a bg_email?
		if( 'bg_email' != get_post_meta( $key, 'campaign_type', true ) ) {
			return;
		}

		// Ensure this is a new post notification.
		if( 'new_post_notification' != get_post_meta( $key, 'bg_email_type', true ) ) {
			return;
		}

		$campaign_id = get_post_meta( $key, 'associated_campaign', true );
		$post_id     = get_post_meta( $key, 'associated_post', true );

		// If a notification has already been send abort...
		if( get_post_meta( $post_id, 'noptin_associated_new_post_notification_campaign', true ) ) {
			return;
		}

		$this->notify( $post_id, $campaign_id, $key );

	}

	/**
	 * Send out a new post notification
	 */
	public function notify( $post_id, $campaign_id, $key = '' ) {

		// Ensure that both the campaign and post are published.
		if( 'publish' != get_post_status( $post_id ) ||  'publish' != get_post_status( $campaign_id ) ) {
			return;
		}

		update_post_meta( $post_id, 'noptin_associated_new_post_notification_campaign', $campaign_id );

		if( empty( $key ) ) {
			$key = $campaign_id;
		}

		$noptin   = noptin();
		$campaign = get_post( $campaign_id );
		$post     = get_post( $post_id, ARRAY_A, 'display' );

		add_filter('excerpt_more', array( $this, 'excerpt_more' ), 100000 );
		$post['post_excerpt'] = get_the_excerpt( $post_id );
		remove_filter('excerpt_more', array( $this, 'excerpt_more' ), 100000 );

		$post['excerpt'] 	  = $post['post_excerpt'];
		$post['post_content'] = apply_filters( 'the_content', $post['post_content']);
		$post['content'] 	  = $post['post_content'];
		$post['post_title']   = get_the_title( $post_id );
		$post['title']   	  = $post['post_title'];

		$author = get_userdata( $post['post_author'] );

		// Author details.
		$post['post_author']       = $author->display_name;
		$post['post_author_email'] = $author->user_email;
		$post['post_author_login'] = $author->user_login;
		$post['post_author_id']    = $author->ID;

		// Date.
		$post['post_date']         = get_the_date( '', $post_id );

		// Link.
		$post['post_url']     = get_the_permalink( $post_id );

		unset( $post['ID'] );
		$post['post_id']	  = $post_id;

		// Read more button.
		$post['read_more_button']	  = $this->read_more_button( $post['post_url'] );
		$post['/read_more_button']    = '</a></div>';

		$item     = array(
			'campaign_id' 		=> $campaign_id,
			'associated_post' 	=> $post_id,
			'subscribers_query' => '1=1',
			'key'				=> $key,
			'automation_type'   => 'post_notifications',
			'campaign_type'     => 'automation',
			'campaign_data'		=> array(
				'email_body'	=> wp_kses_post( stripslashes_deep( $campaign->post_content ) ),
				'email_subject' => sanitize_text_field( stripslashes_deep( get_post_meta( $campaign_id, 'subject', true ) ) ),
				'preview_text'  => sanitize_text_field( stripslashes_deep( get_post_meta( $campaign_id, 'preview_text', true ) ) ),
				'template' 		=> get_noptin_include_dir( 'admin/templates/email-templates/paste.php' ),
				'merge_tags'	=> $post,
			),
		);

		if( $content  = get_post_meta( $post_id, 'noptin_post_notify_content', true ) ) {
			$item['campaign_data']['email_body'] = wp_kses_post( stripslashes_deep( $content ) );
		}

		if( $subject = get_post_meta( $post_id, 'noptin_post_notify_subject', true ) ) {
			$item['campaign_data']['email_subject'] = sanitize_text_field( stripslashes_deep( $subject ) );
		}

		if( $preview =  get_post_meta( $post_id, 'noptin_post_notify_preview_text', true ) ) {
			$item['campaign_data']['preview_text'] = sanitize_text_field( stripslashes_deep( $preview ) );
		}

		$item = apply_filters( 'noptin_mailer_new_post_automation_details', $item, $post_id, $campaign_id );

		$noptin->bg_mailer->push_to_queue( $item );

		$noptin->bg_mailer->save()->dispatch();

	}

	/**
	 * Generates read more button markup
	 */
	public function read_more_button( $url ) {
		$url  = esc_url( $url );
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

		if( ! is_array( $item ) || empty( $item['campaign_id'] ) || empty( $item['associated_post'] ) ) {
			return;
		}

		$post_ids = get_posts(array(
			'fields'        => 'ids',
			'numberposts'	=> -1,
			'post_type'		=> 'noptin-campaign',
			'meta_query'    => array(
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
				)
			)
		));

		if( is_array( $post_ids ) ) {
			foreach( $post_ids as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}

	}

	/**
	 * Filters an automation's details
	 */
	public function about_automation( $about, $type, $automation ){

		if( 'post_notifications' != $type ) {
			return $about;
		}

		$delay = 'immeadiately';

		$sends_after      = (int) get_post_meta( $automation->ID, 'noptin_sends_after', true );
		$sends_after_unit = sanitize_text_field( get_post_meta( $automation->ID, 'noptin_sends_after_unit', true ) );

		if( $sends_after ) {
			$delay = "$sends_after $sends_after_unit after";
		}

		return "Sends <em style='color: #607D8B;'>$delay</em> new content is published";
	}

}

$post_notifications = new Noptin_New_Post_Notify();
$post_notifications->init();
