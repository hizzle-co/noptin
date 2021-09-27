<?php
    $geolocation = noptin_locate_ip_address( $subscriber->ip_address );

    if ( ! is_array( $geolocation ) ) {
        return;
    }

    $fields      = array(
        'continent' => __( 'Continent', 'newsletter-optin-box' ),
        'country'   => __( 'Country', 'newsletter-optin-box' ),
        'state'     => __( 'State', 'newsletter-optin-box' ),
        'city'      => __( 'City', 'newsletter-optin-box' ),
        'latitude'  => __( 'Latitude', 'newsletter-optin-box' ),
        'longitude' => __( 'Longitude', 'newsletter-optin-box' ),
        'currency'  => __( 'Currency', 'newsletter-optin-box' ),
        'time zone' => __( 'Time Zone', 'newsletter-optin-box' ),
    );
?>

<?php
    foreach ( array_keys( $fields ) as $field ) {

        if ( ! isset( $geolocation[ $field ] ) ) {
            return;
        }

        $value = esc_html( $geolocation[ $field ] );

        if ( 'country' === $field && isset( $geolocation['country_flag'] ) ) {
            $url   = esc_url( $geolocation['country_flag'] );
            $value = "<img src='$url' width='20px' height='auto'>&nbsp;$value";
        }

        if ( 'country' === $field && isset( $geolocation['calling_code'] ) ) {
            $code  = intval( $geolocation['calling_code'] );
            $value = "$value ( +$code )";
        }
?>

    <div class="misc-pub-section misc-pub-noptin-subscriber-geolocate-<?php echo esc_attr( $field ); ?>">
	    <span id="noptin-subscriber-geolocate-<?php echo esc_attr( $field ); ?>">
        <span class="dashicons dashicons-plus" style="padding-right: 3px; color: #607d8b"></span>
            <?php echo $fields[ $field ]; ?>:&nbsp;<b><?php echo $value; ?></b>
        </span>
    </div>

<?php }
