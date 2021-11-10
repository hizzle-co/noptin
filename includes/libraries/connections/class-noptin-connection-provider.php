<?php
/**
 * Noptin.com Connection Providers Class.
 *
 * @package Noptin\noptin.com
 * @since   1.5.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin_Connection_Provider Class
 *
 * @since 1.5.1
 * @ignore
 */
abstract class Noptin_Connection_Provider extends Noptin_Abstract_Integration {

	/**
	 * @var int The priority for hooks.
	 * @since 1.5.1
	 */
	public $priority = 100;

	/**
	 * @var string type of integration.
	 * @since 1.5.1
	 */
	public $integration_type = 'esp';

	/**
	 * @var string last error message.
	 * @since 1.5.1
	 */
	public $last_error = '';

	/**
	 * @var Noptin_List_Providers Main list providers factory.
	 * @since 1.5.1
	 */
	public $list_providers;

	/**
	 * @var array
	 * @since 1.5.1
	 */
	public $supports = array();

	/**
	 * Checks if we're connected to the provider.
	 *
	 * @since 1.5.1
	 */
	abstract public function is_connected();

	/**
	 * Checks if a given feature is supported.
	 *
	 * @since 1.5.1
	 */
	public function supports( $feature ) {
		return in_array( $feature, $this->supports );
	}

	/**
	 * This method is called after an integration is initialized.
	 *
	 * @since 1.5.1
	 */
	public function initialize() {

		// Register this provider.
		add_filter( 'noptin_connection_providers', array( $this, 'register_provider' ), $this->priority );

		if ( ! $this->is_connected() || empty( $this->list_providers ) ) {
			return;
		}

		// Automation rules.
		add_action( 'noptin_automation_rules_load', array( $this, 'load_automation_rules' ), $this->priority );

		// New subscribers.
		add_action( 'noptin_insert_subscriber', array( $this, 'add_subscriber'), $this->priority, 2 );

		add_filter( 'noptin_single_integration_settings', array( $this, 'add_list_options' ), $this->priority, 3 );

		// Send campaigns.
		if ( $this->supports( 'campaigns' ) ) {
			add_filter( 'noptin_email_senders', array( $this, 'register_sender' ), $this->priority );
			add_action( 'noptin_sender_options_' . $this->slug, array( $this, 'show_sender_options' ), $this->priority );
			add_filter( 'noptin_get_newsletter_campaign_meta', array( $this, 'register_meta' ), $this->priority );
			add_action( 'handle_noptin_email_sender_' . $this->slug, array( $this, 'send_campaign' ), $this->priority, 2 );			
		}

	}

	/**
	 * Loads automation rules.
	 *
	 * @param Noptin_Automation_Rules $automation_rules
	 * @since 1.5.1
	 */
	public function load_automation_rules( $automation_rules ) {

		$automation_rules->add_action( new Noptin_Connection_Provider_Add_List_Action( $this ) );
		$automation_rules->add_action( new Noptin_Connection_Provider_Remove_List_Action( $this ) );

		if ( $this->supports( 'tags' ) ) {
			$automation_rules->add_action( new Noptin_Connection_Provider_Add_Tags_Action( $this ) );
			$automation_rules->add_action( new Noptin_Connection_Provider_Remove_Tags_Action( $this ) );
		}

		foreach ( $this->list_providers->get_secondary() as $secondary => $is_universal ) {
			$automation_rules->add_action( new Noptin_Connection_Provider_Add_Secondary_List_Action( $this, $secondary, $is_universal ) );
		}

	}

	/**
	 * Adds a new Noptin subscriber to the provider.
	 *
	 * @since 1.5.1
	 */
	public function add_subscriber( $subscriber_id, $data = array() ) {

		// Retrieve the Noptin subscriber.
		$noptin_subscriber = new Noptin_Subscriber( $subscriber_id );
		if ( ! $noptin_subscriber->exists() ) {
			return;
		}

		// Fetch appropriate list.
		$data             = $this->prepare_new_subscriber_data( $noptin_subscriber, $data );
		$integration_data = empty( $data[ $this->slug ] ) ? array() : $data[ $this->slug ];
		$lists            = empty( $integration_data['lists'] ) ? $this->get_default_list_id() : $integration_data['lists'];

		if ( ! is_array( $lists ) ) {
			$lists = array( $lists );
		}

		if ( empty( $lists ) || in_array( '-1', $lists ) ) {
			return;
		}

		$integration_data['double_optin'] = $this->enabled_double_optin();

		foreach ( array_keys( $this->list_providers->get_secondary() ) as $secondary ) {

			if ( empty( $integration_data[ $secondary ] ) ) {
				$integration_data[ $secondary ] = noptin_parse_list( get_noptin_option( "noptin_{$this->slug}_default_{$secondary}", '' ), true );
			}

		}

		if ( empty( $integration_data['tags'] ) ) {
			$integration_data['tags'] = noptin_parse_list( get_noptin_option( "noptin_{$this->slug}_default_tags", '' ), true );
		}

		$integration_data['tags'] = is_array( $integration_data['tags'] ) ? array_filter( $integration_data['tags'] ) : array();

		// Add the subscriber to the list(s).
		try {

			foreach ( $lists as $list_id ) {
				$list = $this->list_providers->get_list( trim( $list_id ) );

				if ( ! empty( $list ) ) {
					$integration_data['fields'] = $this->prepare_list_fields( $noptin_subscriber, $list->get_id() );
					$list->add_subscriber( $noptin_subscriber, $integration_data );
				}

			}

			if ( $this->supports( 'universal_tags' ) && ! empty( $integration_data['tags'] ) ) {
				$this->list_providers->tag_subscriber( $noptin_subscriber, $integration_data['tags'] );
			}

		} catch ( Exception $ex ) {
			$this->last_error = $ex->getMessage();
			log_noptin_message( $ex->getMessage() );
		}

	}

	/**
	 * Returns an array of subscriber fields.
	 *
	 * @param Noptin_Subscriber $subscriber
	 * @param array $data
	 * @since 1.5.1
	 * @return array
	 */
	public function prepare_new_subscriber_data( $subscriber, $data ) {

		// This is usually saved with the new forms.
		delete_noptin_subscriber_meta( $subscriber->id, $this->slug );

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		if ( empty( $data[ $this->slug ] ) ) {
			$data[ $this->slug ] = array();
		}

		$form = $subscriber->get( '_subscriber_via' );

		if ( empty( $form ) ) {
			return $data;
		}

		if ( ! is_numeric( $form ) ) {
			$list = get_noptin_option( sanitize_text_field( "noptin_{$this->slug}_{$form}_default_list" ) );
			$tags = get_noptin_option( sanitize_text_field( "noptin_{$this->slug}_{$form}_default_tags" ) );

			// Suscriber tags.
			if ( $this->supports( 'tags' ) && ! isset( $data[ $this->slug ]['tags'] ) && ! empty( $tags ) ) {
				$data[ $this->slug ]['tags'] = noptin_parse_list( $tags, true );
			}

			// Suscriber list.
			if ( ! isset( $data[ $this->slug ]['lists'] ) && ! empty( $list ) ) {
				$data[ $this->slug ]['lists'] = array_map( 'trim', explode( ',', $list ) );
			}

			// Secondary fields.
			foreach ( array_keys( $this->list_providers->get_secondary() ) as $secondary ) {
				$default = noptin_parse_list( get_noptin_option( "noptin_{$this->slug}_{$form}_default_{$secondary}", '' ), true );
				if ( ! isset( $data[ $this->slug ][ $secondary ] ) && ! empty( $default ) ) {
					$data[ $this->slug ][ $secondary ] = noptin_parse_list( $default, true );
				}
			}

			return $data;
		}

		if ( ! is_legacy_noptin_form( absint( $form ) ) ) {
			return $data;
		}

		$form = absint( $form );
		$form = noptin_get_optin_form( $form );

		// Ensure the form exists.
		if ( ! $form->is_published() ) {
			return $data;
		}

		// Suscriber tags.
		$tags = "{$this->slug}_tags";
		if ( $this->supports( 'tags' ) && ! empty( $form->$tags ) ) {
			$data[ $this->slug ]['tags'] = array_map( 'trim', explode( ',', $form->$tags ) );
		}

		// Suscriber list.
		$list = "{$this->slug}_list";
		if ( ! empty( $form->$list ) ) {
			$data[ $this->slug ]['lists'] = array_map( 'trim', explode( ',', $form->$list ) );
		}

		foreach ( array_keys( $this->list_providers->get_secondary() ) as $secondary ) {
			$key = sanitize_key( "{$this->slug}_{$secondary}" );

			if ( ! empty( $form->$key ) ) {
				$data[ $this->slug ][ $secondary ] = noptin_parse_list ( $form->$key, true );
			}

		}

		return $data;
	}

	/**
	 * Prepares list fields.
	 *
	 * @param Noptin_Subscriber $noptin_subscriber
	 * @param string $list_id
	 * @since 1.5.1
	 * @return array
	 */
	public function prepare_list_fields( $noptin_subscriber, $list_id ) {

		$fields = array();
		$key    = $this->slug;
		$key    = $this->supports( 'universal_fields' ) ? $key : "{$key}_{$list_id}";

		foreach ( get_noptin_custom_fields() as $field ) {

			if ( empty( $field[ $key ] ) ) {
				continue;
			}

			$value = $noptin_subscriber->get( $field['merge_tag'] );

			if ( '' !== $value ) {
				$fields[ $field[ $key ] ] = $value;
			}
		}

		return $fields;

	}

	/**
	 * Returns the default list id.
	 *
	 * @since 1.5.1
	 * @return string
	 */
	public function get_default_list_id() {
		return get_noptin_option( 'noptin_' . $this->slug .'_enable_default_list', '-1' );
	}

	/**
	 * Registers the provider.
	 *
	 * @since 1.5.1
	 * @param Noptin_Connection_Provider[] $providers
	 * @return Noptin_Connection_Provider[]
	 */
	public function register_provider( $providers ) {
		$providers[ $this->slug ] = $this;
		return $providers;
	}

	/**
	 * Registers integration options.
	 *
	 * @since 1.5.1
	 * @param array $_options Current Noptin settings.
	 * @return array
	 */
	public function add_options( $_options ) {

		if ( $this->supports( 'built_in' ) ) {
			return $_options;
		}

		$slug    = $this->slug;
		$options = $this->add_enable_integration_option( array() );

		if ( ! $this->supports( 'oauth' ) ) {
			$options = $this->add_connection_options( $options );
		}

		if ( $this->is_connected() ) {

			// Double optin.
			if ( $this->supports( 'double_optin' ) ) {

				$options["noptin_{$slug}_enable_double_optin"] = array(
					'type'                  => 'checkbox_alt',
					'el'                    => 'input',
					'section'		        => 'integrations',
					'label'                 => __( 'Enable double opt-in', 'newsletter-optin-box' ),
					'description'           => __( 'Send contacts an opt-in confirmation email when they sign up', 'newsletter-optin-box' ),
					'restrict'              => $this->get_enable_integration_option_name(),
				);

			}

			// Default list.
			if ( ! empty( $this->list_providers ) ) {

				$options["noptin_{$slug}_enable_default_list"] = array(
					'section'	  => 'integrations',
					'el'          => 'select',
					'options'     => $this->list_providers->get_dropdown_lists(),
					'placeholder' => sprintf( __( 'Select a default %s', 'newsletter-optin-box' ), $this->list_providers->get_name() ),
					'label'       => sprintf( __( 'Default %s', 'newsletter-optin-box' ), $this->list_providers->get_name() ),
					'restrict'    => $this->get_enable_integration_option_name(),
				);

			}

			// Tags.
			if ( $this->supports( 'tags' ) ) {

				$options["noptin_{$slug}_default_tags"] = array(
					'el'                    => 'input',
					'section'		        => 'integrations',
					'label'                 => __( 'Default tags', 'newsletter-optin-box' ),
					'description'           => __( 'Enter a comma separated list of default tags to assign new suscribers.', 'newsletter-optin-box' ),
					'restrict'              => $this->get_enable_integration_option_name(),
					'placeholder' => 'tag 1, tag 2',
				);

			}

			foreach ( $this->list_providers->get_secondary() as $secondary => $is_universal ) {

				if ( $is_universal ) {

					$options["noptin_{$slug}_default_{$secondary}"] = array(
						'el'          => 'multiselect',
						'label'       => sprintf( __( 'Default %s', 'newsletter-optin-box' ), $secondary ),
						'options'     => $this->list_providers->get_dropdown( $secondary ),
					);

				} else {

					$options["noptin_{$slug}_default_{$secondary}"] = array(
						'el'          => 'input',
						'label'       => sprintf( __( 'Default %s (ids)', 'newsletter-optin-box' ), $secondary ),
					);

				}

			}

			// Extra integration options.
			$options = $this->get_options( $options );

		}

		$options = apply_filters( "noptin_single_integration_settings", $options, $slug, $this );

		if ( $this->supports( 'oauth' ) ) {
			$options = $this->add_connection_options( $options );
		}

		$_options["settings_section_$slug"] = array(
			'id'          => "settings_section_$slug",
			'el'          => 'settings_section',
			'children'    => $options,
			'section'     => 'integrations',
			'heading'     => sanitize_text_field( $this->name ),
			'description' => sanitize_text_field( $this->description ),
			'badge'       => $this->get_hero_extra(),
		);

		return apply_filters( "noptin_{$slug}_integration_settings", $_options, $this );

	}

	/**
	 * Adds connection options to settings fields.
	 *
	 * @since 1.5.1
	 * @return false
	 */
	public function add_connection_options( $options ) {
		return $options;
	}

	/**
	 * Extra setting fields.
	 *
	 * @since 1.5.1
	 * @return false
	 */
	public function get_options( $options ) {
		return $options;
	}

	/**
	 * Returns extra texts to append to the hero
	 *
	 * @return string
	 * @since 1.5.1
	 */
	public function get_hero_extra() {

		$option   = $this->get_enable_integration_option_name();
		$disabled = __( 'Disabled', 'newsletter-optin-box' );

		if ( $this->is_connected() ) {
			$enabled = __( 'Connected', 'newsletter-optin-box' );
		} else {

			if ( ! empty( $this->last_error ) ) {
				$enabled = __( 'Not Connected', 'newsletter-optin-box' );
				$enabled = "$enabled <em>( {$this->last_error} )</em>";
			} else {
				$enabled = __( 'Enabled', 'newsletter-optin-box' );
			}

		}

		return "
			<span style='color: #43a047;' v-if='$option'>$enabled</span>
			<span style='color: #616161;' v-else>$disabled</span>
		";

	}

	/**
	 * Checks if double opt-in is enabled.
	 *
	 * @since 1.5.1
	 * @return bool
	 */
	public function enabled_double_optin() {
		return (bool) get_noptin_option( "noptin_{$this->slug}_enable_double_optin", false );
	}

	/**
	 * Returns connection specific form settings.
	 *
	 * @param array $options Current Noptin settings.
	 * @return array
	 */
	public function add_custom_options( $options ) {
		return $options;
	}

	/**
	 * Returns connection specific form state.
	 *
	 * @param array $options Current Noptin settings.
	 * @return array
	 */
	public function add_custom_default_form_state( $options ) {
		return $options;
	}

	/**
	 * Returns connection specific default field props.
	 *
	 * @param array $default Default field props.
	 * @return array
	 */
	public function add_custom_field_props( $default ) {
		return $default;
	}

	/**
	 * Registers our sender.
	 *
	 */
	public function register_sender( $senders ) {
		$senders[ $this->slug ] = $this->name;
		return $senders;
	}

	/**
	 * Displays the sender options.
	 *
	 * @param null|WP_Post $campaign
	 */
	public function show_sender_options( $campaign ) {
		$options = empty( $campaign ) ? array() : get_post_meta( $campaign->ID, $this->slug, true );
		$list    = empty( $options['list'] ) ? '0' : esc_attr( $options['list'] );
		$tags    = empty( $options['tags'] ) ? '' : esc_attr( $options['tags'] );
		$extra   = empty( $options['extra'] ) ? array() : esc_attr( $options['extra'] );
		?>

			<div class="noptin-<?php echo esc_attr( $this->slug );?>-list">

				<label class="noptin-margin-y">
					<strong><?php echo esc_html( ucwords( $this->list_providers->get_name() ) ); ?></strong>
					<select name="<?php echo esc_attr( $this->slug ); ?>[list]" style="width: 100%;" class="list-select">
						<option value="0" <?php selected( empty( $list ) ) ?>><?php _e( 'Select an option', 'newsletter-optin-box' );?></option>
						<?php foreach ( $this->list_providers->get_dropdown_lists() as $id => $name ) :?>
							<option value="<?php echo esc_attr( $id ) ?>" <?php selected( $id, $list ) ?>><?php echo esc_html( $name );?></option>
						<?php endforeach; ?>
					</select>
				</label>

				<?php if ( $this->supports( 'tags' ) ) : ?>
					<label class="noptin-margin-y">
						<strong><?php _e( 'Tags', 'newsletter-optin-box' ); ?></strong>
						<input style="width: 100%;" type="text" value="<?php echo esc_attr( $tags ) ?>" name="<?php echo esc_attr( $this->slug ); ?>[tags]" />
					</label>
				<?php endif; ?>

				<?php foreach ( $this->list_providers->get_lists() as $_list ) : ?>
					<div class="noptin-filter-list noptin-<?php echo esc_attr( $this->slug );?>-filter-list noptin-list-<?php echo esc_attr( $_list->get_id() ) ?>">
					<?php foreach ( $_list->get_children() as $child_id => $child ) : ?>
						<?php $value = isset( $extra[ $_list->get_id() ][ $child_id ] ) ? $extra[ $_list->get_id() ][ $child_id ] : '' ?>
						<label class="noptin-margin-y"><strong><?php echo esc_html( $child['label'] ); ?></strong>
							<select name="<?php echo esc_attr( $this->slug ); ?>[extra][<?php echo esc_attr( $_list->get_id() ); ?>][<?php echo esc_attr( $child_id ); ?>]" style="width: 100%;">
								<option <?php selected( empty( $value ) ) ?> value='0'>
									<?php _e( 'All', 'newsletter-optin-box' ); ?>
								</option>
								<?php foreach ( $child['options'] as $id => $name ) : ?>
									<option <?php selected( $value, $id ) ?> value='<?php echo esc_attr( $id );?>'>
										<?php echo sanitize_text_field( $name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</label>
					<?php endforeach; ?>
					</div>
				<?php endforeach; ?>

			</div>

			<script>
				var sync = function() {

					var parent   = jQuery('.noptin-<?php echo esc_js( $this->slug );?>-list')
					var selected = parent.find( '.list-select' ).val()

					// Hide list filter options.
					parent.find('.noptin-<?php echo esc_attr( $this->slug );?>-filter-list').hide()

					// Show available filters.
					parent.find( '.noptin-list-' + selected ).show()

				}
				sync()
				jQuery('.noptin-<?php echo esc_js( $this->slug );?>-list .list-select').on( 'change', sync )
			</script>
		<?php
	}

	/**
	 * Registers meta data.
	 *
	 * @param array $meta
	 */
	public function register_meta( $meta ) {
		$meta[] = $this->slug;
		return $meta;
	}

	/**
	 * Send campaign.
	 *
	 * @param array $campaign
	 * @param WP_Post $post
	 */
	public function send_campaign( $campaign, $post ) {

		update_post_meta( $post->ID, 'completed', 1 );

		$options = get_post_meta( $post->ID, $this->slug, true );

		if ( empty( $options['list'] ) ) {
			return;
		}

		$list = $this->list_providers->get_list( $options['list'] );

		if ( empty( $list ) ) {
			return;
		}

		$tags  = empty( $options['tags'] ) ? array() : noptin_parse_list( $options['tags'] );
		$extra = empty( $options['extra'][ $list->get_id() ] ) ? array() : $options['extra'][ $list->get_id() ];

		$campaign['custom_merge_tags']['unsubscribe_url'] = 'http://temporaryunsubscribe.com';

		$campaign_data               = $campaign['campaign_data'];
		$campaign_data['merge_tags'] = $campaign['custom_merge_tags'];

		if ( $this->supports( 'overide_footers' ) ) {
			$campaign_data['permission_text'] = '';
			$campaign_data['footer_text']     = '';
		}

		$strip_tags = noptin()->mailer->strip_tags;

		noptin()->mailer->strip_tags = true;

		$campaign          = noptin()->mailer->prepare( $campaign_data );
		$campaign['tags']  = $tags;
		$campaign['extra'] = $extra;

		$campaign['email_body'] = str_replace( 'http://temporaryunsubscribe.com', $this->get_unsubscribe_tag(), $campaign['email_body'] );

		noptin()->mailer->strip_tags = $strip_tags;

		$list->send_campaign( $campaign );
	}

	/**
	 * Returns the unsubscription tag.
	 *
	 * @since 1.5.1
	 */
	public function get_unsubscribe_tag() {
		return '[[unsubscribe_url]]';
	}

	/**
	 * Registers list options.
	 *
	 * @since 1.5.1
	 * @param array $options
	 * @param string $slug
	 * @param Noptin_Abstract_Integration $integration
	 */
	public function add_list_options( $options, $slug, $integration ) {

		if ( $integration->integration_type == 'normal' || $integration->integration_type == 'ecommerce' ) {

			$via       = str_replace( '_form', '', $slug );
			$via      .= $integration->integration_type == 'ecommerce' ? '_checkout' : '';

			$option    = sanitize_text_field( "noptin_{$this->slug}_{$via}_default_list" );

			$options[ $option ]  = array(
				'el'          => 'select',
				'section'     => 'integrations',
				'label'       => sprintf(
					'%s %s',
					$this->name,
					$this->list_providers->get_name()
				),
				'restrict'    => sprintf(
					'%s && %s',
					$this->get_enable_integration_option_name(),
					$integration->get_enable_integration_option_name()
				),
				'options'     => $this->list_providers->get_dropdown_lists(),
				'description' => sprintf( __( 'New subscribers will be added to this %s', 'newsletter-optin-box' ), $this->list_providers->get_name() ),
				'placeholder' => sprintf( __( 'Select a %s', 'newsletter-optin-box' ), $this->list_providers->get_name() ),
			);

			if ( $this->supports( 'tags' ) ) {

				$option = sanitize_text_field( "noptin_{$this->slug}_{$via}_default_tags" );

				$options[ $option ]  = array(
					'el'          => 'input',
					'section'     => 'integrations',
					'label'       => sprintf( __( '%s tags', 'newsletter-optin-box' ), $this->name ),
					'description' => __( 'Enter a comma separated list of tags to assign new suscribers.', 'newsletter-optin-box' ) ,
					'placeholder' => '',
					'restrict'    => sprintf(
						'%s && %s',
						$this->get_enable_integration_option_name(),
						$integration->get_enable_integration_option_name()
					),
				);

			}

			foreach ( $this->list_providers->get_secondary() as $secondary => $is_universal ) {

				$option    = sanitize_text_field( "noptin_{$this->slug}_{$via}_default_{$secondary}" );

				if ( $is_universal ) {

					$options[ $option ]  = array(
						'el'          => 'multiselect',
						'section'     => 'integrations',
						'label'       => sprintf(
							'%s %s',
							$this->name,
							$secondary
						),
						'restrict'    => sprintf(
							'%s && %s',
							$this->get_enable_integration_option_name(),
							$integration->get_enable_integration_option_name()
						),
						'options'     => $this->list_providers->get_dropdown( $secondary ),
						'description' => sprintf( __( 'New subscribers will be added to this %s', 'newsletter-optin-box' ), $secondary ),
					);

				} else {

					$options[ $option ]  = array(
						'el'          => 'input',
						'section'     => 'integrations',
						'label'       => sprintf(
							'%s %s',
							$this->name,
							$secondary
						),
						'description' => __( 'New subscribers will be added here', 'newsletter-optin-box' ),
						'restrict'    => sprintf(
							'%s && %s',
							$this->get_enable_integration_option_name(),
							$integration->get_enable_integration_option_name()
						),
					);

				}

			}

		}

		return $options;
	}

}
