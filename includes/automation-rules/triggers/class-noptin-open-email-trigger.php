<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Fires when a subscriber opens an email address.
 *
 * @since       1.2.8
 */
class Noptin_Open_Email_Trigger extends Noptin_Abstract_Trigger {

    /**
     * Constructor.
     *
     * @since 1.3.0
     * @return string
     */
    public function __construct() {
        add_action( 'log_noptin_subscriber_campaign_open', array( $this, 'maybe_trigger' ), 10, 2 );
    }

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'open_email';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'Open Email', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Fired when a subscriber opens an email campaign', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_rule_description( $rule ) {
        return __( 'When a subscriber opens an email campaign', 'newsletter-optin-box' );
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
            'open',
            'email'
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {

        $args = array(
			'numberposts'	=> -1,
			'post_type'		=> 'noptin-campaign',
			'post_status'	=> array( 'publish', 'draft', 'pending' ),
			'meta_query'    => array(
				array(
					'key'   		=> 'campaign_type',
                    'value' 		=> array( 'automation', 'newsletter' ),
                    'meta_compare'  => 'IN',
				),
			)
		);
        $posts     =  get_posts( $args );
        $campaigns = wp_list_pluck( $posts, 'post_title', 'ID' );

        return array(

            'campaign_id'     => array(
				'el'          => 'select',
				'label'       => __( 'Campaign', 'newsletter-optin-box' ),
				'placeholder' => __( 'Fire for all campaigns', 'newsletter-optin-box' ),
				'options'     => $campaigns,
				'description' => __( 'Select a campaign above if you want to watch for a specific campaign.', 'newsletter-optin-box' ),
            ),

        );

    }

    /**
     * @inheritdoc
     */
    public function is_rule_valid_for_args( $rule, $args, $subscriber, $action ) {
        $settings = $rule->trigger_settings;

        // Are we filtering by campaign id?
        $campaign_id = (int) $args['campaign_id'];
        if ( ! empty( $settings['campaign_id'] ) && (int) $settings['campaign_id'] !== $campaign_id ) {
            return false;
        }

        return true;
    }

    /**
     * Called when a subscriber opens an email.
     *
     * @param int $subscriber_id The subscriber in question.
     * @param $campaign_id The campaign that was clicked.
     */
    public function maybe_trigger ( $subscriber_id, $campaign_id ) {
        $this->trigger( $subscriber_id, compact( 'campaign_id' ) );
    }

}
