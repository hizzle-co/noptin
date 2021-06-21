<?php
/**
 * Noptin Connection Provider Add Tags Action Class.
 *
 * @package Noptin\noptin.com
 * @since   1.5.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin Connection Provider Add Tags Action Class.
 *
 * @since 1.5.1
 * @ignore
 */
class Noptin_Connection_Provider_Add_Tags_Action extends Noptin_Abstract_Action {

	/**
	 * The connection provider.
	 * @var Noptin_Connection_Provider
	 */
	protected $provider;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 * @param Noptin_Connection_Provider $connection_provider
	 * @return void
	 */
	public function __construct( $connection_provider ) {
		$this->provider = $connection_provider;
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return sprintf(
			'add-%s-tags',
			$this->provider->slug
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return sprintf(
			esc_html__( '%s > Tag Subscriber', 'newsletter-optin-box' ),
			$this->provider->name
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {

		if ( $this->provider->supports( 'built_in' ) ) {
			return __( 'Tag the subscriber', 'newsletter-optin-box' );
		}

		return sprintf(
			__( 'Tag the subscriber in %s', 'newsletter-optin-box' ),
			$this->provider->name
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_description( $rule ) {

		$settings = $rule->action_settings;

		if ( empty( $settings['tags'] ) ) {
			return $this->get_description();
		}

		$tags = sanitize_text_field( $settings['tags'] );

		return sprintf(
			__( 'Tag the subscriber: "%s"', 'newsletter-optin-box' ),
			"<code>$tags</code> ({$this->provider->name})"
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
			'tags'
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		$settings = array(

			'list'     => array(
				'el'          => 'select',
				'label'       => $this->provider->list_providers->get_name(),
				'placeholder' => __( 'Select an option', 'newsletter-optin-box' ),
				'options'     => $this->provider->list_providers->get_dropdown_lists(),
				'default'     => $this->provider->get_default_list_id(),
			),

			'tags' => array(
				'el'                => 'input',
				'label'             => __( 'Tags', 'newsletter-optin-box' ),
				'placeholder'       => 'tag, another tag',
				'description'       => __( 'The listed tags will be applied to the subscriber. Separate multiple tags with a comma.', 'newsletter-optin-box' ),
			)

		);

		if ( $this->provider->supports( 'universal_tags' ) ) {
			unset( $settings['list'] );
		}

		return $settings;
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
		if ( empty( $settings['tags'] ) ) {
			return;
		}

		if ( ! $this->provider->supports( 'universal_tags' ) && empty( $settings['list'] ) ) {
			return;
		}

		$tags = array_filter( noptin_clean( explode( ',', $settings['tags'] ) ) );

		if ( empty( $tags ) ) {
			return;
		}

		if ( $this->provider->supports( 'universal_tags' ) ) {

			try {
				$this->provider->list_providers->tag_subscriber( $subscriber, $tags );
			} catch ( Exception $ex ) {
				log_noptin_message( $ex->getMessage() );
			}

			return;
		}

		$list = sanitize_text_field( $settings['list'] );
		$list = $this->provider->list_providers->get_list( $list );

		if ( empty( $list ) ) {
			return;
		}

		try {
			$list->tag_subscriber( $subscriber, $tags );
		} catch ( Exception $ex ) {
			log_noptin_message( $ex->getMessage() );
		}

	}

}
