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
	public $category = 'Mass Mail';

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
	public $post;

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
	 * Returns the image URL or dashicon for the automated email type.
	 *
	 * @return string|array
	 */
	public function get_image() {
		return array(
			'icon' => 'admin-post',
			'fill' => '#3f9ef4',
		);
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
	 * Returns the default preview text.
	 *
	 */
	public function default_preview_text() {
		return __( 'New post published on [[blog_name]]', 'newsletter-optin-box' );
	}

	/**
	 * Returns the default content.
	 *
	 */
	public function default_content_normal() {
		ob_start();
		?>
		<p>[[featured_image size="medium_large"]]</p>
		<p>[[post_excerpt]]</p>
		<p>[[button url="[[post_url]]" text="<?php esc_attr_e( 'Continue Reading', 'newsletter-optin-box' ); ?>"]]</p>
		<p><?php esc_html_e( "If that doesn't work, copy and paste the following link into your browser:", 'newsletter-optin-box' ); ?></p>
		<p>[[post_url]]</p>
		<p><?php esc_html_e( 'Cheers', 'newsletter-optin-box' ); ?></p>
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

		if ( defined( 'NOPTIN_ADDONS_PACK_VERSION' ) ) {
			return;
		}

		printf(
			'<p>%s</p><p>%s</p>',
			esc_html__( 'By default, this email will only send for new blog posts.', 'newsletter-optin-box' ),
			sprintf(
				// translators: %s is the link to the Noptin addons pack.
				esc_html__( 'Install the %s to send notifications for products and other post types or limit notifications to certain categories, tags, and authors.', 'newsletter-optin-box' ),
				"<a href='" . esc_url( noptin_get_upsell_url( '/ultimate-addons-pack/', 'new-post-notifications', 'email-campaigns' ) ) . "' target='_blank'>Ultimate Addons Pack</a>"
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
				// Translators: %s is the delay.
				__( 'Sends %s after new content is published', 'newsletter-optin-box' ),
				(int) $campaign->get_sends_after() . ' ' . esc_html( $campaign->get_sends_after_unit( true ) )
			);

		}

		return __( 'Sends immediately new content is published', 'newsletter-optin-box' );

	}

	/**
	 * Retrieves a URL to the help document.
	 */
	public function help_url() {

		return noptin_get_upsell_url( '/guide/email-automations/new-post-notifications/', 'new-post-notifications', 'email-campaigns' );
	}

	/**
	 * Checks if a given campaign was already sent.
	 */
	public function was_notification_sent( $post_id ) {
		$sent_notification = get_post_meta( $post_id, 'noptin_sent_notification_campaign', true );
		return is_array( $sent_notification ) && $post_id === (int) $sent_notification[0];
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
			if ( $automation->can_send() && $this->is_automation_valid_for( $automation, $post ) ) {
				$this->schedule_notification( $post->ID, $automation );
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

		// Ensure that both the campaign and post are still published.
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
		$type     = $campaign->get_email_type();
		$content  = $campaign->get_content( $type );

		// Prepare environment.
		$this->post = get_post( $post_id );
		$this->before_send( $campaign );

		// Parse paragraphs.
		if ( 'normal' === $type ) {
			$content = wpautop( trim( $content ) );
		}

		// Prepare campaign args.
		$args = array_merge(
			$campaign->options,
			array(
				'parent_id'         => $campaign->id,
				'status'            => 'publish',
				'subject'           => noptin_parse_email_subject_tags( $campaign->get_subject(), true ),
				'heading'           => noptin_parse_email_content_tags( $campaign->get( 'heading' ), true ),
				'content_' . $type  => trim( noptin_parse_email_content_tags( $content, true ) ),
				'associated_post'   => $post_id,
				'subscribers_query' => array(),
				'preview_text'      => noptin_parse_email_content_tags( $campaign->get( 'preview_text' ), true ),
				'footer_text'       => noptin_parse_email_content_tags( $campaign->get( 'footer_text' ), true ),
				'custom_title'      => sprintf( /* translators: Post title */ __( 'New post notification for "%s"', 'newsletter-optin-box' ), esc_html( get_the_title( $post_id ) ) ),
			)
		);

		// Remove unrelated content.
		foreach ( array( 'content_normal', 'content_plain_text', 'content_raw_html' ) as $content_type ) {
			if ( 'content_' . $type !== $content_type ) {
				unset( $args[ $content_type ] );
			}
		}

		// Prepare the newsletter.
		$newsletter = new Noptin_Newsletter_Email( $args );

		// Send normal campaign.
		if ( apply_filters( 'noptin_should_send_new_post_notification', true, $newsletter, $campaign ) ) {
			$newsletter->save();
		}

		// Clear environment.
		$this->post = null;
		$this->after_send( $campaign );

	}

	/**
	 * Generates read more button markup
	 */
	public function read_more_button( $url ) {
		$url = esc_url( $url );
		return "<div style='text-align: center; padding: 20px;' align='center'> <a href='$url' class='noptin-round' style='background: #1a82e2; display: inline-block; padding: 16px 36px; font-size: 16px; color: #ffffff; text-decoration: none; border-radius: 6px;'>";
	}

	/**
	 * Retrieves an array of supported merge tags.
	 *
	 * @return array
	 */
	public function get_merge_tags() {

		return array(
			__( 'Post', 'newsletter-optin-box' ) => array(

				'post_id'           => array(
					'description' => __( "The post's ID", 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'post_id',
					'partial'     => true,
				),

				'post_date'         => array(
					'description' => __( "The post's published date", 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'post_date',
					'partial'     => true,
				),

				'post_url'          => array(
					'description' => __( "The post's URL", 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'post_url',
					'partial'     => true,
				),

				'featured_image'    => array(
					'description' => __( "The post's featured image.", 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'featured_image size="medium_large"',
					'partial'     => true,
				),

				'post_title'        => array(
					'description' => __( "The post's title.", 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'post_title',
					'partial'     => true,
				),

				'title'             => array(
					'description' => __( 'Alias for [[post_title]].', 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'title',
					'partial'     => true,
				),

				'post_excerpt'      => array(
					'description' => __( "The post's excerpt.", 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'post_excerpt',
					'partial'     => true,
				),

				'excerpt'           => array(
					'description' => __( 'Alias for [[post_excerpt]].', 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'excerpt',
					'partial'     => true,
				),

				'post_content'      => array(
					'description' => __( "The post's content.", 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'post_content',
					'partial'     => true,
				),

				'content'           => array(
					'description' => __( 'Alias for [[post_content]].', 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'content',
					'partial'     => true,
				),

				'post_author'       => array(
					'description' => __( "The post's author", 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'post_author',
					'partial'     => true,
				),

				'post_author_email' => array(
					'description' => __( "The post author's email", 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'post_author_email',
					'partial'     => true,
				),

				'post_author_login' => array(
					'description' => __( "The post author's login name", 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'post_author_login',
					'partial'     => true,
				),

				'post_author_id'    => array(
					'description' => __( "The post author's user ID", 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'post_author_id',
					'partial'     => true,
				),

				'post_meta'         => array(
					'description' => __( 'Displays the value of a give meta key.', 'newsletter-optin-box' ),
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'post_meta',
					'partial'     => true,
				),

				'read_more_button'  => array(
					'description' => '',
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => 'read_more_button',
					'partial'     => true,
					'hidden'      => true,
				),

				'/read_more_button' => array(
					'description' => '',
					'callback'    => array( $this, 'get_post_field' ),
					'example'     => '/read_more_button',
					'partial'     => true,
					'hidden'      => true,
				),

			),

		);

	}

	/**
	 * Post field value of the current post.
	 *
	 * @param array $args
	 * @param string $field
	 * @return string
	 */
	public function get_post_field( $args = array(), $field = 'post_id' ) {
		$default = isset( $args['default'] ) ? $args['default'] : '';

		// Abort if no post.
		if ( empty( $this->post ) ) {
			return esc_html( $default );
		}

		// Process author fields.
		if ( in_array( $field, array( 'post_author', 'post_author_email', 'post_author_login', 'post_author_id' ), true ) ) {

			$author = get_userdata( $this->post->post_author );

			if ( empty( $author ) ) {
				return esc_html( $default );
			}

			switch ( $field ) {

				case 'post_author':
					return esc_html( $author->display_name );

				case 'post_author_email':
					return sanitize_email( $author->user_email );

				case 'post_author_login':
					return sanitize_user( $author->user_login );

				case 'post_author_id':
					return absint( $author->ID );
			}
		}

		// Process post fields.
		switch ( $field ) {

			case 'post_id':
				return $this->post->ID;

			case 'post_date':
				return get_the_date( '', $this->post );

			case 'post_url':
				return get_the_permalink( $this->post );

			case 'featured_image':
				$size = empty( $args['size'] ) ? 'medium_large' : $args['size'];
				return get_the_post_thumbnail( $this->post, $size );

			case 'post_excerpt':
			case 'excerpt':
				return noptin_get_post_excerpt( $this->post );

			case 'post_content':
			case 'content':
				return apply_filters( 'the_content', $this->post->post_content );

			case 'post_title':
			case 'title':
				return get_the_title( $this->post );

			case 'read_more_button':
				return $this->read_more_button( get_permalink( $this->post->ID ) );

			case '/read_more_button':
				return '</a></div>';

			case 'post_meta':
				// Abort if no key provided.
				if ( empty( $args['key'] ) ) {
					return esc_html( $default );
				}

				return wp_kses_post( (string) get_post_meta( $this->post->ID, trim( $args['key'] ), true ) );

		}

		return esc_html( $default );
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
