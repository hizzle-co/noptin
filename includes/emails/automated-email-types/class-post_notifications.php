<?php
/**
 * Emails API: New post notifications.
 *
 * Notify users whenever you publish a new blog post.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Notify users whenever you publish a new blog post.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_New_Post_Notification extends Noptin_Automated_Email_Type {

	/**
	 * @var string
	 */
	public $type = 'post_notifications';

	/**
	 * @var string
	 */
	public $notification_hook = 'noptin_new_post_notification';

	/**
	 * @var WP_Post
	 */
	public $posts;

	/**
	 * Registers hooks.
	 *
	 */
	public function add_hooks() {
		parent::add_hooks();

		// Notify subscribers.
		add_action( 'transition_post_status', array( $this, 'maybe_schedule_notification' ), 10, 3 );

	}

	/**
	 * Retrieves the automated email type name.
	 *
	 */
	public function get_name() {
		return __( 'New Post Notification', 'newsletter-optin-box' );
	}

	/**
	 * Retrieves the automated email type description.
	 *
	 */
	public function get_description() {
		return __( 'Get more traffic to your site by notifying your subscribers every time you publish new content.', 'newsletter-optin-box' );
	}

	/**
	 * Retrieves the automated email type image.
	 *
	 */
	public function the_image() {
		echo '<svg xmlns="http://www.w3.org/2000/svg" fill="#ff5722" viewBox="0 0 122.88 122.83"><path d="M73.81,7.47A43.14,43.14,0,0,1,92.69,19.35a42.33,42.33,0,0,1,10.76,21.36l0,.28c.21,1.21.36,2.36.45,3.44.11,1.26.17,2.53.17,3.8h0V58.36c0,2.81,0,5.67.2,8.54a32.41,32.41,0,0,0,4.34,14.62A36.6,36.6,0,0,0,120,92.83a6.34,6.34,0,0,1,2.65,3.65,6.52,6.52,0,0,1-.08,3.56,6.62,6.62,0,0,1-1.91,3,6.33,6.33,0,0,1-4.25,1.57H82.27l0,.08h0c-4.14,24.2-37.61,24.13-41.65-.08H6.45A6.33,6.33,0,0,1,2,102.92a6.6,6.6,0,0,1-1.81-6.5A6.33,6.33,0,0,1,3,92.71c5.66-3.83,9.62-8,12.12-12.76s3.65-10.44,3.65-17.28V48.23c0-1.16.06-2.42.18-3.77s.29-2.52.51-3.76A42.89,42.89,0,0,1,49.39,7.41C54-2.47,69.2-2.49,73.81,7.47ZM87.71,24A36.34,36.34,0,0,0,70.38,13.57,3.42,3.42,0,0,1,68,11.22c-1.71-5.87-11-6-12.72-.05a3.43,3.43,0,0,1-2.48,2.38A36.1,36.1,0,0,0,26.15,41.9q-.28,1.58-.42,3.15c-.09,1-.13,2-.13,3.18V62.67c0,7.91-1.38,14.56-4.45,20.43-2.94,5.62-7.36,10.39-13.54,14.72H115.27A42.38,42.38,0,0,1,102.8,85,39.18,39.18,0,0,1,97.5,67.4c-.22-2.88-.21-6-.2-9V48.23h0c0-1.1,0-2.17-.13-3.22s-.21-2-.36-2.85l-.06-.27a35.62,35.62,0,0,0-9-17.9Z"/></svg>';
	}

	/**
	 * Returns the default subject.
	 *
	 */
	public function default_subject() {
		return '[[post_title]]';
	}

	/**
	 * Returns the default heading.
	 *
	 */
	public function default_heading() {
		return '[[post_title]]';
	}

	/**
	 * Returns the default content.
	 *
	 */
	public function default_content_normal() {
		ob_start();
		?>
		<p>[[post_excerpt]]</p>
		<p>[[button url="post_url" text="<?php esc_attr_e( 'Continue Reading', 'newsletter-optin-box' ); ?>"]]</p>
		<p><?php _e( "If that doesn't work, copy and paste the following link in your browser:", 'newsletter-optin-box' ); ?></p>
		<p>[[post_url]]</p>
		<p><?php _e( 'Cheers', 'newsletter-optin-box' ); ?></p>
		<p>[[post_author]]</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the default plain text content.
	 *
	 */
	public function default_content_plain_text() {
		return noptin_convert_html_to_text( $this->default_content_normal() );
	}

	/**
	 * Displays a metabox.
	 *
	 * @param Noptin_Automated_Email $campaign
	 */
	public function render_metabox( $campaign ) {

		if ( defined( 'NOPTIN_WELCOME_EMAILS_FILE' ) ) {
			return;
		}

		$url = add_query_arg(
			array(
				'utm_medium'   => 'plugin-dashboard',
				'utm_campaign' => 'post-digests',
				'utm_source'   => 'email-editor',
			),
			'https://noptin.com/product/ultimate-addons-pack'
		);

		printf(
			'<p>%s</p><p>%s</p>',
			__( 'By default, this email will only send for new blog posts.', 'newsletter-optin-box' ),
			sprintf(
				__( 'Install the %s to send notifications for products and other post types or limit notifications to certain categories and tags.', 'newsletter-optin-box' ),
				"<a href='$url' target='_blank'>Ultimate Addons Pack</a>"
			)
		);

	}

	/**
	 * Filters automation summary.
	 *
	 * @param string $about
	 * @param Noptin_Automated_Email $campaign
	 */
	public function about_automation( $about, $campaign ) {

		if ( ! $campaign->sends_immediately() ) {

			return sprintf(
				__( 'Sends %s after new content is published', 'newsletter-opti-box' ),
				(int) $campaign->get_sends_after() . ' ' . esc_html( $campaign->get_sends_after_unit( true ) )
			);

		}

		return __( 'Sends immediately new content is published', 'newsletter-opti-box' );

	}

	/**
	 * Retrieves a URL to the help document.
	 */
	public function help_url() {

		add_query_arg(
			array(
				'utm_medium'   => 'plugin-dashboard',
				'utm_campaign' => 'new-post-notifications',
				'utm_source'   => urlencode( esc_url( get_home_url() ) ),
			),
			'https://noptin.com/guide/email-automations/new-post-notifications/'
		);

	}

	/**
	 * Checks if a given campaign was already sent.
	 */
	public function was_notification_sent( $post_id ) {
		$sent_notification = get_post_meta( $post_id, 'noptin_sent_notification_campaign', true );
		return is_array( $sent_notification ) && $post_id == $sent_notification[0];
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
		if ( $this->was_notification_sent( $post->ID ) ) {
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
	 * Checks if a given notification is valid for a given post
	 *
	 * @param Noptin_Automated_Email $automation
	 * @param WP_Post $post
	 */
	public function is_automation_valid_for( $automation, $post ) {

		$post_type          = $automation->get( 'post_type' );
		$post_type          = empty( $post_type ) ? 'post' : $post_type;
		$allowed_post_types = apply_filters( 'noptin_new_post_notification_post_types', array( $post_type ), $automation );

		if ( ! in_array( $post->post_type, $allowed_post_types, true ) ) {
			return false;
		}

		return apply_filters( 'noptin_new_post_notification_valid_for_post', true, $automation, $post );

	}

	/**
	 * (Maybe) Send out a new post notification
	 *
	 * @param int $object_id
	 * @param Noptin_Automated_Email $automation
	 * @param string $key
	 */
	public function maybe_send_notification( $post_id, $campaign_id, $key = '' ) {

		// If a notification has already been send abort...
		if ( $this->was_notification_sent( $post_id ) ) {
			return;
		}

		// Ensure that both the campaign and post are published.
		if ( 'publish' !== get_post_status( $post_id ) || 'publish' !== get_post_status( $campaign_id ) ) {
			return;
		}

		if ( empty( $key ) ) {
			$key = $post_id . '_' . $campaign_id;
		}

		$this->notify( $post_id, $campaign_id, $key );

	}

	/**
	 * Send out a new post notification
	 */
	protected function notify( $post_id, $campaign_id, $key ) {

		update_post_meta( $post_id, 'noptin_sent_notification_campaign', array( $post_id, $campaign_id ) );

		// Create normal campaign.
		$campaign = new Noptin_Automated_Email( $campaign_id );
		$post     = array(
			'post_status'   => 'publish',
			'post_parent'   => $campaign->id,
			'post_type'     => 'noptin-campaign',
			'post_date'     => current_time( 'mysql' ),
			'post_date_gmt' => current_time( 'mysql', true ),
			'edit_date'     => true,
			'post_title'    => sanitize_text_field( $campaign->get_subject() ),
			'post_content'  => $campaign->get_content(),
			'meta_input'    => array(
				'campaign_type'         => 'newsletter',
				'preview_text'          => esc_html( stripslashes_deep( get_post_meta( $campaign_id, 'preview_text', true ) ) ),
				'new_post_notification' => $key,
				'custom_merge_tags'     => $this->get_post_merge_tags( get_post( $post_id ) ),
				'campaign_id'           => $campaign_id,
				'associated_post'       => $post_id,
				'subscribers_query'     => array(),
				'email_sender'          => $campaign->get( 'email_sender' ),
				'custom_title'          => sprintf( __( 'New post notification for "%s"', 'newsletter-optin-box' ), esc_html( get_the_title( $post_id ) ) ),
			),
		);

		foreach( noptin_get_newsletter_meta() as $meta_key ) {
			$post['meta_input'][ $meta_key ] = get_post_meta( $campaign_id, $meta_key, true );
		}

		$content  = get_post_meta( $post_id, 'noptin_post_notify_content', true );
		if ( ! empty( $content ) ) {
			$post['post_content'] = wp_kses_post( stripslashes_deep( $content ) );
		}

		$subject = get_post_meta( $post_id, 'noptin_post_notify_subject', true );
		if ( ! empty( $subject ) ) {
			$post['post_title'] = esc_html( stripslashes_deep( $subject ) );
		}

		$preview = get_post_meta( $post_id, 'noptin_post_notify_preview_text', true );
		if ( ! empty( $preview ) ) {
			$post['meta_input']['preview_text'] = esc_html( stripslashes_deep( $preview ) );
		}

		$post['post_title']                 = add_noptin_merge_tags( $post['post_title'], $post['meta_input']['custom_merge_tags'], false, false );
		$post['post_content']               = add_noptin_merge_tags( $post['post_content'], $post['meta_input']['custom_merge_tags'], false, false );
		$post['meta_input']['preview_text'] = add_noptin_merge_tags( $post['meta_input']['preview_text'], $post['meta_input']['custom_merge_tags'], false, false );
		$post = apply_filters( 'noptin_mailer_new_post_automation_campaign_details', $post );

		// Send normal campaign.
		if ( apply_filters( 'noptin_should_send_new_post_notification', true, $post ) ) {
			wp_insert_post( $post );
		}

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

		// Metadata.
		$tags['post_meta'] = map_deep( get_post_meta( $post->ID ), 'maybe_unserialize' );

		return $tags;
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
	 * Sends a test email.
	 *
	 * @param Noptin_Automated_Email $campaign
	 * @param string $recipient
	 * @return bool Whether or not the test email was sent
	 */
	public function send_test( $campaign, $recipient ) {

		$this->prepare_test_data( $campaign );

		// Maybe set related subscriber.
		$subscriber = get_noptin_subscriber( sanitize_email( $recipient ) );

		if ( $subscriber->exists() ) {
			$this->subscriber = $subscriber;
		}

		return $this->send( $recipient, 'test', array( sanitize_email( $recipient ) => false ) );
	}

	/**
	 * Prepares test data.
	 *
	 * @param Noptin_Automated_Email $email
	 */
	public function prepare_test_data( $email ) {

		// Prepare user and subscriber.
		parent::prepare_test_data( $email );

		$post_type = $email->get( 'post_type' );
		$post_type = empty( $post_type ) ? 'post' : $post_type;

		// Fetch test posts.
		$this->post = current(
			get_posts(
				array(
					'numberposts'      => 1,
					'orderby'          => 'date',
					'order'            => 'DESC',
					'post_type'        => $post_type,
					'suppress_filters' => true,
				)
			)
		);

		// If no post found, abort.
		if ( ! $this->post ) {
			throw new Exception( __( 'Could not find a post for this preview.', 'newsletter-optin-box' ) );
		}

	}

	/**
	 * Fired after sending a campaign.
	 *
	 * @param Noptin_Automated_Email $campaign
	 */
	protected function after_send( $campaign ) {

		// Remove temp variables.
		$this->post = null;

		parent::after_send( $campaign );
	}

}
