<div class="noptin-subscribers wrap">
<?php
printf(
	__('There is no subscriber with that id. %sGo back to the subscribers overview page%s.',  'newsletter-optin-box'),
	'<a href="' . esc_url( get_noptin_subscribers_overview_url() ) . '">',
	'</a>'
);
?>
</div>
