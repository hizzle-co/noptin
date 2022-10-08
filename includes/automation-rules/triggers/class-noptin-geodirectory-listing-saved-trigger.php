<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a GeoDirectory listing is saved.
 *
 * @since 1.8.3
 */
class Noptin_GeoDirectory_Listing_Saved_Trigger extends Noptin_Abstract_Trigger {

	/**
	 * @var string The trigger's post type.
	 */
	protected $post_type;

	/**
	 * Constructor.
	 *
	 * @since 1.8.3
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
     * @since 1.8.3
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
				),
				'author_id'         => array(
					'description'       => __( 'User ID (Post Author)', 'newsletter-optin-box' ),
					'conditional_logic' => 'number',
				),
				'author_email'      => array(
					'description'       => __( 'Email Address (Post Author)', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
				'author_name'       => array(
					'description'       => __( 'Name (Post Author)', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
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
				),
				'post_id'           => array(
					'description'       => __( 'Listing ID', 'newsletter-optin-box' ),
					'conditional_logic' => 'number',
				),
				'post_url'           => array(
					'description'       => __( 'Listing URL', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
				'post_status'       => array(
					'description'       => __( 'Listing Status', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
					'options'           => get_post_statuses(),
				),
				'post_date'         => array(
					'description'       => __( 'Listing Date', 'newsletter-optin-box' ),
					'conditional_logic' => 'date',
				),
				'featured'          => array(
					'description'       => __( 'Is Featured', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
					'options'           => array(
						'1' => __( 'True', 'newsletter-optin-box' ),
						'0' => __( 'False', 'newsletter-optin-box' ),
					),
				),
				'featured_image'    => array(
					'description'       => __( 'Featured Image', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
				'submit_ip'         => array(
					'description'       => __( 'Submit IP', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
				'overall_rating'    => array(
					'description'       => __( 'Overall Rating', 'newsletter-optin-box' ),
					'conditional_logic' => 'number',
				),
				'rating_count'      => array(
					'description'       => __( 'Rating Count', 'newsletter-optin-box' ),
					'conditional_logic' => 'number',
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
	 * @inheritdoc
	 */
	public function get_settings() {

		return array(

			'trigger_subject' => array(
				'type'        => 'text',
				'el'          => 'input',
				'label'       => __( 'Trigger Subject', 'newsletter-optin-box' ),
				'description' => sprintf(
					'%s %s',
					__( 'This trigger will fire for the email address that you specify here. ', 'newsletter-optin-box' ),
					sprintf(
						/* translators: %1: Opening link, %2 closing link tag. */
						esc_html__( 'You can use %1$s smart tags %2$s to provide a dynamic value.', 'newsletter-optin-box' ),
						'<a href="#TB_inline?width=0&height=550&inlineId=noptin-automation-rule-smart-tags" class="thickbox">',
						'</a>'
					)
				),
				'default'     => '[[author_email]]',
			),

		);

	}

	/**
	 * Inits the trigger.
	 *
	 * @param array $postarr The post info.
	 * @param object $gd_post The gd post data.
	 * @param WP_Post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 * @since 1.8.3
	 */
	public function init_trigger( $postarr, $gd_post, $post, $update ) {

		// Abort if this is a post revision.
		if ( wp_is_post_revision( $post->ID ) ) {
			return;
		}

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
		}

		// Featured image.
		if ( isset( $gd_post->featured_image ) && $gd_post->featured_image ) {
			$upload_dir                = wp_upload_dir( null, false );
			$postarr['featured_image'] = $upload_dir['baseurl'] . $gd_post->featured_image;
		}

		$postarr = array_replace( (array) $gd_post, $postarr );

		$this->trigger( $listing_owner, $postarr );
	}

	/**
     * Triggers action callbacks.
     *
     * @since 1.8.3
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

            // Ensure that the rule is valid for the provided args.
            if ( $this->is_rule_valid_for_args( $rule, $args, $args['email'], $action ) ) {
                $action->maybe_run( $args['email'], $rule, $args );
            }
        }

    }
}
