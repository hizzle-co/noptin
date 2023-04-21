<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a GeoDirectory listing is saved.
 *
 * @since 1.9.0
 */
class Noptin_GeoDirectory_Listing_Saved_Trigger extends Noptin_Abstract_Trigger {

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

		add_action( 'geodir_post_saved', array( $this, 'init_trigger' ), 10000, 4 );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'geodir_save_' . sanitize_key( $this->post_type );
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		// translators: %s is the post type label.
		return sprintf( __( 'GeoDirectory > Save %s', 'newsletter-optin-box' ), geodir_post_type_singular_name( $this->post_type, true ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		// translators: %s is the post type label.
		return sprintf( __( 'When a %s is saved', 'newsletter-optin-box' ), geodir_strtolower( geodir_post_type_singular_name( $this->post_type, true ) ) );
	}

	/**
     * Returns an array of known smart tags.
     *
     * @since 1.9.0
     * @return array
     */
    public function get_known_smart_tags() {

		$smart_tags = array_merge(
			parent::get_known_smart_tags(),
			array(
				'saving_type'       => array(
					'description'       => __( 'Saving Type', 'newsletter-optin-box' ),
					'options'           => array(
						'new'    => __( 'New Listing', 'newsletter-optin-box' ),
						'update' => __( 'Update Listing', 'newsletter-optin-box' ),
					),
					'conditional_logic' => 'string',
					'example'           => 'saving_type',
				),
				'author_id'         => array(
					'description'       => __( 'User ID (Post Author)', 'newsletter-optin-box' ),
					'conditional_logic' => 'number',
					'example'           => 'author_id',
				),
				'author_email'      => array(
					'description'       => __( 'Email Address (Post Author)', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
					'example'           => 'author_email',
				),
				'author_name'       => array(
					'description'       => __( 'Name (Post Author)', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
					'example'           => 'author_name',
				),
				'author_first_name' => array(
					'description'       => __( 'First Name (Post Author)', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
				'author_last_name'  => array(
					'description'       => __( 'Last Name (Post Author)', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
				'author_login'      => array(
					'description'       => __( 'Login Name (Post Author)', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
					'example'           => 'author_login',
				),
				'post_id'           => array(
					'description'       => __( 'Listing ID', 'newsletter-optin-box' ),
					'conditional_logic' => 'number',
					'example'           => 'post_id',
				),
				'post_url'          => array(
					'description'       => __( 'Listing URL', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
					'example'           => 'post_url',
				),
				'post_status'       => array(
					'description'       => __( 'Listing Status', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
					'options'           => get_post_statuses(),
					'example'           => 'post_status',
				),
				'post_date'         => array(
					'description'       => __( 'Listing Date', 'newsletter-optin-box' ),
					'conditional_logic' => 'date',
					'example'           => 'post_date',
				),
				'featured'          => array(
					'description'       => __( 'Is Featured', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
					'options'           => array(
						'1' => __( 'True', 'newsletter-optin-box' ),
						'0' => __( 'False', 'newsletter-optin-box' ),
					),
					'example'           => 'featured',
				),
				'featured_image'    => array(
					'description'       => __( 'Featured Image', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
					'example'           => 'featured_image',
				),
				'submit_ip'         => array(
					'description'       => __( 'Submit IP', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
					'example'           => 'submit_ip',
				),
				'overall_rating'    => array(
					'description'       => __( 'Overall Rating', 'newsletter-optin-box' ),
					'conditional_logic' => 'number',
					'example'           => 'overall_rating',
				),
				'rating_count'      => array(
					'description'       => __( 'Rating Count', 'newsletter-optin-box' ),
					'conditional_logic' => 'number',
					'example'           => 'rating_count',
				),
			)
		);

		foreach ( GeoDir_Settings_Cpt_Cf::get_cpt_custom_fields( $this->post_type ) as $custom_field ) {

			// Skip post content.
			if ( 'post_content' === $custom_field->htmlvar_name ) {
				continue;
			}

			// Address fields.
			if ( 'address' === $custom_field->field_type ) {
				$smart_tags = array_merge(
					$smart_tags,
					array(
						'street'    => array(
							'description'       => __( 'Street', 'newsletter-optin-box' ),
							'conditional_logic' => 'string',
						),
						'street2'   => array(
							'description'       => __( 'Street2', 'newsletter-optin-box' ),
							'conditional_logic' => 'string',
						),
						'city'      => array(
							'description'       => __( 'City', 'newsletter-optin-box' ),
							'conditional_logic' => 'string',
						),
						'region'    => array(
							'description'       => __( 'Region', 'newsletter-optin-box' ),
							'conditional_logic' => 'string',
						),
						'country'   => array(
							'description'       => __( 'Country', 'newsletter-optin-box' ),
							'conditional_logic' => 'string',
						),
						'zip'       => array(
							'description'       => __( 'Zip', 'newsletter-optin-box' ),
							'conditional_logic' => 'string',
						),
						'latitude'  => array(
							'description'       => __( 'Latitude', 'newsletter-optin-box' ),
							'conditional_logic' => 'string',
						),
						'longitude' => array(
							'description'       => __( 'Longitude', 'newsletter-optin-box' ),
							'conditional_logic' => 'string',
						),
						'mapview'   => array(
							'description'       => __( 'Map View', 'newsletter-optin-box' ),
							'conditional_logic' => 'string',
						),
						'mapzoom'   => array(
							'description'       => __( 'Map Zoom', 'newsletter-optin-box' ),
							'conditional_logic' => 'string',
						),
					)
				);

				continue;
			}

			$smart_tags[ sanitize_key( $custom_field->htmlvar_name ) ] = array(
				'description'       => esc_html( $custom_field->admin_title ),
				'conditional_logic' => 'string',
			);

			// Packages.
			if ( 'package_id' === $custom_field->htmlvar_name && function_exists( 'geodir_pricing_get_packages' ) ) {
				$smart_tags[ sanitize_key( $custom_field->htmlvar_name ) ]['options'] = wp_list_pluck(
					geodir_pricing_get_packages(
						array(
							'status'    => 'all',
							'post_type' => $this->post_type,
						)
					),
					'name',
					'id'
				);

				$smart_tags['package_name'] = array(
					'description'       => __( 'Package Name', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				);

				continue;
			}

			if ( isset( $custom_field->data_type ) && ( 'DECIMAL' === $custom_field->data_type || 'INT' === $custom_field->data_type ) ) {
				$smart_tags[ sanitize_key( $custom_field->htmlvar_name ) ]['conditional_logic'] = 'number';
			}

			if ( isset( $custom_field->field_type ) && 'checkbox' === $custom_field->field_type ) {
				$smart_tags[ sanitize_key( $custom_field->htmlvar_name ) ]['options'] = array(
					'1' => __( 'True', 'newsletter-optin-box' ),
					'0' => __( 'False', 'newsletter-optin-box' ),
				);
			}
		}

		return $smart_tags;
    }

	/**
	 * Retrieves trigger args.
	 *
	 * @param array $postarr The post info.
	 * @param object $gd_post The gd post data.
	 * @param WP_Post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 * @since 1.9.0
	 */
	public function prepare_gd_args( $postarr, $gd_post, $post, $update ) {

		$postarr['post_id']     = $post->ID;
		$postarr['saving_type'] = $update ? 'update' : 'new';
		$postarr['post_url']    = get_permalink( $post->ID );

		// Add listing author info.
		$listing_owner = get_userdata( $post->post_author );

		// Add listing owner details.
		if ( ! empty( $listing_owner ) ) {
			$postarr['author_id']         = $listing_owner->ID;
			$postarr['author_email']      = $listing_owner->user_email;
			$postarr['author_name']       = $listing_owner->display_name;
			$postarr['author_first_name'] = $listing_owner->user_firstname;
			$postarr['author_last_name']  = $listing_owner->user_lastname;
			$postarr['author_login']      = $listing_owner->user_login;

			if ( empty( $postarr['email'] ) ) {
				$postarr['email'] = $listing_owner->user_email;
			}
		}

		// Featured image.
		if ( isset( $gd_post->featured_image ) && $gd_post->featured_image ) {
			$upload_dir                = wp_upload_dir( null, false );
			$postarr['featured_image'] = $upload_dir['baseurl'] . $gd_post->featured_image;
		}

		$postarr = array_replace( (array) $gd_post, $postarr );

		$prepared = array();

		foreach ( $postarr as $key => $value ) {

			if ( is_array( $value ) ) {

				if ( ! is_scalar( current( $value ) ) ) {
					$value = wp_json_encode( $value );
				} else {
					$value = implode( ', ', $value );
				}
			}

			if ( is_email( $value ) && empty( $prepared['email'] ) ) {
				$prepared['email'] = sanitize_email( $value );
			}

			$prepared[ $key ] = $value;
		}

		// Packages.
		if ( isset( $prepared['package_id'] ) && function_exists( 'geodir_pricing_package_name' ) ) {
			$prepared['package_name'] = geodir_pricing_package_name( $postarr['package_id'] );
		}

		return $prepared;
	}

	/**
	 * Inits the trigger.
	 *
	 * @param array $postarr The post info.
	 * @param object $gd_post The gd post data.
	 * @param WP_Post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 * @since 1.9.0
	 */
	public function init_trigger( $postarr, $gd_post, $post, $update ) {

		// Abort if this is a post revision.
		if ( wp_is_post_revision( $post->ID ) || $post->post_type !== $this->post_type ) {
			return;
		}

		$this->trigger( get_userdata( $post->post_author ), $this->prepare_gd_args( $postarr, $gd_post, $post, $update ) );
	}

	/**
     * Triggers action callbacks.
     *
     * @since 1.9.0
     * @param mixed $subject The subject.
     * @param array $args Extra arguments passed to the action.
     * @return void
     */
    public function trigger( $subject, $args ) {

        $args['subject'] = $subject;

        $args = apply_filters( 'noptin_automation_trigger_args', $args, $this );

        $args['smart_tags'] = new Noptin_Automation_Rules_Smart_Tags( $this, $subject, $args );

        foreach ( $this->get_rules() as $rule ) {

            // Retrieve the action.
            $action = noptin()->automation_rules->get_action( $rule->action_id );
            if ( empty( $action ) ) {
                continue;
            }

            // Prepare the rule.
            $rule = noptin()->automation_rules->prepare_rule( $rule );

			// If we're not sending an email...
			if ( 'email' !== $rule->action_id ) {

				// Maybe use default trigger subject.
				if ( empty( $rule->trigger_settings['trigger_subject'] ) && ! empty( $args['email'] ) ) {
					$rule->trigger_settings['trigger_subject'] = $args['email'];
				}

				// Abort if no valid trigger subject.
				if ( empty( $rule->trigger_settings['trigger_subject'] ) ) {
					continue;
				}

				// Maybe process merge tags.
				$trigger_subject = $args['smart_tags']->replace_in_email( $rule->trigger_settings['trigger_subject'] );

				// Abort if not an email.
				if ( ! is_email( $trigger_subject ) ) {
					continue;
				}

				$args['email'] = $trigger_subject;
			}

			// Set the current email.
			$GLOBALS['current_noptin_email'] = $this->get_subject_email( $subject, $rule, $args );

			// Are we delaying the action?
			$delay = $rule->get_delay();

			if ( $delay > 0 ) {
				do_action( 'noptin_delay_automation_rule_execution', $rule, $args, $delay );
				continue;
			}

            // Ensure that the rule is valid for the provided args.
            if ( $this->is_rule_valid_for_args( $rule, $args, $args['email'], $action ) ) {
            	$action->maybe_run( $args['email'], $rule, $args );
            }
        }

    }

	/**
	 * Prepares email test data.
	 *
	 * @since 1.11.0
	 * @param Noptin_Automation_Rule $rule
	 * @return Noptin_Automation_Rules_Smart_Tags
	 * @throws Exception
	 */
	public function get_test_smart_tags( $rule ) {

		// Fetch post from the post type.
		$post = get_posts(
			array(
				'post_type'      => $this->post_type,
				'posts_per_page' => 1,
				'orderby'        => 'rand',
			)
		);

		if ( empty( $post ) ) {
			throw new Exception( __( 'No test data available for this trigger.', 'newsletter-optin-box' ) );
		}

		$post    = array_shift( $post );
		$gd_post = geodir_get_post_info( $post->ID );

		if ( empty( $gd_post ) ) {
			throw new Exception( __( 'No test data available for this trigger.', 'newsletter-optin-box' ) );
		}

		$args = $this->prepare_trigger_args(
			get_userdata( $post->post_author ),
			$this->prepare_gd_args( $post->to_array(), $gd_post, $post, true )
		);

		return $args['smart_tags'];
	}

	/**
	 * Serializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return false|array
	 */
	public function serialize_trigger_args( $args ) {
		return array(
            'post_id'     => $args['post_id'],
            'saving_type' => $args['saving_type'],
        );
	}

	/**
	 * Unserializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return array|false
	 */
	public function unserialize_trigger_args( $args ) {

        $post        = get_post( $args['user_id'] );
		$saving_type = $args['saving_type'];

		if ( empty( $post ) ) {
			throw new Exception( 'The post no longer exists' );
		}

		$gd_post = geodir_get_post_info( $post->ID );

		if ( empty( $gd_post ) ) {
			throw new Exception( 'The GD post info no longer exists' );
		}

		$this->prepare_trigger_args(
			get_userdata( $post->post_author ),
			$this->prepare_gd_args( $post->to_array(), $gd_post, $post, 'update' === $saving_type )
		);
	}
}
