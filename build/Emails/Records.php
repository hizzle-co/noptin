<?php

namespace Hizzle\Noptin\Emails;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a Noptin emails.
 *
 * @since 3.0.0
 */
class Records extends \Hizzle\Noptin\Objects\Collection {

	/**
	 * @var string the record class.
	 */
	public $record_class = '\Hizzle\Noptin\Emails\Record';

	/**
	 * @var string integration.
	 */
	public $integration = 'noptin';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function __construct() {
		$this->label             = __( 'Email Campaigns', 'newsletter-optin-box' );
		$this->singular_label    = __( 'Email Campaign', 'newsletter-optin-box' );
		$this->type              = 'noptin-campaign';
		$this->smart_tags_prefix = 'campaign';
		$this->can_list          = false;
		$this->icon              = array(
			'icon' => 'email',
			'fill' => '#008000',
		);

		parent::__construct();
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		return array(
			'id'                  => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'name'                => array(
				'label'      => __( 'Name', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'campaign_title',
			),
			'subject'             => array(
				'label' => __( 'Subject', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'preview_url'         => array(
				'label' => __( 'Preview URL', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'edit_url'            => array(
				'label' => __( 'Edit URL', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'view_in_browser_url' => array(
				'label' => __( 'View in browser', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'send_count'          => array(
				'label' => __( 'Sends', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'open_count'          => array(
				'label' => __( 'Opens', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'click_count'         => array(
				'label' => __( 'Clicks', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'unsubscribe_count'   => array(
				'label' => __( 'Unsubscribed', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'created'             => array(
				'label' => __( 'Created', 'newsletter-optin-box' ),
				'type'  => 'date',
			),
		);
	}
}
