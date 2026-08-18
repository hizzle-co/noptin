<?php

namespace Hizzle\Noptin\Integrations\WordPress_Comment_Form;

defined( 'ABSPATH' ) || exit;

/**
 * Container for a WordPress comment author.
 */
class Commentor extends \Hizzle\Noptin\Objects\Person {

	/**
	 * @var \WP_Comment|null The external comment object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param int|\WP_Comment $comment Comment ID or object.
	 */
	public function __construct( $comment ) {
		$this->external = is_numeric( $comment ) ? get_comment( $comment ) : $comment;
	}

	/**
	 * Checks whether the comment author exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return $this->external instanceof \WP_Comment && ! empty( $this->external->comment_ID );
	}

	/**
	 * Retrieves a comment author field.
	 *
	 * @param string $field Field name.
	 * @param array  $args  Formatting arguments.
	 * @return mixed
	 */
	public function get( $field, $args = array() ) {
		if ( ! $this->exists() ) {
			return null;
		}

		$map = array(
			'name'       => 'comment_author',
			'email'      => 'comment_author_email',
			'website'    => 'comment_author_url',
			'ip_address' => 'comment_author_IP',
			'user_id'    => 'user_id',
		);

		return isset( $map[ $field ] ) ? $this->external->{$map[ $field ]} : $this->get_provided( $field, $args );
	}
}
