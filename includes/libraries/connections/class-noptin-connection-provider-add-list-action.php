<?php
/**
 * Noptin Connection Provider Add List Action Class.
 *
 * @package Noptin\noptin.com
 * @since   1.5.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin Connection Provider Add List Action Class.
 *
 * @since 1.5.1
 * @ignore
 */
class Noptin_Connection_Provider_Add_List_Action extends Noptin_Abstract_Action {

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
            'add-%s-list',
            $this->provider->slug
        );
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return sprintf(
            __( '%s List Add', 'newsletter-optin-box' ),
            $this->provider->name
        );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return sprintf(
            __( 'Add the subscriber to a %s list', 'newsletter-optin-box' ),
            $this->provider->name
        );
    }

    /**
     * @inheritdoc
     */
    public function get_rule_description( $rule ) {

        $settings = $rule->action_settings;

        if ( empty( $settings['list'] ) ) {
            return $this->get_description();
        }

        $list = sanitize_text_field( $settings['list'] );
        $list = $this->provider->list_providers->get_list( $list );

        if ( empty( $list ) ) {
            return $this->get_description();
        }

        $list = esc_html( $list->get_name() );

        return sprintf(
            __( 'Add the subscriber to the list "%s"', 'newsletter-optin-box' ),
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
            'list'
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {

        return array(

            'list'     => array(
				'el'          => 'select',
				'label'       => __( 'List', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select a list', 'newsletter-optin-box' ),
				'options'     => $this->provider->list_providers->get_dropdown_lists(),
                'default'     => $this->provider->get_default_list_id(),
				'description' => __( 'Select the list where you want the subscriber to be added to.', 'newsletter-optin-box' ),
            )

        );

    }

    /**
     * Add the subscriber to a list.
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
        if ( empty( $settings['list'] ) ) {
            return;
        }

        $list = sanitize_text_field( $settings['list'] );
        $list = $this->provider->list_providers->get_list( $list );

        if ( empty( $list ) ) {
            return;
        }

        try {
            $list->add_subscriber( $subscriber );
        } catch ( Exception $ex ) {
            log_noptin_message( $ex->getMessage() );
        }

    }

}
