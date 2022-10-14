<?php
/**
 * @var Noptin_Subscriber $subscriber
 */

defined( 'ABSPATH' ) || exit;

$activities = array(
	__( 'Subscribed', 'newsletter-optin-box' ) => $subscriber->date_created,
);

if ( $subscriber->confirmed && ! empty( $subscriber->confirmed_on ) ) {
	$activities[ __( 'Confirmed Email Address', 'newsletter-optin-box' ) ] = $subscriber->confirmed_on;
}

if ( ! $subscriber->is_active() && ! empty( $subscriber->unsubscribed_on ) ) {
	$activities[ __( 'Unsubscribed', 'newsletter-optin-box' ) ] = $subscriber->unsubscribed_on;
}

// Record the activity.
$extra_activity = get_noptin_subscriber_meta( $subscriber->id, '_subscriber_activity', true );

if ( is_array( $extra_activity ) && function_exists( 'wp_date' ) ) {

	foreach ( $extra_activity as $activity ) {

		if ( ! empty( $activity['time'] ) ) {
			$activities[ $activity['content'] ] = wp_date( 'Y-m-d H:i:s', $activity['time'] );
		}
	}
}

$activities = apply_filters( 'noptin_subscriber_activity_feed', $activities, $subscriber );

uasort(
	$activities,
	function( $a, $b ) {
		return strtotime( $a ) - strtotime( $b );
	}
);

?>

<div style="overflow-x: auto;">
	<table class="form-table">
		<tbody>
			<?php foreach ( $activities as $activity => $date ) : ?>
				<tr>
					<th scope="row" style="font-weight: 400; color: #757575;">
						<?php echo esc_html( $date ); ?>
					</th>
					<td>
						<?php echo wp_kses_post( $activity ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
