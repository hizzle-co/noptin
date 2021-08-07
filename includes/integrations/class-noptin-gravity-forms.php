<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

GFForms::include_feed_addon_framework();

/**
 * Handles integrations with Gravity Forms Forms
 *
 * @since       1.3.3
 */
class Noptin_Gravity_Forms extends GFFeedAddOn {

    /**
     * Contains an instance of this class, if available.
     */
    private static $_instance = null;

    /**
     * Defines the version of this addon.
     */
    protected $_version = '1.4.0';

    /**
     * Defines the minimum Gravity Forms version required.
     */
    protected $_min_gravityforms_version = '2.4.13';

    /**
     * Defines the plugin slug.
     */
    protected $_slug = 'noptin';

    /**
     * Defines the full path to this class file.
     */
    protected $_full_path = __FILE__;

    /**
     * Defines the URL where this add-on can be found.
     */
    protected $_url = 'https://noptin.com';

    /**
     * Defines the title of this add-on.
     */
    protected $_title = 'Noptin';

    /**
     * Defines the short title of the add-on.
     */
    protected $_short_title = 'Noptin';

    /**
     * Defines the capability needed to access the Add-On settings page.
     */
    protected $_capabilities_settings_page = 'manage_options';

    /**
     * Defines the capability needed to access the Add-On form settings page.
     */
    protected $_capabilities_form_settings = 'manage_options';

    /**
     * Defines the capability needed to uninstall the Add-On.
     */
    protected $_capabilities_uninstall = 'manage_options';

	/**
	 * Returns an instance of this feed.
	 */
    public static function get_instance() {

        if ( self::$_instance === null ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Feed starting point.
     */
    public function init() {
        parent::init();

        $this->add_delayed_payment_support(
            array(
                'option_label' => esc_html__( 'Subscribe to Noptin only when payment is received.', 'newsletter-optin-box' ),
            )
		);

    }

	/**
	 * Returns an array of map fields
	 */
	public function get_map_fields() {

		$map_fields = array(

			array(
                'name'       => 'email',
				'label'      => __( 'Subscriber Email', 'newsletter-optin-box' ),
				'required'   => 1,
				'field_type' => array( 'email', 'hidden' ),
			),

            array(
                'name'       => 'name',
				'label'      => __( 'Subscriber Name', 'newsletter-optin-box' ),
            ),

            array(
                'name'       => 'GDPR_consent',
				'label'      => __( 'GDPR Consent', 'newsletter-optin-box' ),
				'field_type' => array( 'consent' ),
			)

        );

        foreach ( get_noptin_custom_fields() as $custom_field ) {

            if ( ! $custom_field['predefined'] ) {
                $map_fields[] = array(
                    'name'    => $custom_field['merge_tag'],
                    'label'   => $custom_field['label'],
                );
            }

		}

		return apply_filters( 'noptin_gravity_forms_map_fields', $map_fields );

	}

    /**
     * Form settings page title
     *
     * @return string Form Settings Title
     */
    public function feed_settings_title() {
        return esc_html__('Feed Settings', 'newsletter-optin-box');
    }

    /**
     * Enable feed duplication.
     *
     * @param int $id Feed ID requesting duplication.
     *
     * @return bool
     */
    public function can_duplicate_feed( $id ) {
        return true;
    }

    /**
     * Feed settings
     *
     * @return array
     */
    public function feed_settings_fields() {

        $normal_settings = array(

			array(
				'label'   => __( 'Feed Name', 'newsletter-optin-box' ),
				'type'    => 'text',
				'name'    => 'feedName',
				'tooltip' => __( 'Provide feed name e.g "Newsletter Subscription"', 'newsletter-optin-box' ),
				'class'   => 'medium'
			),

			array (
				'name'           => 'optinCondition',
				'label'          => esc_html__( 'Opt-In Condition', 'newsletter-optin-box' ),
				'checkbox_label' => __( 'Enable Condition', 'newsletter-optin-box' ),
				'type'           => 'feed_condition',
				'instructions'   => __( 'Only add to Noptin if', 'newsletter-optin-box' )
			)

		);

		$map_settings = array(

			array(
				'name'      => 'noptinFields',
            	'label'     => esc_html__( 'Map Fields', 'newsletter-optin-box' ),
            	'type'      => 'field_map',
            	'field_map' => $this->get_map_fields()
			)

		);

		$fields = apply_filters(
			'noptin_gravity_forms_settings_fields',
			array(
				array(
					'fields' => apply_filters( 'noptin_gravity_forms_normal_settings_fields', $normal_settings ),
				),

				array(
					'fields' => apply_filters( 'noptin_gravity_forms_map_settings_fields', $map_settings ),
				)
			)
		);

		return $fields;
    }

    /**
     * Process the feed action.
     *
     * @param array $feed The feed object to be processed.
     * @param array $entry The entry object currently being processed.
     * @param array $form The form object currently being processed.
     *
     */
    public function process_feed( $feed, $entry, $form ) {

		// Map the submitted fields.
        $field_map = $this->get_field_map_fields( $feed, 'noptinFields' );

		// Prepare subscriber details.
		$subscriber = array(
			'_subscriber_via' => 'Gravity Forms',
			'conversion_page' => $entry['source_url'],
        );

        // Add the subscriber's IP address.
		$address = noptin_get_user_ip();
		if ( ! empty( $address ) && '::1' !== $address ) {
			$subscriber['ip_address'] = $address;
		}

		// Add mapped fields.
        foreach ( $field_map as $noptin => $gravity_forms ) {

			// If no field is mapped, skip it.
            if ( rgblank( $gravity_forms ) ) {
                continue;
			}

            $subscriber[ $noptin ] = $this->get_field_value( $form, $entry, $gravity_forms );
        }

		// We need an email.
		if ( empty( $subscriber['email'] ) ) {
			return;
		}

		// Add integration data.
		$subscriber['integration_data'] = compact( 'form', 'entry', 'feed' );

		// Filter the subscriber fields.
		$subscriber = apply_filters( 'noptin_gravity_forms_integration_new_subscriber_fields', $subscriber, $this );

		// Register the subscriber.
		add_noptin_subscriber( $subscriber );

    }

    /**
     * Configures which columns should be displayed on the feed list page.
     *
     * @return array
     */
    public function feed_list_columns() {
        return array(
            'feedName' => esc_html__( 'Name', 'newsletter-optin-box' ),
        );
    }
}
