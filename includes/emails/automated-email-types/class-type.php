<?php
/**
 * Emails API: Automated Email Type.
 *
 * Container for a single automated email type.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Container for a single automated email type.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
abstract class Noptin_Automated_Email_Type {

	/**
	 * @var string
	 */
	public $type;

	/**
	 * Object this email is for, for example a customer, product, or subscriber.
	 *
	 * @var object|bool
	 */
	public $object;

	/**
	 * True when email is being sent.
	 *
	 * @var bool
	 */
	public $sending;

	/**
	 * Retrieves the automated email type name.
	 *
	 */
	abstract public function get_name();

	/**
	 * Retrieves the automated email type description.
	 *
	 */
	abstract public function get_description();

	/**
	 * Retrieves the automated email type image.
	 *
	 */
	abstract public function the_image();

	/**
	 * Registers relevant hooks.
	 *
	 */
	public function add_hooks() {

		add_filter( 'noptin_get_automated_email_prop', array( $this, 'maybe_set_default' ), 10, 3 );
		add_filter( "noptin_default_automated_email_{$this->type}_recipient", array( $this, 'get_default_recipient' ) );

		if ( is_callable( array( $this, 'render_metabox' ) ) ) {
			add_filter( "noptin_automated_email_{$this->type}_options", array( $this, 'render_metabox' ) );
		}

		if ( is_callable( array( $this, 'about_automation' ) ) ) {
			add_filter( "noptin_automation_table_about_{$this->type}", array( $this, 'about_automation' ), 10, 2 );
		}

	}

	/**
	 * Sets the default value for a given email type's value.
	 *
	 * @param mixed $value
	 * @param string $prop
	 * @param Noptin_Automated_Email $email
	 */
	public function maybe_set_default( $value, $prop, $email ) {

		// Abort if the email is saved or is not our type.
		if ( ! empty( $value ) || $email->exists() || $email->type !== $this->type ) {
			return $value;
		}

		// Set default template, permission and footer texts.
		switch ( $prop ) {

			case 'name':
				$value = $this->get_name();
				break;

			case 'footer_text':
				$value = get_noptin_footer_text();
				break;

			case 'permission_text':
				$value = get_noptin_permission_text();
				break;

			case 'template':
				$value = get_noptin_option( 'email_template',  'plain' );
				break;
		}

		// Is there a custom method to filter this prop?
		$method = sanitize_key( "default_$prop" );
		if ( is_callable( array( $this, $method ) ) ) {
			$value = $this->$method();
		}

		// Apply email type specific filter then return.
		return apply_filters( "noptin_{$this->type}_default_$prop", $value );

	}

	/**
	 * Returns the default recipient.
	 *
	 */
	public function get_default_recipient() {
		return '';
	}

	/**
	 * Returns the URL to create a new campaign.
	 *
	 */
	public function new_campaign_url() {
		return add_query_arg( 'campaign', urlencode( $this->type ), admin_url( 'admin.php?page=noptin-email-campaigns&section=automations&sub_section=edit_campaign' ) );
	}

	/**
	 * Returns an array of all published automated emails.
	 *
	 * @return Noptin_Automated_Email[]
	 */
	public function get_automations() {

		$emails = array();
		$args   = array(
			'numberposts'            => -1,
			'post_type'              => 'noptin-campaign',
			'orderby'                => 'menu_order',
			'order'                  => 'ASC',
			'suppress_filters'       => true, // DO NOT allow WPML to modify the query
			'cache_results'          => true,
			'update_post_term_cache' => false,
			'post_status'            => array( 'publish' ),
			'meta_query'             => array(
				array(
					'key'   => 'campaign_type',
					'value' => 'automation',
				),
				array(
					'key'   => 'automation_type',
					'value' => $this->type,
				),
			),
		);

		foreach ( get_posts( $args ) as $post ) {noptin_dump( $post );
			$emails[] = new Noptin_Automated_Email( $post->ID );
		}

		return $emails;

	}

}
