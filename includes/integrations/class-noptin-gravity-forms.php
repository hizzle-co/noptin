<?php

// phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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

        if ( null === self::$_instance ) {
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

        // Load automation rule.
		if ( did_action( 'noptin_automation_rules_load' ) ) {
			$this->load_automation_rule( noptin()->automation_rules );
		} else {
			add_action( 'noptin_automation_rules_load', array( $this, 'load_automation_rule' ) );
		}
		add_filter( 'noptin_gravity_forms_forms', array( $this, 'filter_forms' ) );
    }

    /**
	 * Loads our automation rule.
	 *
	 * @param Noptin_Automation_Rules $rules The automation rules instance.
	 */
	public function load_automation_rule( $rules ) {
		$rules->add_trigger( new Noptin_Form_Submit_Trigger( 'gravity_forms', 'Gravity Forms' ) );
	}

    /**
     * Prepares form fields.
     *
     * @param GF_Field[] $fields The form fields.
     * @param string $return Either array or id.
     * @return array
     */
    public function prepare_noptin_automation_rule_fields( $fields, $return = 'array' ) {

        $prepared_fields = array();

        $static_fields = array(
            'source_url' => array(
                'description'       => __( 'Source URL', 'newsletter-optin-box' ),
                'conditional_logic' => 'string',
            ),
            'ip'         => array(
                'description'       => __( 'IP Address', 'newsletter-optin-box' ),
                'conditional_logic' => 'string',
            ),
            'currency'   => array(
                'description'       => __( 'Currency', 'newsletter-optin-box' ),
                'conditional_logic' => 'string',
            ),
            'user_agent' => array(
                'description'       => __( 'User Agent', 'newsletter-optin-box' ),
                'conditional_logic' => 'string',
            ),
        );

        foreach ( $static_fields as $key => $field ) {
            if ( 'id' === $return ) {
                $prepared_fields[ $key ] = $key;
            } else {
                $prepared_fields[ $key ] = $field;
            }
        }

        // Loop through all fields.
        foreach ( $fields as $gravity_field ) {

            // Skip fields with no name.
            if ( empty( $gravity_field->label ) ) {
                continue;
            }

            $key = sanitize_title( $gravity_field->label );

            if ( 'id' === $return ) {
                $prepared_fields[ $key ] = $gravity_field->id;
            } else {
                $field = array(
                    'description'       => $gravity_field->label,
                    'conditional_logic' => 'number' === $gravity_field->type ? 'number' : 'string',
                );

                if ( ! empty( $gravity_field->choices ) && is_array( $gravity_field->choices ) ) {
                    $field['options'] = array_combine( wp_list_pluck( $gravity_field->choices, 'value' ), wp_list_pluck( $gravity_field->choices, 'text' ) );
                }

                $prepared_fields[ $key ] = $field;
            }

            // Fields with multiple inputs.
            if ( is_array( $gravity_field->inputs ) ) {

                foreach ( $gravity_field->inputs as $input ) {

                    $input_key = $key . '_' . sanitize_title( $input['label'] );

                    if ( 'id' === $return ) {
                        $prepared_fields[ $input_key ] = $input['id'];
                        continue;
                    }

                    $field = array(
                        'description'       => $gravity_field->label . ' - ' . $input['label'],
                        'conditional_logic' => 'string',
                    );

                    if ( ! empty( $input['choices'] ) && is_array( $input['choices'] ) ) {
                        $field['options'] = array_combine( wp_list_pluck( $gravity_field->choices, 'value' ), wp_list_pluck( $gravity_field->choices, 'text' ) );
                    }

                    $prepared_fields[ $input_key ] = $field;
                }
            }
        }

        return $prepared_fields;
    }

    /**
	 * Filters forms.
	 *
	 * @param array $forms An array of forms.
	 * @return array
	 */
	public function filter_forms( $forms ) {
		global $noptin_gravity_forms_forms;

		// Return cached forms.
		if ( is_array( $noptin_gravity_forms_forms ) ) {
			return array_replace( $forms, $noptin_gravity_forms_forms );
		}

		$noptin_gravity_forms_forms = array();

		$all_forms = array_filter( GFAPI::get_forms() );

		// Loop through all forms.
		foreach ( $all_forms as $form ) {
			$noptin_gravity_forms_forms[ $form['id'] ] = array(
				'name'   => $form['title'],
				'fields' => $this->prepare_noptin_automation_rule_fields( $form['fields'] ),
			);
		}

		return array_replace( $forms, $noptin_gravity_forms_forms );
	}

    /**
	 * Determines what feeds need to be processed for the provided entry.
	 *
	 * @access public
	 * @param array $entry The Entry Object currently being processed.
	 * @param array $form The Form Object currently being processed.
	 *
	 * @return array $entry
	 */
	public function maybe_process_feed( $entry, $form ) {

        $form_fields = $this->prepare_noptin_automation_rule_fields( $form['fields'], 'id' );
        $posted      = array();

        foreach ( $form_fields as $key => $field_id ) {
            $posted[ $key ] = $this->get_field_value( $form, $entry, $field_id );
        }

        do_action( 'noptin_gravity_forms_form_submitted', $form['id'], $posted );

		return parent::maybe_process_feed( $entry, $form );
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
                'name'  => 'name',
				'label' => __( 'Subscriber Name', 'newsletter-optin-box' ),
            ),

            array(
                'name'       => 'GDPR_consent',
				'label'      => __( 'GDPR Consent', 'newsletter-optin-box' ),
				'field_type' => array( 'consent' ),
			),

        );

        foreach ( get_noptin_custom_fields() as $custom_field ) {

            if ( ! $custom_field['predefined'] ) {
                $map_fields[] = array(
                    'name'  => $custom_field['merge_tag'],
                    'label' => $custom_field['label'],
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
        return esc_html__( 'Feed Settings', 'newsletter-optin-box' );
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
				'class'   => 'medium',
			),

			array(
				'name'           => 'optinCondition',
				'label'          => esc_html__( 'Opt-In Condition', 'newsletter-optin-box' ),
				'checkbox_label' => __( 'Enable Condition', 'newsletter-optin-box' ),
				'type'           => 'feed_condition',
				'instructions'   => __( 'Only add to Noptin if', 'newsletter-optin-box' ),
            ),

		);

		$map_settings = array(

			array(
				'name'      => 'noptinFields',
            	'label'     => esc_html__( 'Map Fields', 'newsletter-optin-box' ),
            	'type'      => 'field_map',
            	'field_map' => $this->get_map_fields(),
            ),

		);

		$fields = apply_filters(
			'noptin_gravity_forms_settings_fields',
			array(
				array(
					'fields' => apply_filters( 'noptin_gravity_forms_normal_settings_fields', $normal_settings ),
				),

				array(
					'fields' => apply_filters( 'noptin_gravity_forms_map_settings_fields', $map_settings ),
                ),
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
			'source'          => 'Gravity Forms',
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
