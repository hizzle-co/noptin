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
     * The support URL.
     */
    public $support_url;

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
     * Sets values.
     *
     * @param array $args The whitelabel options.
     */
    public function set( $args = array() ) {
        foreach ( $args as $key => $value ) {
            if ( property_exists( $this, $key ) && ! empty( $value ) ) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Retrieves a white label option.
     *
     * @param string $option The option name.
     * @param mixed  $default_value The default value.
     */
    public function get( $option, $default_value = '' ) {

        // Check if the property is set.
        if ( ! empty( $this->{$option} ) ) {
            return $this->{$option};
        }

        return $default_value;
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
            'menu'    => $this->get_menu(),
        );
    }

    /**
     * Returns the plugin menu.
     *
     * @return array
     */
    public function get_menu() {
        $current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

        $menu = array(
            'dashboard'     => array(
                'text'      => __( 'Dashboard', 'newsletter-optin-box' ),
                'href'      => admin_url( 'admin.php?page=noptin' ),
                'isPressed' => 'noptin' === $current_page,
            ),
            'forms'         => array(
                'text'      => __( 'Forms', 'newsletter-optin-box' ),
                'href'      => admin_url( 'edit.php?post_type=noptin-form' ),
                'isPressed' => 'noptin-forms' === $current_page,
            ),
            'subscribers'   => array(
                'text'      => __( 'Subscribers', 'newsletter-optin-box' ),
                'href'      => admin_url( 'admin.php?page=noptin-subscribers' ),
                'isPressed' => 'noptin-subscribers' === $current_page,
            ),
            'emails'        => array(
                'text'      => __( 'Emails', 'newsletter-optin-box' ),
                'href'      => admin_url( 'admin.php?page=noptin-email-campaigns' ),
                'isPressed' => 'noptin-email-campaigns' === $current_page,
            ),
            'automation'    => array(
                'text'      => __( 'Automation', 'newsletter-optin-box' ),
                'href'      => admin_url( 'admin.php?page=noptin-automation-rules' ),
                'isPressed' => 'noptin-automation-rules' === $current_page,
            ),
            'settings'      => array(
                'text'      => __( 'Settings', 'newsletter-optin-box' ),
                'href'      => admin_url( 'admin.php?page=noptin-settings' ),
                'isPressed' => 'noptin-settings' === $current_page,
            ),
            'tools'         => array(
                'text'      => esc_html__( 'Tools', 'newsletter-optin-box' ),
                'href'      => admin_url( 'admin.php?page=noptin-tools' ),
                'isPressed' => 'noptin-tools' === $current_page,
            ),
            'addons'        => array(
                'text'      => esc_html__( 'Add-ons', 'newsletter-optin-box' ),
                'href'      => admin_url( 'admin.php?page=noptin-addons' ),
                'isPressed' => 'noptin-addons' === $current_page,
            ),
            'documentation' => array(
                'text'      => __( 'Need Help?', 'newsletter-optin-box' ),
                'href'      => empty( $this->support_url ) ? noptin_get_guide_url( 'Admin Menu' ) : $this->support_url,
                'isPressed' => false,
            ),
        );

        foreach ( $menu as $key => $item ) {
            if ( 'none' === ( $item['href'] ?? 'none' ) || ! apply_filters( "noptin_show_{$key}_page", true ) ) {
                unset( $menu[ $key ] );
                continue;
            }

            $menu[ $key ]['className'] = apply_filters( "noptin_nav_menu_{$key}_class", 'noptin-menu-' . $key );
        }

        return apply_filters( 'noptin_white_label_menu', array_values( $menu ) );
    }
}
