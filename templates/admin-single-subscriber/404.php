<div class="noptin-subscribers wrap">
<?php
printf(
	/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
	__( 'There is no subscriber with that id. %1$sGo back to the subscribers overview page%2$s.', 'newsletter-optin-box' ),
	'<a href="' . esc_url( urldecode( $_GET['return']) ) . '">',
	'</a>'
);
?>
</div>
