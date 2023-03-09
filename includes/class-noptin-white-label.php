<?php

/**
 * Handles the whitelabeling of the plugin.
 *
 * @since 1.0.0
 */
class Noptin_White_Label {

    /**
     * The name of the plugin.
     *
     * @var string
     */
    public $name;

    /**
     * The version of the plugin.
     *
     * @var string
     */
    public $version;

    /**
     * The plugin icon image or URL..
     *
     * Either:-
     * 1. Pass a base64-encoded SVG using a data URI, which will be colored to match the color scheme. This should begin with 'data:image/svg+xml;base64,'.
     * 2. Pass the name of a Dashicons helper class to use a font icon, e.g. 'dashicons-chart-pie'.
     * 3. Pass 'none' to leave div.wp-menu-image empty so an icon can be added via CSS.
     * @var string
     */
    public $icon;

    /**
     * Class constructor.
     */
    public function __construct() {
        $this->name    = esc_html__( 'Noptin Newsletter', 'newsletter-optin-box' );
        $this->version = noptin()->version;
        $this->icon    = 'dashicons-forms';
    }

    /**
     * Returns the admin screen id prefix.
     *
     */
    public function admin_screen_id() {
        return sanitize_title( $this->name );
    }

    /**
     * Checks if the plugin is network activated.
     *
     * @return bool
     */
    public function is_network_activated() {
        return is_plugin_active_for_network( plugin_basename( noptin()->file ) );
    }
}
