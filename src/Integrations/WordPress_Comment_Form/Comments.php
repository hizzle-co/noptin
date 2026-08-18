<?php

namespace Hizzle\Noptin\Integrations\WordPress_Comment_Form;

defined( 'ABSPATH' ) || exit;

/**
 * Collection of WordPress comments.
 */
class Comments extends \Hizzle\Noptin\Objects\Collection {

	/**
	 * Comment IDs processed during the current request.
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
		$this->type              = 'comment';
		$this->label             = __( 'Comments', 'newsletter-optin-box' );
		$this->singular_label    = __( 'Comment', 'newsletter-optin-box' );
		$this->title_field       = 'id';
		$this->description_field = 'content';
		$this->url_field         = 'url';
		$this->provides          = array( 'comment_author', 'post_author' );
		$this->icon              = array(
			'icon' => 'admin-comments',
			'fill' => '#23282d',
		);

		parent::__construct();

		add_action( 'wp_set_comment_status', array( $this, 'comment_status_changed' ), 1000, 2 );
		add_action( 'wp_insert_comment', array( $this, 'comment_inserted' ), 1000, 2 );
	}

	/**
	 * Returns the available comment triggers.
	 *
	 * @return array
	 */
	public function get_triggers() {
		return array(
			'new_comment' => array(
				'label'       => __( 'Comment > Added', 'newsletter-optin-box' ),
				'description' => __( 'When someone leaves a comment', 'newsletter-optin-box' ),
				'subject'     => 'comment_author',
			),
		);
	}

	/**
	 * Returns the available comment fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		$fields = array(
			'id'                 => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'post_id'            => array(
				'label' => __( 'Post ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'parent_id'          => array(
				'label' => __( 'Parent comment ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'date'               => array(
				'label' => __( 'Date', 'newsletter-optin-box' ),
				'type'  => 'date',
			),
			'date_gmt'           => array(
				'label' => __( 'Date (GMT)', 'newsletter-optin-box' ),
				'type'  => 'date',
			),
			'content'            => array(
				'label' => __( 'Content', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'karma'              => array(
				'label' => __( 'Karma', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'approved'           => array(
				'label' => __( 'Approval status', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'agent'              => array(
				'label' => __( 'User agent', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'type'               => array(
				'label' => __( 'Type', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'url'                => array(
				'label' => __( 'URL', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'post_date'          => array(
				'label'      => __( 'Post date', 'newsletter-optin-box' ),
				'type'       => 'date',
				'deprecated' => 'post_date',
			),
			'post_title'         => array(
				'label'      => __( 'Post title', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'post_title',
			),
			'post_url'           => array(
				'label'      => __( 'Post URL', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'post_url',
			),
			'post_excerpt'       => array(
				'label'      => __( 'Post excerpt', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'post_excerpt',
			),
			'post_content'       => array(
				'label'      => __( 'Post content', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'post_content',
			),
			'post_status'        => array(
				'label'      => __( 'Post status', 'newsletter-optin-box' ),
				'type'       => 'string',
				'options'    => get_post_stati(),
				'deprecated' => 'post_status',
			),
			'post_password'      => array(
				'label'      => __( 'Post password', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'post_password',
			),
			'post_name'          => array(
				'label'      => __( 'Post slug', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'post_name',
			),
			'post_modified'      => array(
				'label'      => __( 'Post modified date', 'newsletter-optin-box' ),
				'type'       => 'date',
				'deprecated' => 'post_modified',
			),
			'post_type'          => array(
				'label'      => __( 'Post type', 'newsletter-optin-box' ),
				'type'       => 'string',
				'options'    => get_post_types(),
				'deprecated' => 'post_type',
			),
			'post_comment_count' => array(
				'label'      => __( 'Post comment count', 'newsletter-optin-box' ),
				'type'       => 'number',
				'deprecated' => 'post_comment_count',
			),
			'meta'               => $this->meta_key_tag_config(),
		);

		$legacy_fields = $this->legacy_fields( 'comment' );

		foreach ( $fields as $key => $field ) {
			if ( isset( $legacy_fields[ $key ] ) ) {
				$fields[ $key ]['deprecated'] = $legacy_fields[ $key ];
			}
		}

		$fields['post_id']['deprecated'] = array( 'comment_post_id', 'post_id' );

		return $fields;
	}

	/**
	 * Maps collection fields to legacy comment merge tags.
	 *
	 * @param string $prefix Legacy prefix.
	 * @return array
	 */
	private function legacy_fields( $prefix ) {
		return array(
			'id'        => $prefix . '_id',
			'post_id'   => $prefix . '_post_id',
			'parent_id' => $prefix . '_parent',
			'date'      => $prefix . '_date',
			'date_gmt'  => $prefix . '_date_gmt',
			'content'   => $prefix . '_content',
			'karma'     => $prefix . '_karma',
			'approved'  => $prefix . '_approved',
			'agent'     => $prefix . '_agent',
			'type'      => $prefix . '_type',
		);
	}

	/**
	 * Fires for an approved comment inserted into WordPress.
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
	 * Fires when a comment becomes approved.
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
	 * Fires the appropriate comment trigger.
	 *
	 * @param int $comment_id Comment ID.
	 */
	private function maybe_trigger( $comment_id ) {
		$comment_id = (int) $comment_id;

		if ( isset( $this->processed[ $comment_id ] ) ) {
			return;
		}

		$comment = get_comment( $comment_id );

		if ( ! $comment || ! empty( $comment->comment_parent ) ) {
			return;
		}

		$this->processed[ $comment_id ] = $comment_id;
		$args                           = array(
			'email'      => $comment->comment_author_email,
			'object_id'  => $comment_id,
			'subject_id' => $comment_id,
			'url'        => get_comment_link( $comment ),
		);

		$this->trigger( 'new_comment', $args );
	}

	/**
	 * Retrieves test data for a comment trigger.
	 *
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule Automation rule.
	 * @return array
	 * @throws \Exception When no matching comment exists.
	 */
	public function get_test_args( $rule ) {
		$query = array(
			'number'  => 1,
			'status'  => 'approve',
			'orderby' => 'comment_date_gmt',
			'order'   => 'DESC',
		);

		$query['parent'] = 0;

		$comments = get_comments( $query );

		$comment = reset( $comments );

		if ( ! $comment ) {
			throw new \Exception( 'No matching comment exists.' );
		}

		$args = array(
			'email'      => $comment->comment_author_email,
			'object_id'  => (int) $comment->comment_ID,
			'subject_id' => (int) $comment->comment_ID,
		);

		return $args;
	}
}
