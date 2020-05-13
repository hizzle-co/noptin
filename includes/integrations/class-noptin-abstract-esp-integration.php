<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Base Email Service Provider integration
 *
 * @since       1.2.8
 */
abstract class Noptin_Abstract_ESP_Integration extends Noptin_Abstract_Integration {

	/**
	 * @var int The priority for hooks.
	 * @since 1.2.8
	 */
	public $priority = 100;

	/**
	 * @var string type of integration.
	 * @since 1.2.8
	 */
	public $integration_type = 'esp';

	/**
	 * @var string last error message.
	 * @since 1.2.8
	 */
	protected $last_error = '';

	/**
	 * @var string Setup page.
	 * @since 1.2.8
	 */
	public $setup_page = '';

	/**
	 * This method is called after an integration is initialized.
	 *
	 * @since 1.2.8
	 */
	public function initialize() {
		add_action( 'noptin_insert_subscriber', array( $this, 'sync_noptin_subscriber'), $this->priority, 2 );
		add_action( 'noptin_update_subscriber', array( $this, 'sync_noptin_subscriber'), $this->priority, 2 );
	}

	/**
	 * Adds a new Noptin subscriber to remote.
	 *
	 * @since 1.2.8
	 */
	public function sync_noptin_subscriber( $subscriber_id, $data = array() ) {

		// Retrieve the Noptin subscriber.
		$noptin_subscriber = new Noptin_Subscriber( $subscriber_id );
		if ( ! $noptin_subscriber->exists() ) {
			return;
		}

		// Is the subscriber already added to remote?
		$remote_subscriber = $this->get_remote_subscriber( $noptin_subscriber );

		// If yes, update them...
		if ( ! empty( $remote_subscriber ) ) {

			if ( ! $this->update_remote_subscriber( $remote_subscriber, $noptin_subscriber, $data ) ) {
				log_noptin_message(
					sprintf(
						__( 'Error updating %s subscriber: %s', 'newsletter-optin-box' ),
						$this->name,
						$this->get_last_error()
					)
				);
			}
			return;

		}

		// ... Else, add them
		if ( ! $this->add_remote_subscriber( $noptin_subscriber, $data ) ) {
			log_noptin_message(
				sprintf(
					__( 'Error adding %s subscriber: %s', 'newsletter-optin-box' ),
					$this->name,
					esc_html( $this->get_last_error() )
				)
			);
		}
		return;
	}

	/**
	 * Returns the remote subscriber associated with the Noptin subscriber.
	 *
	 * @param Noptin_Subscriber $subscriber The subscriber to check for.
	 * @since 1.2.8
	 * @return bool/mixed False if not registered remotely, the remote subscriber otherwise.
	 */
	public function get_remote_subscriber( $subscriber ) {

		try {
			return $this->get_client()->get_subscriber( $subscriber );
        } catch ( Exception $ex ) {
			$this->last_error = $ex->getMessage();
			return false;
		}

	}

	/**
	 * Updates a remote subscriber.
	 *
	 * @param mixed             $remote_subscriber The remote subscriber.
	 * @param Noptin_Subscriber $subscriber The subscriber to check for.
	 * @param array             $args Extra subscriber args.
	 * @since 1.2.8
	 * @return bool Whether or not the subscriber was successfully updated.
	 */
	public function update_remote_subscriber( $remote_subscriber, $subscriber, $args = array() ) {
		
		try {
			return $this->get_client()->update_subscriber( $remote_subscriber, $subscriber, $args );
        } catch ( Exception $ex ) {
			$this->last_error = $ex->getMessage();
			return false;
		}

	}

	/**
	 * Creates a new remote subscriber.
	 *
	 * @param Noptin_Subscriber $subscriber The subscriber to check for.
	 * @param array             $args Extra subscriber args.
	 * @since 1.2.8
	 * @return bool Whether or not the subscriber was successfully added.
	 */
	public function add_remote_subscriber( $subscriber, $args = array() ) {

		try {
			return $this->get_client()->add_subscriber( $subscriber, $args );
        } catch ( Exception $ex ) {
			$this->last_error = $ex->getMessage();
			return false;
		}

	}

	/**
	 * Checks if the site is connected to the remote.
	 *
	 * @since 1.2.8
	 * @return bool Whether or not the website is connected remotely.
	 */
	public function is_connected() {

		try {
			return $this->get_client()->is_connected();
        } catch ( Exception $ex ) {
			$this->last_error = $ex->getMessage();
			return false;
		}

	}

	/**
	 * Registers integration options.
	 *
	 * @since 1.2.8
	 * @param array $options Current Noptin settings.
	 * @return array
	 */
	public function add_options( $options ) {

		$slug = $this->slug;

		// Integration name hero text.
		if ( ! empty( $this->name ) ) {

			$options["noptin_{$slug}_integration_hero"] = array(
				'el'              => 'hero',
				'section'		  => 'integrations',
				'content'         => $this->name . $this->get_hero_extra(), 
			);

		}

		// Integration description text.
		if ( ! empty( $this->description ) ) {

			$options["noptin_{$slug}_integration_description"] = array(
				'el'              => 'paragraph',
				'section'		  => 'integrations',
				'content'         => $this->description, 
			);

		}

		// Enables the integration.
		$options = $this->add_enable_integration_option( $options );

		// Double optin.
		if ( $this->supports_double_optin() ) {

			$options["noptin_{$slug}_enable_double_optin"] = array(
				'type'                  => 'checkbox_alt',
				'el'                    => 'input',
				'section'		        => 'integrations',
				'label'                 => __( 'Enable double opt-in', 'newsletter-optin-box' ),
				'description'           => __( 'Send contacts an opt-in confirmation email when they sign up', 'newsletter-optin-box' ),
				'restrict'              => $this->get_enable_integration_option_name(),
			);

		}

		// Extra integration options.
		$options = $this->get_options( $options );

		// Setup the integration.
		if ( ! empty( $this->setup_page ) ) {

			$url  = esc_url( $this->setup_page );
			$text = __( 'Configure integration', 'newsletter-optin-box' );

			if ( ! empty( $this->name ) ) {
				$text = sprintf(
					__( 'Configure %s', 'newsletter-optin-box' ),
					$this->name
				);
			}

			$options["noptin_{$slug}_setup"] = array(
				'el'              => 'paragraph',
				'section'		  => 'integrations',
				'content'         => "<a href='$url'>$text</a>", 
			);

		}
		
		$options = apply_filters( "noptin_single_integration_settings", $options, $slug, $this );

		return apply_filters( "noptin_{$slug}_integration_settings", $options, $this );

	}

	/**
	 * Returns extra texts to append to the hero
	 *
	 * @return string
	 * @since 1.2.8
	 */
	public function get_hero_extra() {

		if ( $this->is_connected() ) {
			return '&nbsp;&mdash;&nbsp;<em style="color: #4CAF50; font-size: 14px;">' . __( 'Connected', 'newsletter-optin-box' ) . '</em>';
		}

		$error = __( 'Not Connected', 'newsletter-optin-box' );

		if ( ! empty( $this->last_error ) ) {
			$error = "$error ( {$this->last_error} )";
		}

		$error = esc_html( $error );
		return "&nbsp;&mdash;&nbsp;<em style='color: #F44336; font-size: 14px;'>$error</em>";

	}

	/**
	 * Returns integration specific settings.
	 *
	 * Ideally, you will want to  rewrite this in your integration class.
	 *
	 * @param array $options Current Noptin settings.
	 * @since 1.2.8
	 * @return array
	 */
	public function get_options( $options ) {
		return $options;
	}

	/**
	 * Checks if this integration supports lists.
	 *
	 * @since 1.2.8
	 * @return bool
	 */
	public function supports_lists() {
		return false;
	}

	/**
	 * Returns an array of lists.
	 *
	 * @since 1.2.8
	 * @return array
	 */
	public function get_lists( $misc = null ) {
		
		try {
			return $this->get_client()->get_lists( $misc );
        } catch ( Exception $ex ) {
			$this->last_error = $ex->getMessage();
			return false;
        }

	}

	/**
	 * Checks if this integration supports sequences.
	 *
	 * @since 1.2.8
	 * @return bool
	 */
	public function supports_sequences() {
		return false;
	}

	/**
	 * Returns an array of sequences.
	 *
	 * @since 1.2.8
	 * @return array
	 */
	public function get_sequences( $misc = null ) {

		try {
			return $this->get_client()->get_sequences( $misc );
        } catch ( Exception $ex ) {
			$this->last_error = $ex->getMessage();
			return false;
        }

	}

	/**
	 * Checks if this integration supports fields.
	 *
	 * @since 1.2.8
	 * @return bool
	 */
	public function supports_fields() {
		return false;
	}

	/**
	 * Returns an array of fields.
	 *
	 * @since 1.2.8
	 * @return array
	 */
	public function get_fields( $list = null ) {

		try {
			return $this->get_client()->get_fields( $list );
        } catch ( Exception $ex ) {
			$this->last_error = $ex->getMessage();
			return false;
        }

	}

	/**
	 * Checks if this integration supports campaigns.
	 *
	 * @since 1.2.8
	 * @return bool
	 */
	public function supports_campaigns() {
		return false;
	}

	/**
	 * Prepares a campaign.
	 *
	 * @since 1.2.8
	 * @return array
	 */
	public function prepare_campaign( $campaign_data ) {
		return array();
	}

	/**
	 * Saves a campaign.
	 *
	 * @since 1.2.8
	 * @return id Campaign id
	 * @param mixed $prepared_campaign Prepared campaign.
	 */
	public function save_campaign( $prepared_campaign ) {

		try {
			return $this->get_client()->save_campaign( $prepared_campaign );
        } catch ( Exception $ex ) {
			$this->last_error = $ex->getMessage();
			return false;
        }

	}

	/**
	 * Sends a campaign.
	 *
	 * @since 1.2.8
	 * @return bool
	 */
	public function send_campaign( $campaign_id ) {

		try {
			return $this->get_client()->send_campaign( $campaign_id );
        } catch ( Exception $ex ) {
			$this->last_error = $ex->getMessage();
			return false;
        }

	}

	/**
	 * Supports double opt-in.
	 *
	 * @since 1.2.8
	 * @return bool
	 */
	public function supports_double_optin() {
		return false;
	}

	/**
	 * Checks if double opt-in is enabled.
	 *
	 * @since 1.2.8
	 * @return bool
	 */
	public function enabled_double_optin() {
		return (bool) get_noptin_option( "noptin_{$this->slug}_enable_double_optin", false );
	}

	/**
	 * Retrieves the last error message.
	 *
	 * @since 1.2.8
	 * @return bool
	 */
	public function get_last_error() {

		$error = $this->last_error;
		$this->last_error = null;
		return $error;

	}

	/**
	 * Returns the remote client.
	 *
	 * @since 1.2.8
	 * @return object
	 */
	abstract public function get_client();

}
