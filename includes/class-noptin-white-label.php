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
     * The plugin logo image URL.
     */
    public $logo;

    /**
     * Class constructor.
     */
    public function __construct() {
        $this->name    = 'Noptin';
        $this->version = noptin()->version;
        $this->icon    = 'dashicons-forms';
        $this->logo    = noptin()->plugin_url . 'includes/assets/images/logo.png';
    }

    /**
     * Retrieves a white label option.
     *
     * @param string $option The option name.
     * @param mixed  $default The default value.
     */
    public function get( $option, $default = '' ) {

        // Check if the property is set.
        if ( isset( $this->{$option} ) ) {
            return $this->{$option};
        }

        return $default;
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

    /**
     * Returns the whitelabel details.
     *
     * @return array
     */
    public function get_details() {
        return array(
            'name'    => $this->name,
            'version' => $this->version,
            'icon'    => $this->icon,
            'logo'    => $this->logo,
        );
    }
}
