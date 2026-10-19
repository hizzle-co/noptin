<?php

namespace Hizzle\Noptin\Integrations\WordPress_Comment_Form;

defined( 'ABSPATH' ) || exit;

/**
 * Collection of WordPress comment replies.
 */
class Replies extends \Hizzle\Noptin\Objects\Collection {

	/**
	 * Reply IDs processed during the current request.
	 *
	 * @var int[]
	 */
	private $processed = array();

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->record_class      = __NAMESPACE__ . '\\Comment';
		$this->integration       = 'wordpress-comments';
		$this->type              = 'reply';
		$this->label             = __( 'Replies', 'newsletter-optin-box' );
		$this->singular_label    = __( 'Reply', 'newsletter-optin-box' );
		$this->title_field       = 'id';
		$this->description_field = 'content';
		$this->url_field         = 'url';
		$this->provides          = array( 'comment' );
		$this->icon              = array(
			'icon' => 'admin-comments',
			'fill' => '#23282d',
		);

		parent::__construct();

		add_action( 'wp_set_comment_status', array( $this, 'comment_status_changed' ), 1000, 2 );
		add_action( 'wp_insert_comment', array( $this, 'comment_inserted' ), 1000, 2 );
	}

	/**
	 * Returns the reply trigger.
	 *
	 * @return array
	 */
	public function get_triggers() {
		return array(
			'new_comment_reply' => array(
				'label'       => __( 'Reply > Added', 'newsletter-optin-box' ),
				'description' => __( "When someone replies to someone else's comment", 'newsletter-optin-box' ),
				'subject'     => 'reply_author',
				'category'    => __( 'Comments', 'newsletter-optin-box' ),
			),
		);
	}

	/**
	 * Returns the available reply fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			'id'        => array(
				'label'      => __( 'ID', 'newsletter-optin-box' ),
				'type'       => 'number',
				'deprecated' => 'reply_id',
			),
			'post_id'   => array(
				'label'      => __( 'Post ID', 'newsletter-optin-box' ),
				'type'       => 'number',
				'deprecated' => 'reply_post_id',
			),
			'parent_id' => array(
				'label'      => __( 'Parent comment ID', 'newsletter-optin-box' ),
				'type'       => 'number',
				'deprecated' => 'reply_parent',
			),
			'date'      => array(
				'label'      => __( 'Date', 'newsletter-optin-box' ),
				'type'       => 'date',
				'deprecated' => 'reply_date',
			),
			'date_gmt'  => array(
				'label'      => __( 'Date (GMT)', 'newsletter-optin-box' ),
				'type'       => 'date',
				'deprecated' => 'reply_date_gmt',
			),
			'content'   => array(
				'label'      => __( 'Content', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'reply_content',
			),
			'karma'     => array(
				'label'      => __( 'Karma', 'newsletter-optin-box' ),
				'type'       => 'number',
				'deprecated' => 'reply_karma',
			),
			'approved'  => array(
				'label'      => __( 'Approval status', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'reply_approved',
			),
			'agent'     => array(
				'label'      => __( 'User agent', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'reply_agent',
			),
			'type'      => array(
				'label'      => __( 'Type', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'reply_type',
			),
			'url'       => array(
				'label' => __( 'URL', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'meta'      => $this->meta_key_tag_config(),
		);
	}

	/**
	 * Fires for an approved reply inserted into WordPress.
	 *
	 * @param int         $comment_id Comment ID.
	 * @param \WP_Comment $comment    Comment object.
	 */
	public function comment_inserted( $comment_id, $comment ) {
		if ( '1' === (string) $comment->comment_approved ) {
			$this->maybe_trigger( $comment_id );
		}
	}

	/**
	 * Fires when a reply becomes approved.
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $status     New status.
	 */
	public function comment_status_changed( $comment_id, $status ) {
		if ( in_array( (string) $status, array( '1', 'approve' ), true ) ) {
			$this->maybe_trigger( $comment_id );
		}
	}

	/**
	 * Fires the reply trigger.
	 *
	 * @param int $comment_id Comment ID.
	 */
	private function maybe_trigger( $comment_id ) {
		$comment_id = (int) $comment_id;

		if ( isset( $this->processed[ $comment_id ] ) ) {
			return;
		}

		$reply = get_comment( $comment_id );

		if ( ! $reply || empty( $reply->comment_parent ) ) {
			return;
		}

		$comment = get_comment( $reply->comment_parent );

		if ( ! $comment || $reply->comment_author_email === $comment->comment_author_email ) {
			return;
		}

		$this->processed[ $comment_id ] = $comment_id;

		$this->trigger(
			'new_comment_reply',
			array(
				'email'      => $reply->comment_author_email,
				'object_id'  => $comment_id,
				'subject_id' => $comment_id,
				'url'        => get_comment_link( $reply ),
			)
		);
	}

	/**
	 * Retrieves test data for the reply trigger.
	 *
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule Automation rule.
	 * @return array
	 * @throws \Exception When no reply exists.
	 */
	public function get_test_args( $rule ) {
		$replies = get_comments(
			array(
				'number'         => 1,
				'status'         => 'approve',
				'orderby'        => 'comment_date_gmt',
				'order'          => 'DESC',
				'parent__not_in' => array( 0 ),
			)
		);
		$reply = reset( $replies );

		if ( ! $reply ) {
			throw new \Exception( 'No matching comment reply exists.' );
		}

		return array(
			'email'      => $reply->comment_author_email,
			'object_id'  => (int) $reply->comment_ID,
			'subject_id' => (int) $reply->comment_ID,
		);
	}
}
