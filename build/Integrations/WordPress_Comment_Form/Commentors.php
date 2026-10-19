<?php

namespace Hizzle\Noptin\Integrations\WordPress_Comment_Form;

defined( 'ABSPATH' ) || exit;

/**
 * Collection of WordPress comment authors.
 */
class Commentors extends \Hizzle\Noptin\Objects\People {

	/**
	 * @var string Legacy merge-tag prefix.
	 */
	public $legacy_prefix;

	/**
	 * Class constructor.
	 */
	public function __construct( $type, $label, $singular_label, $legacy_prefix ) {
		$this->record_class   = __NAMESPACE__ . '\\Commentor';
		$this->integration    = 'wordpress-comments';
		$this->type           = $type;
		$this->label          = $label;
		$this->singular_label = $singular_label;
		$this->legacy_prefix  = $legacy_prefix;
		$this->is_stand_alone = false;
		$this->icon           = array(
			'icon' => 'admin-users',
			'fill' => '#23282d',
		);

		parent::__construct();
	}

	/**
	 * Retrieves a comment author by email address.
	 *
	 * @param string $email Email address.
	 * @return Commentor
	 */
	public function get_from_email( $email ) {
		$comments = get_comments(
			array(
				'author_email' => $email,
				'number'       => 1,
				'orderby'      => 'comment_date_gmt',
				'order'        => 'DESC',
			)
		);

		return $this->get( empty( $comments ) ? 0 : reset( $comments ) );
	}

	/**
	 * Retrieves a test comment author ID.
	 *
	 * @return int
	 */
	public function get_test_id() {
		$comments = get_comments(
			array(
				'fields'  => 'ids',
				'number'  => 1,
				'orderby' => 'comment_date_gmt',
				'order'   => 'DESC',
			)
		);

		return empty( $comments ) ? 0 : (int) reset( $comments );
	}

	/**
	 * Retrieves the available comment author fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			'name'       => array(
				'label'      => __( 'Name', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => $this->legacy_prefix . '_author',
			),
			'email'      => array(
				'label'      => __( 'Email', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => $this->legacy_prefix . '_author_email',
			),
			'website'    => array(
				'label'      => __( 'Website', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => $this->legacy_prefix . '_author_url',
			),
			'ip_address' => array(
				'label'      => __( 'IP address', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => $this->legacy_prefix . '_author_ip',
			),
			'user_id'    => array(
				'label'      => __( 'WordPress user ID', 'newsletter-optin-box' ),
				'type'       => 'number',
				'deprecated' => 'user_id',
			),
		);
	}

	/**
	 * Retrieves manual recipient options.
	 *
	 * @return array
	 */
	public function get_manual_recipients() {
		return array(
			$this->field_to_merge_tag( 'email' ) => $this->singular_label,
		);
	}
}
