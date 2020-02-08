<div class="noptin-subscribers wrap">
<?php
printf(
	__( 'There is no subscriber with that id. %1$sGo back to the subscribers overview page%2$s.', 'newsletter-optin-box' ),
	'<a href="' . esc_url( get_noptin_subscribers_overview_url() ) . '">',
	'</a>'
);
?>
</div>
