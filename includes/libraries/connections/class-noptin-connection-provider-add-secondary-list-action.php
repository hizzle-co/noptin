<?php
/**
 * Noptin Connection Provider Add Secondary List Action Class.
 *
 * @package Noptin\noptin.com
 * @since   1.5.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin Connection Provider Add Secondary List Action Class.
 *
 * @since 1.5.1
 * @ignore
 */
class Noptin_Connection_Provider_Add_Secondary_List_Action extends Noptin_Abstract_Action {

	/**
	 * The connection provider.
	 * @var Noptin_Connection_Provider
	 */
	protected $provider;

	/**
	 * The secondary list.
	 * @var string
	 */
	protected $secondary_list;

	/**
	 * True if the list is universal.
	 * @var string
	 */
	protected $is_universal;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 * @param Noptin_Connection_Provider $connection_provider
	 * @param string $secondary_list
	 * @param bool $secondary_list
	 * @return void
	 */
	public function __construct( $connection_provider, $secondary_list, $is_universal = false ) {
		$this->provider       = $connection_provider;
		$this->secondary_list = sanitize_text_field( $secondary_list );
		$this->is_universal   = $is_universal;
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return sprintf(
			'add-%s-%s',
			$this->provider->slug,
			$this->secondary_list
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {

		return sprintf(
			/* Translators: %1$s provider tame, %2$s list type. */
			__( '%1$ %2$s Add.', 'newsletter-optin-box' ),
			$this->provider->name,
			ucwords( $this->secondary_list )
		);

	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {

		return sprintf(
			/* Translators: %1$s provider tame, %2$s list type. */
			__( 'Add the subscriber to a %1$ %2$s.', 'newsletter-optin-box' ),
			$this->provider->name,
			$this->secondary_list
		);

	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_description( $rule ) {

		$settings = $rule->action_settings;

		if ( empty( $settings[ $this->secondary_list ] ) ) {
			return $this->get_description();
		}

		$list = esc_html( $settings[ $this->secondary_list ] );

		return sprintf(
			/* Translators: %1$s list type, %2$s list name. */
			__( 'Add the subscriber to the %1$ %2$s.', 'newsletter-optin-box' ),
			esc_html( $this->secondary_list ),
			"<code>$list</code> ({$this->provider->name})"
		);

	}

	/**
	 * @inheritdoc
	 */
	public function get_image() {
		return '';
	}

	/**
	 * @inheritdoc
	 */
	public function get_keywords() {
		return array(
			'noptin',
			$this->provider->slug,
			'add',
			$this->secondary_list
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		if ( $this->is_universal ) {

			$options = $this->provider->list_providers->get_dropdown( $this->secondary_list );
			return array(
				$this->secondary_list => array(
					'el'          => 'select',
					'label'       => $this->secondary_list,
					'placeholder' => __( 'Select an option', 'newsletter-optin-box' ),
					'options'     => $options,
					'default'     => key( $options ),
					'description' => __( 'Where should we add the subscriber?', 'newsletter-optin-box' ),
				)
			);

		}

		return array(

			'list'     => array(
				'el'          => 'select',
				'label'       => $this->provider->list_providers->get_name(),
				'placeholder' => __( 'Select an option', 'newsletter-optin-box' ),
				'options'     => $this->provider->list_providers->get_dropdown_lists(),
				'default'     => $this->provider->get_default_list_id(),
			),

			$this->secondary_list => array(
				'el'          => 'input',
				'label'       => $this->secondary_list,
				'placeholder' => __( 'Enter a value', 'newsletter-optin-box' ),
			)

		);

	}

	/**
	 * Tags the subscriber.
	 *
	 * @since 1.5.1
	 * @param Noptin_Subscriber $subscriber The subscriber.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function run( $subscriber, $rule, $args ) {

		$settings = $rule->action_settings;

		// Nothing to do here.
		if ( empty( $settings[ $this->secondary_list ] ) ) {
			return;
		}

		try {
			$secondary_list = array_filter( noptin_clean( explode( ',', $settings[ $this->secondary_list ] ) ) );

			if ( empty( $secondary_list ) ) {
				return;
			}

			if ( $this->is_universal ) {
				$this->provider->list_providers->add_to( $subscriber, $secondary_list, $this->secondary_list );
				return;
			}

			if ( empty( $settings['list'] ) ) {
				return;
			}

			$list = sanitize_text_field( $settings['list'] );
			$list = $this->provider->list_providers->get_list( $list );

			if ( empty( $list ) ) {
				return;
			}

			$this->provider->list_providers->add_to( $subscriber, $secondary_list, $this->secondary_list );

		} catch ( Exception $ex ) {
			log_noptin_message( $ex->getMessage() );
		}

	}

}
