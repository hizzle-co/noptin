<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Create / update a GeoDirectory listing.
 *
 * @since 1.11.3
 */
class Noptin_GeoDirectory_Update_Listing_Action extends Noptin_Abstract_Action {

	/**
	 * @var string The trigger's post type.
	 */
	protected $post_type;

	/**
	 * @var string
	 */
	public $category = 'GeoDirectory';

	/**
	 * @var string
	 */
	public $integration = 'geodirectory';

	/**
	 * Constructor.
	 *
	 * @since 1.9.0
	 * @param string $post_type The trigger's post type.
	 */
	public function __construct( $post_type ) {
		$this->post_type = $post_type;
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'geodir_update_' . sanitize_key( $this->post_type );
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		// translators: %s is the post type label.
		return sprintf( __( 'GeoDirectory > Create or update %s', 'newsletter-optin-box' ), geodir_post_type_singular_name( $this->post_type, true ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		// translators: %s is the post type label.
		return sprintf( __( 'Create or update %s', 'newsletter-optin-box' ), geodir_strtolower( geodir_post_type_singular_name( $this->post_type, true ) ) );
	}

	/**
	 * Returns custom fields.
	 *
	 * @return array
	 */
	public function get_listing_fields() {

		$fields = array(
			'noptin_author_id'     => __( 'User ID (Post Author)', 'newsletter-optin-box' ),
			'noptin_author_email'  => __( 'Email Address (Post Author)', 'newsletter-optin-box' ),
			'noptin_author_name'   => __( 'Name (Post Author)', 'newsletter-optin-box' ),
			'noptin_post_id'       => __( 'Listing ID', 'newsletter-optin-box' ),
			'noptin_post_status'   => __( 'Listing Status', 'newsletter-optin-box' ),
			'noptin_post_category' => __( 'Listing Categories', 'newsletter-optin-box' ),
			'default_category'     => __( 'Default Category', 'newsletter-optin-box' ),
		);

		foreach ( GeoDir_Settings_Cpt_Cf::get_cpt_custom_fields( $this->post_type ) as $custom_field ) {

			// Skip post content.
			if ( 'post_category' === $custom_field->htmlvar_name || 'fieldset' === $custom_field->field_type ) {
				continue;
			}

			// Address fields.
			if ( 'address' === $custom_field->field_type ) {
				$fields = array_merge(
					$fields,
					array(
						'street'    => __( 'Street', 'newsletter-optin-box' ),
						'street2'   => __( 'Street2', 'newsletter-optin-box' ),
						'city'      => __( 'City', 'newsletter-optin-box' ),
						'region'    => __( 'Region', 'newsletter-optin-box' ),
						'country'   => __( 'Country', 'newsletter-optin-box' ),
						'zip'       => __( 'Zip', 'newsletter-optin-box' ),
						'latitude'  => __( 'Latitude', 'newsletter-optin-box' ),
						'longitude' => __( 'Longitude', 'newsletter-optin-box' ),
						'mapview'   => __( 'Map View', 'newsletter-optin-box' ),
						'mapzoom'   => __( 'Map Zoom', 'newsletter-optin-box' ),
					)
				);

				continue;
			}

			// Checkboxes.
			if ( isset( $custom_field->field_type ) && 'checkbox' === $custom_field->field_type ) {
				$fields[ sanitize_key( $custom_field->htmlvar_name ) ] = sprintf(
					'%s (1/0)',
					esc_html( $custom_field->admin_title )
				);
			} else {
				$fields[ sanitize_key( $custom_field->htmlvar_name ) ] = esc_html( $custom_field->admin_title );
			}
		}

		return $fields;
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		$settings = array(
			'noptin_description' => array(
				'el'      => 'paragraph',
				'content' => __( 'This action will only update the mapped fields.', 'newsletter-optin-box' ),
			),
		);

		foreach ( $this->get_listing_fields() as $field => $label ) {

			$settings[ $field ] = array(
				'el'          => 'input',
				'label'       => $label,
				'placeholder' => 'noptin_post_id' === $field ? __( 'Leave blank to create a new listing.', 'newsletter-optin-box' ) : __( 'Leave blank to ignore.', 'newsletter-optin-box' ),
			);
		}

		return $settings;
	}

	/**
	 * Deactivates the subscriber.
	 *
	 * @since 1.3.1
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function run( $subject, $rule, $args ) {

		$settings = wp_unslash( $rule->action_settings );
		$details  = array();
		$post     = array();

		/** @var Noptin_Automation_Rules_Smart_Tags $smart_tags */
		$smart_tags = $args['smart_tags'];

		foreach ( array_keys( $this->get_listing_fields() ) as $field_key ) {

			if ( ! isset( $settings[ $field_key ] ) || '' === $settings[ $field_key ] ) {
				continue;
			}

			$value = $smart_tags->replace_in_content( $settings[ $field_key ] );

			if ( 'noptin_post_id' === $field_key && ! empty( $value ) ) {
				$post = get_post( $value );

				// If the post doesn't exist, abort.
				if ( empty( $post ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
					return false;
				}
			}

			$details[ $field_key ] = $value;
		}

		$post_id = $this->save_post( $details );

		if ( is_wp_error( $post_id ) ) {
			noptin_error_log( $post_id->get_error_message() );
		}

		return $post_id;
	}

	/**
	 * Prepares a single post for create or update.
	 *
	 * @since 1.11.0
	 *
	 * @param array $args Request object.
	 * @return int|WP_Error Post object or WP_Error.
	 */
	protected function save_post( $args ) {
		$prepared = array();
		$author   = array();

		foreach ( $args as $key => $value ) {
			if ( is_null( $value ) || '' === $value ) {
				continue;
			}

			switch ( $key ) {
				case 'noptin_author_id':
					$prepared['post_author'] = (int) $value;
					break;
				case 'noptin_author_email':
					$author['email'] = $value;
					break;
				case 'noptin_author_name':
					$author['name'] = $value;
					break;
				case 'noptin_post_id':
					$prepared['ID'] = (int) $value;
					break;
				case 'noptin_post_status':
					$key              = str_replace( 'noptin_', '', $key );
					$prepared[ $key ] = $value;
					break;
				case 'post_tags':
					$prepared['tax_input'] = isset( $prepared['tax_input'] ) ? $prepared['tax_input'] : array();

					$prepared['tax_input'][ $this->post_type . '_tags' ] = noptin_parse_list( $value, true );
					break;
				case 'noptin_post_category':
					// WordPress expects IDs.
					$categories    = noptin_parse_list( $value, true );
					$prepared_cats = array();

					foreach ( $categories as $category ) {
						if ( is_numeric( $category ) ) {
							$prepared_cats[] = (int) $category;
						} else {
							$term = get_term_by( 'name', $category, $this->post_type . 'category' );

							if ( $term ) {
								$prepared_cats[] = (int) $term->term_id;
							}
						}
					}

					$prepared['tax_input'] = isset( $prepared['tax_input'] ) ? $prepared['tax_input'] : array();

					$prepared['tax_input'][ $this->post_type . 'category' ] = $prepared_cats;
					break;
				default:
					$prepared[ $key ] = $value;
					break;
			}
		}

		// Create or update the author.
		if ( empty( $prepared['post_author'] ) && ! empty( $author['email'] ) ) {
			if ( email_exists( $author['email'] ) ) {
				$prepared['post_author'] = email_exists( $author['email'] );
			} else {
				$user = wp_insert_user(
					array(
						'user_email'   => $author['email'],
						'user_login'   => $author['email'],
						'user_pass'    => wp_generate_password(),
						'display_name' => empty( $author['name'] ) ? strtok( $author['email'], '@' ) : $author['name'],
					)
				);

				if ( ! is_wp_error( $user ) ) {
					$prepared['post_author'] = $user;
				}
			}
		}

		$prepared['post_type'] = $this->post_type;

		// Slash data.
		$prepared = wp_slash( $prepared );

		// Create new post.
		if ( empty( $prepared['ID'] ) ) {
			return wp_insert_post( $prepared, true );
		}

		return wp_update_post( $prepared, true );
	}
}
