<?php
    $geolocation = noptin_locate_ip_address( $subscriber->ip_address );
    $fields      = array(
        'continent',
        'country',
        'state',
        'city',
        'zipcode',
        'latitude',
        'longitude',
        'currency',
        'time zone'
    );
?>

<?php
    foreach ( $fields as $field ) {

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
            <?php echo ucfirst( esc_html( $field ) ); ?>:&nbsp;<b><?php echo $value; ?></b>
        </span>
    </div>

<?php }
