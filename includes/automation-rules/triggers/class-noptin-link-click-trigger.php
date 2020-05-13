<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Fires when a subscriber clicks on link.
 *
 * @since       1.2.8
 */
class Noptin_Link_Click_Trigger extends Noptin_Abstract_Trigger {

    /**
     * Constructor.
     *
     * @since 1.3.0
     * @return string
     */
    public function __construct() {
        add_action( 'log_noptin_subscriber_campaign_click', array( $this, 'maybe_trigger' ), 10, 3 );
    }

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'link_click';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'Link Click', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Fired when a subscriber clicks on a link in an email', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_rule_description( $rule ) {
        $settings = $rule->trigger_settings;

        $campaign_id = empty( $settings['campaign_id'] ) ? 0 : (int) $settings['campaign_id'];
        $url = empty( $settings['url'] ) ? '' : noptin_clean_url( $settings['url'] );

        if ( empty( $campaign_id ) && empty( $url ) ) {
            return __( 'When a subscriber clicks on any link from any email campaign', 'newsletter-optin-box' );
        }

        if ( empty( $campaign_id ) ) {
            return sprintf(
                __( 'When a subscriber clicks on the link %s from any email campaign', 'newsletter-optin-box' ),
               "<code>$url</code>"
            );
        }

        $campaign_title = esc_html( get_the_title( $campaign_id ) );

        if ( empty( $campaign_title ) ) {
            $campaign_title = __( 'Deleted campaign', 'newsletter-optin-box' );
        }

        if ( empty( $url ) ) {
            return sprintf(
                __( 'When a subscriber clicks on any link from the email campaign %s', 'newsletter-optin-box' ),
               "<code>$campaign_title</code>"
            );
        }

        return sprintf(
            __( 'When a subscriber clicks on the link %s from the email campaign %s', 'newsletter-optin-box' ),
           "<code>$url</code>",
           "<code>$campaign_title</code>"
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
            'click',
            'link'
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
				'placeholder' => __( 'Watch for clicks on all campaigns', 'newsletter-optin-box' ),
				'options'     => $campaigns,
				'description' => __( 'Select the campaign that you would like to watch for clicks.', 'newsletter-optin-box' ),
            ),

            'url' => array(
				'el'          => 'input',
				'label'       => __( 'URL', 'newsletter-optin-box' ),
				'description' => __( 'Enter the URL to watch for clicks or leave empty to watch for all URLs.', 'newsletter-optin-box' ),
            )

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

        // Are we filtering by link?
        $link = noptin_clean_url( $args['link'] );
        if ( ! empty( $settings['url'] ) && noptin_clean_url( $settings['url'] ) !== $link ) {
            return false;
        }

        return true;
    }

    /**
     * Called when a subscriber clicks on a link.
     *
     * @param int $subscriber_id The subscriber in question.
     * @param $campaign_id The campaign that was clicked.
     * @param $link The link that was clicked.
     */
    public function maybe_trigger ( $subscriber_id, $campaign_id, $link ) {
        $this->trigger( $subscriber_id, compact( 'campaign_id', 'link' ) );
    }

}
