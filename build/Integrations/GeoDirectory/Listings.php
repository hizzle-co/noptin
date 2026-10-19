<?php

namespace Hizzle\Noptin\Integrations\GeoDirectory;

defined( 'ABSPATH' ) || exit;

/**
 * Container for listings.
 */
class Listings extends \Hizzle\Noptin\Objects\Generic_Post_Type {

	/**
	 * Constructor.
	 *
	 * @param string $post_type
	 * @since 1.0.0
	 * @return string
	 */
	public function __construct( $post_type ) {
		$this->record_class = __NAMESPACE__ . '\Listing';
		$this->integration  = 'geodirectory';
		parent::__construct( $post_type, true );

		if ( is_string( $this->icon ) ) {
			$this->icon = array(
				'icon' => $this->icon,
				'fill' => '#ff8333',
			);
		}

		if ( defined( 'GEODIR_PRICING_VERSION' ) ) {
			add_action( 'geodir_pricing_post_downgraded', array( $this, 'post_downgraded' ), 10000, 3 );
			add_action( 'geodir_pricing_post_expired', array( $this, 'post_expired' ), 10000 );
			add_action( 'geodir_pricing_complete_package_post_updated', array( $this, 'post_paid' ), 10000, 3 );
		}
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		$action = 'create_or_update_' . $this->type;
		$fields = array_merge(
			parent::get_fields(),
			array(
				'featured'       => array(
					'label'   => __( 'Is Featured', 'newsletter-optin-box' ),
					'type'    => 'string',
					'options' => array(
						'1' => __( 'True', 'newsletter-optin-box' ),
						'0' => __( 'False', 'newsletter-optin-box' ),
					),
					'actions' => array( $action ),
				),
				'submit_ip'      => array(
					'label'   => __( 'Submit IP', 'newsletter-optin-box' ),
					'type'    => 'string',
					'actions' => array( $action ),
				),
				'overall_rating' => array(
					'label' => __( 'Overall Rating', 'newsletter-optin-box' ),
					'type'  => 'number',
				),
				'rating_count'   => array(
					'label' => __( 'Rating Count', 'newsletter-optin-box' ),
					'type'  => 'number',
				),
			)
		);

		$fields['status']['options'] = geodir_get_post_statuses();

		if ( isset( $fields[ "tax_{$this->type}_tags" ] ) ) {
			$fields[ "tax_{$this->type}_tags" ]['deprecated'] = 'post_tags';
		}

		if ( isset( $fields[ "tax_{$this->type}category" ] ) ) {
			$fields[ "tax_{$this->type}category" ]['deprecated'] = 'post_category';
		}

		foreach ( \GeoDir_Settings_Cpt_Cf::get_cpt_custom_fields( $this->type ) as $custom_field ) {

			// Skip default fields.
			if ( in_array( $custom_field->htmlvar_name, array( 'post_category', 'post_tags' ), true ) || isset( $fields[ str_replace( 'post_', '', $custom_field->htmlvar_name ) ] ) ) {
				continue;
			}

			// Address fields.
			if ( 'address' === $custom_field->field_type ) {
				$fields = array_merge(
					$fields,
					array(
						'street'    => array(
							'label'   => __( 'Street', 'newsletter-optin-box' ),
							'type'    => 'string',
							'actions' => array( $action ),
						),
						'street2'   => array(
							'label'   => __( 'Street2', 'newsletter-optin-box' ),
							'type'    => 'string',
							'actions' => array( $action ),
						),
						'city'      => array(
							'label'   => __( 'City', 'newsletter-optin-box' ),
							'type'    => 'string',
							'actions' => array( $action ),
						),
						'region'    => array(
							'label'   => __( 'Region', 'newsletter-optin-box' ),
							'type'    => 'string',
							'actions' => array( $action ),
						),
						'country'   => array(
							'label'   => __( 'Country', 'newsletter-optin-box' ),
							'type'    => 'string',
							'actions' => array( $action ),
						),
						'zip'       => array(
							'label'   => __( 'Zip', 'newsletter-optin-box' ),
							'type'    => 'string',
							'actions' => array( $action ),
						),
						'latitude'  => array(
							'label'   => __( 'Latitude', 'newsletter-optin-box' ),
							'type'    => 'string',
							'actions' => array( $action ),
						),
						'longitude' => array(
							'label'   => __( 'Longitude', 'newsletter-optin-box' ),
							'type'    => 'string',
							'actions' => array( $action ),
						),
						'mapview'   => array(
							'label'   => __( 'Map View', 'newsletter-optin-box' ),
							'type'    => 'string',
							'actions' => array( $action ),
						),
						'mapzoom'   => array(
							'label'   => __( 'Map Zoom', 'newsletter-optin-box' ),
							'type'    => 'string',
							'actions' => array( $action ),
						),
					)
				);

				continue;
			}

			$fields[ sanitize_key( $custom_field->htmlvar_name ) ] = array(
				'label'   => esc_html( $custom_field->admin_title ),
				'type'    => 'string',
				'actions' => array( $action ),
			);

			// Packages.
			if ( 'package_id' === $custom_field->htmlvar_name && function_exists( 'geodir_pricing_get_packages' ) ) {
				$fields[ sanitize_key( $custom_field->htmlvar_name ) ]['options'] = wp_list_pluck(
					geodir_pricing_get_packages(
						array(
							'status'    => 'all',
							'post_type' => $this->type,
						)
					),
					'name',
					'id'
				);

				$fields['package_name'] = array(
					'label' => __( 'Package Name', 'newsletter-optin-box' ),
					'type'  => 'string',
				);

				continue;
			}

			if ( isset( $custom_field->data_type ) && ( 'DECIMAL' === $custom_field->data_type || 'INT' === $custom_field->data_type ) ) {
				$fields[ sanitize_key( $custom_field->htmlvar_name ) ]['type'] = 'number';
			}

			if ( isset( $custom_field->field_type ) && 'checkbox' === $custom_field->field_type ) {
				$fields[ sanitize_key( $custom_field->htmlvar_name ) ]['options'] = array(
					'1' => __( 'True', 'newsletter-optin-box' ),
					'0' => __( 'False', 'newsletter-optin-box' ),
				);
			}
		}

		return apply_filters( 'noptin_post_type_known_custom_fields', $fields, $this->type );
	}

	/**
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {

		$triggers = array_merge(
			parent::get_triggers(),
			array(
				'geodir_save_' . sanitize_key( $this->type )   => array(
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Saved', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'When a %s is saved', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'subject'     => 'post_author',
					'extra_args'  => array(
						'saving_type' => array(
							'label'   => __( 'Saving Type', 'newsletter-optin-box' ),
							'options' => array(
								'new'    => __( 'New Listing', 'newsletter-optin-box' ),
								'update' => __( 'Update Listing', 'newsletter-optin-box' ),
							),
							'type'    => 'string',
						),
					),
				),
			)
		);

		if ( isset( $triggers[ $this->type . '_published' ] ) ) {
			$triggers[ $this->type . '_published' ]['alias'] = 'geodir_publish_' . sanitize_key( $this->type );
		}

		if ( defined( 'GEODIR_PRICING_VERSION' ) ) {
			$triggers[ 'geodir_downgraded_' . sanitize_key( $this->type ) ] = array(
				'label'       => sprintf(
					/* translators: %s: Object type label. */
					__( '%s > Downgraded', 'newsletter-optin-box' ),
					$this->singular_label
				),
				'description' => sprintf(
					/* translators: %s: Object type label. */
					__( 'When a %s is downgraded', 'newsletter-optin-box' ),
					strtolower( $this->singular_label )
				),
				'subject'     => 'post_author',
				'extra_args'  => array(
					'previous_package_id'   => array(
						'label' => __( 'ID', 'newsletter-optin-box' ),
						'type'  => 'number',
						'group' => __( 'Previous Package', 'newsletter-optin-box' ),
					),
					'previous_package_name' => array(
						'label' => __( 'Name', 'newsletter-optin-box' ),
						'type'  => 'string',
						'group' => __( 'Previous Package', 'newsletter-optin-box' ),
					),
				),
			);

			$triggers[ 'geodir_expire_' . sanitize_key( $this->type ) ] = array(
				'label'       => sprintf(
					/* translators: %s: Object type label. */
					__( '%s > Expires', 'newsletter-optin-box' ),
					$this->singular_label
				),
				'description' => sprintf(
					/* translators: %s: Object type label. */
					__( 'When a %s expires', 'newsletter-optin-box' ),
					strtolower( $this->singular_label )
				),
				'subject'     => 'post_author',
			);
		}

		return $triggers;
	}

	/**
	 * Fired after a post is inserted.
	 *
	 * @param int           $post_id     Post ID.
	 * @param \WP_Post      $post        Post object.
	 * @param bool          $update      Whether this is an existing post being updated.
	 * @param null|\WP_Post $post_before Null for new posts, the WP_Post object prior
	 *                                  to the update for updated posts.
	 */
	public function after_insert_post( $post_id, $post, $update, $post_before ) {

		// Abort if not our post type.
		if ( wp_is_post_revision( $post ) || $this->type !== $post->post_type ) {
			return;
		}

		parent::after_insert_post( $post_id, $post, $update, $post_before );

		$user = get_user_by( 'id', $post->post_author );

		if ( empty( $user ) ) {
			return;
		}

		$this->trigger(
			'geodir_save_' . sanitize_key( $this->type ),
			array(
				'email'      => $user->user_email,
				'object_id'  => $post->ID,
				'subject_id' => $post->post_author,
				'extra_args' => array(
					$this->type . '.saving_type' => $update ? 'update' : 'new',
				),
			)
		);
	}

	/**
	 * Fired after a post is downgraded.
	 *
	 * @param int           $post_id     Post ID.
	 * @param \WP_Post      $post        Post object.
	 * @param bool          $update      Whether this is an existing post being updated.
	 * @param null|\WP_Post $post_before Null for new posts, the WP_Post object prior
	 *                                  to the update for updated posts.
	 */
	public function post_downgraded( $gd_post, $downgrade_to, $package ) {

		$post = get_post( $gd_post->ID );

		// Abort if not our post type.
		if ( wp_is_post_revision( $post ) || $this->type !== $post->post_type ) {
			return;
		}

		$user = get_user_by( 'id', $post->post_author );

		if ( empty( $user ) ) {
			return;
		}

		$this->trigger(
			'geodir_downgraded_' . sanitize_key( $this->type ),
			array(
				'email'      => $user->user_email,
				'object_id'  => $post->ID,
				'subject_id' => $post->post_author,
				'extra_args' => array(
					$this->type . '.previous_package_name' => $package->name,
					$this->type . '.previous_package_id'   => $package->id,
				),
			)
		);
	}

	/**
	 * Fired after a post expires.
	 *
	 * @param int           $post_id     Post ID.
	 */
	public function post_expired( $gd_post ) {

		$post = get_post( $gd_post->ID );

		// Abort if not our post type.
		if ( wp_is_post_revision( $post ) || $this->type !== $post->post_type ) {
			return;
		}

		$user = get_user_by( 'id', $post->post_author );

		if ( empty( $user ) ) {
			return;
		}

		$this->trigger(
			'geodir_downgraded_' . sanitize_key( $this->type ),
			array(
				'email'      => $user->user_email,
				'object_id'  => $post->ID,
				'subject_id' => $post->post_author,
			)
		);
	}

	public function post_paid( $post_id, $package_id, $post_package_id ) {
		$post_package = \GeoDir_Pricing_Post_Package::get_item( (int) $post_package_id );

		if ( empty( $post_package ) ) {
			return;
		}

		$post = get_post( (int) $post_id );
		$task = \GeoDir_Pricing_Post_Package::get_task( $post_package_id );

		if ( empty( $task ) || empty( $post ) ) {
			return;
		}

		$user = get_user_by( 'id', $post->post_author );

		if ( empty( $user ) ) {
			return;
		}

		$this->trigger(
			'geodir_' . $task . '_' . sanitize_key( $this->type ),
			array(
				'email'      => $user->user_email,
				'object_id'  => $post->ID,
				'subject_id' => $post->post_author,
			)
		);
	}

	/**
	 * Retrieves a test object args.
	 *
	 * @since 3.0.0
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule
	 * @throws \Exception
	 * @return array
	 */
	public function get_test_args( $rule ) {
		$args = parent::get_test_args( $rule );

		if ( 'geodir_save_' . sanitize_key( $this->type ) === $rule->get_trigger_id() ) {
			$args['extra_args'] = array(
				$this->type . '.saving_type' => 'update',
			);
		}

		return $args;
	}

	/**
	 * Fetches post args.
	 *
	 * @param array $args
	 */
	protected function prepare_create_post_args( $args ) {
		$args = parent::prepare_create_post_args( $args );

		if ( empty( $args['meta_input'] ) ) {
			unset( $args['meta_input'] );
		}

		foreach ( \GeoDir_Settings_Cpt_Cf::get_cpt_custom_fields( $this->type ) as $custom_field ) {

			// Address fields.
			if ( 'address' === $custom_field->field_type ) {
				foreach ( array( 'street', 'street2', 'city', 'region', 'country', 'zip', 'latitude', 'longitude', 'mapview', 'mapzoom' ) as $address_field ) {
					if ( isset( $args['meta_input'][ $address_field ] ) ) {
						$args[ $address_field ] = $args['meta_input'][ $address_field ];
						unset( $args['meta_input'][ $address_field ] );
					}
				}

				continue;
			}

			if ( isset( $args['meta_input'][ $custom_field->htmlvar_name ] ) ) {
				$args[ $custom_field->htmlvar_name ] = $args['meta_input'][ $custom_field->htmlvar_name ];
				unset( $args['meta_input'][ $custom_field->htmlvar_name ] );
			}
		}

		return $args;
	}
}
