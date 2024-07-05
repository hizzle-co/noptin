<?php
/**
 * Emails API: Post digests.
 *
 * Automatically send your subscribers a daily, weekly or monthly email highlighting your latest content.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Automatically send your subscribers a daily, weekly or monthly email highlighting your latest content.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Post_Digest extends \Hizzle\Noptin\Emails\Types\Recurring {

	/**
	 * @var WP_Post[]
	 */
	public $posts;

	/**
	 * The current date query.
	 *
	 * @var array
	 */
	public $date_query;

	/**
	 * The current post digest.
	 *
	 * @var \Hizzle\Noptin\Emails\Email
	 */
	public $post_digest;

	/**
	 * Whether or not posts were found.
	 *
	 * @var bool|null
	 */
	public $posts_found = null;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->category          = '';
		$this->type              = 'post_digest';
		$this->notification_hook = 'noptin_send_post_digest';
		add_filter( 'noptin_email_can_send', array( $this, 'can_send' ), 10, 2 );
	}

	/**
	 * Check if we can send the post digest.
	 *
	 * @param bool $can_send
	 * @param \Hizzle\Noptin\Emails\Email $email
	 */
	public function can_send( $can_send, $email ) {

		if ( $can_send && 'automation' === $email->type && $this->type === $email->get_sub_type() && 'visual' !== $email->get_email_type() ) {
			$content = $email->get_content( $email->get_email_type() );

			// Check if we have [[posts or [[post_digest merge tags.
			if ( false === strpos( $content, '[[posts' ) && false === strpos( $content, '[[post_digest' ) ) {
				$can_send = false;
			}
		}

		return $can_send;
	}

	/**
	 * Retrieves the automated email type name.
	 *
	 */
	public function get_name() {
		return __( 'Post Digest', 'newsletter-optin-box' );
	}

	/**
	 * Retrieves the automated email type description.
	 *
	 */
	public function get_description() {
		return __( 'Automatically send your subscribers a daily, weekly, monthly or yearly email highlighting your latest content.', 'newsletter-optin-box' );
	}

	/**
	 * Returns the default subject.
	 *
	 */
	public function default_subject() {
		return __( 'Check out our latest blog posts', 'newsletter-optin-box' );
	}

	/**
	 * Returns the default heading.
	 *
	 */
	public function default_heading() {
		return $this->default_subject();
	}

	/**
	 * Returns the default content.
	 *
	 */
	public function default_content_normal() {
		return '<div>[[post_digest style=list]]</div>';
	}

	/**
	 * Prepares the default blocks.
	 *
	 * @return string
	 */
	protected function prepare_default_blocks() {
		return '<!-- wp:html -->[[post_digest style=list]]<!-- /wp:html -->';
	}

	/**
	 * Returns the default plain text content.
	 *
	 */
	public function default_content_plain_text() {
		return '[[post_digest style=list]]';
	}

	/**
	 * Returns the default frequency.
	 *
	 */
	public function default_frequency() {
		return 'daily';
	}

	/**
	 * Returns the default x days.
	 */
	public function default_x_days() {
		return '14';
	}

	/**
	 * Returns the default time.
	 *
	 */
	public function default_time() {
		return '07:00';
	}

	/**
	 * Returns default email properties.
	 *
	 * @param array $props
	 * @param \Hizzle\Noptin\Emails\Email $email
	 * @return array
	 */
	public function get_default_props( $props, $email ) {

		if ( $email->type !== $this->type && $email->get_sub_type() !== $this->type ) {
			return $props;
		}

		$props['noptin-ap-post-type'] = 'post';

		return parent::get_default_props( $props, $email );
	}

	/**
	 * Fired before sending a campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 */
	public function before_send( $campaign ) {

		if ( $this->type !== $campaign->type && $this->type !== $campaign->get_sub_type() ) {
			return;
		}

		// Prepare environment.
		$this->post_digest = $campaign;
		$this->posts_found = null;
		$this->date_query  = $this->get_date_query( $campaign );

		parent::before_send( $campaign );
	}

	/**
	 * Fired after sending a campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 */
	public function after_send( $campaign ) {

		if ( $this->type !== $campaign->type && $this->type !== $campaign->get_sub_type() ) {
			return;
		}

		$this->post_digest = null;
		parent::after_send( $campaign );
	}

	/**
	 * Retrieve matching posts since last send.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 * @return array
	 */
	public function get_date_query( $campaign ) {

		$time = $campaign->get( 'time' );

		if ( empty( $time ) ) {
			$time = '07:00';
		}

		switch ( $campaign->get( 'frequency' ) ) {

			// Get posts published yesterday.
			case 'daily':
				return array(
					array(
						'after'     => 'yesterday midnight',
						'before'    => 'today midnight',
						'inclusive' => true,
					),
				);

			// Get posts published in the last 7 days.
			case 'weekly':
				return array(
					'after' => gmdate( 'Y-m-d', strtotime( '-7 days' ) ),
				);

			// Get posts published in the last 30 days.
			case 'monthly':
				return array(
					'after' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
				);

			// Get posts published in the last 365 days.
			case 'yearly':
				return array(
					'after' => gmdate( 'Y-m-d', strtotime( '-365 days' ) ),
				);

			// Get posts published last x days.
			case 'x_days':
				$days = $campaign->get( 'x_days' );
				if ( empty( $days ) ) {
					$days = 14;
				}

				return array(
					'after' => gmdate( 'Y-m-d', strtotime( "-$days days" ) ),
				);
		}

		return array();
	}

	/**
	 * Retrieves an array of supported merge tags.
	 *
	 * @return array
	 */
	public function get_merge_tags() {

		return array(
			__( 'Digest', 'newsletter-optin-box' ) => array(

				'post_digest' => array(
					'description' => __( 'Displays your latest content.', 'newsletter-optin-box' ),
					'callback'    => array( $this, 'process_merge_tag' ),
					'example'     => 'post_digest style="list" limit="10"',
					'partial'     => true,
				),

			),

		);
	}

	/**
	 * Processes the post digest merge tag.
	 *
	 * @param array $args
	 * @param string $field
	 * @return string
	 */
	public function process_merge_tag( $args = array() ) {

		if ( is_null( $this->posts_found ) ) {
			$this->posts_found = false;
		}

		// Fetch the posts.
		$posts = $this->get_merge_tag_posts( $args );

		// Abort if we have no posts.
		if ( empty( $posts ) ) {
			$GLOBALS['noptin_email_force_skip'] = array(
				'message' => __( 'No posts found.', 'newsletter-optin-box' ),
				'source'  => 'post_digest',
			);

			return '';
		}

		// Unset the force skip.
		if ( ! empty( $GLOBALS['noptin_email_force_skip'] ) && isset( $GLOBALS['noptin_email_force_skip']['source'] ) && 'post_digest' === $GLOBALS['noptin_email_force_skip']['source'] ) {
			unset( $GLOBALS['noptin_email_force_skip'] );
		}

		// We have posts.
		$this->posts_found = true;

		return $this->get_posts_html( $args, $posts );
	}

	/**
	 * Retrieves the content for the posts merge tag.
	 *
	 * @param array $args
	 * @return WP_Post[]
	 */
	public function get_merge_tag_posts( $args = array() ) {

		$query = array(
			'numberposts'      => isset( $args['limit'] ) ? intval( $args['limit'] ) : 10,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => true,
		);

		if ( ! empty( $this->date_query ) ) {
			$query['date_query'] = $this->date_query;
		}

		$query = apply_filters( 'noptin_post_digest_merge_tag_query', $query, $args, $this );
		$posts = get_posts( $query );

		// Debug the query later.
		if ( defined( 'NOPTIN_IS_TESTING' ) && NOPTIN_IS_TESTING && ! empty( $GLOBALS['wpdb']->last_query ) ) {
			noptin_error_log( $query, 'Post digest args' );
			noptin_error_log( $GLOBALS['wpdb']->last_query, 'Post digest query' );
			noptin_error_log( count( $posts ), 'Post digest posts' );
		}

		return $posts;
	}

	/**
	 * Get posts html to display.
	 *
	 * @param array $args
	 * @param WP_Post[] $campaign_posts
	 *
	 * @return string
	 */
	public function get_posts_html( $args = array(), $campaign_posts = array() ) {

		$template = isset( $args['style'] ) ? $args['style'] : 'list';

		// Allow overwriting this.
		$html = apply_filters( 'noptin_post_digest_html', null, $template, $campaign_posts );

		if ( null !== $html ) {
			return $html;
		}

		$args['campaign_posts'] = $campaign_posts;

		ob_start();
		get_noptin_template( 'post-digests/email-posts-' . $template . '.php', $args );
		return ob_get_clean();
	}
}
