<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a GeoDirectory listing is published.
 *
 * @since 1.9.0
 */
class Noptin_GeoDirectory_Listing_Published_Trigger extends Noptin_GeoDirectory_Listing_Saved_Trigger {

	/**
	 * Constructor.
	 *
	 * @since 1.9.0
	 * @param string $post_type The trigger's post type.
	 */
	public function __construct( $post_type ) {
		$this->post_type = $post_type;

		add_action( 'geodir_post_published', array( $this, 'init_publish_trigger' ), 10000, 2 );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'geodir_publish_' . sanitize_key( $this->post_type );
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		// translators: %s is the post type label.
		return sprintf( __( 'GeoDirectory > Pulish %s', 'newsletter-optin-box' ), geodir_post_type_singular_name( $this->post_type, true ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		// translators: %s is the post type label.
		return sprintf( __( 'When a %s is published', 'newsletter-optin-box' ), geodir_strtolower( geodir_post_type_singular_name( $this->post_type, true ) ) );
	}

	/**
     * Returns an array of known smart tags.
     *
     * @since 1.9.0
     * @return array
     */
    public function get_known_smart_tags() {

		$smart_tags = parent::get_known_smart_tags();
		unset( $smart_tags['saving_type'] );

		return $smart_tags;
    }

	/**
	 * Inits the trigger.
	 *
	 * @param object $gd_post The gd post data.
	 * @param array  $postarr The post info.
	 * @since 1.9.0
	 */
	public function init_publish_trigger( $gd_post, $postarr ) {

		$post = get_post( $gd_post->ID );

		// Abort if this is a post revision.
		if ( wp_is_post_revision( $post->ID ) || $post->post_type !== $this->post_type ) {
			return;
		}

		$this->trigger( get_userdata( $post->post_author ), $this->prepare_gd_args( $postarr, $gd_post, $post, true ) );
	}
}
