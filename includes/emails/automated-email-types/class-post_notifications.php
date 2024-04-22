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
	public $category = '';

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
		add_action( 'noptin_force_trigger_new_post_notification', array( $this, 'force_schedule_notification' ) );
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
		<p>[[featured_image size="large"]]</p>
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
	 * Notify subscribers when new content is published
	 */
	public function force_schedule_notification( $post ) {

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
	 * @param \Hizzle\Noptin\Emails\Email $automation
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
	 * @param \Hizzle\Noptin\Emails\Email $automation
	 * @param string $key
	 */
	public function maybe_send_notification( $post_id, $campaign_id ) {

		// If a notification has already been send abort...
		if ( $this->was_notification_sent( $post_id ) ) {
			return;
		}

		// Ensure that both the campaign and post are still published.
		if ( 'publish' !== get_post_status( $post_id ) || 'publish' !== get_post_status( $campaign_id ) ) {
			return;
		}

		update_post_meta( $post_id, 'noptin_sent_notification_campaign', array( $post_id, $campaign_id ) );

		// Prepare current title tag.
		$GLOBALS['noptin_current_title_tag'] = esc_html( get_the_title( $post_id ) );

		// Prepare environment.
		$this->post = get_post( $post_id );

		// Send campaign.
		$campaign = noptin_get_email_campaign_object( $campaign_id );
		$campaign->send();

		// Clear environment.
		$this->post = null;
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
					'example'     => 'featured_image size="large"',
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
					'hidden'      => true,
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
					'hidden'      => true,
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
					'hidden'      => true,
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
				$size = empty( $args['size'] ) ? 'large' : $args['size'];
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
	 * @param \Hizzle\Noptin\Emails\Email $email
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
			throw new Exception( esc_html__( 'Could not find a post for this preview.', 'newsletter-optin-box' ) );
		}
	}
}
