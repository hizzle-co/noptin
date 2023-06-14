<?php
/**
 * Displays the subscribers page.
 *
 * @since 1.2.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<style>.notice:not(.noptin-badge), .error:not(.noptin-badge) {display:none !important;}</style>

<div class="wrap noptin-subscribers-page" id="noptin-wrapper">

	<div id="noptin-collection__overview-app" data-default-route="/noptin/subscribers">
		<!-- Display a loading animation while the app is loading -->
		<div class="loading">
			<?php esc_html_e( 'Loading...', 'newsletter-optin-box' ); ?>
			<span class="spinner" style="float: none; visibility: visible;">
				<span class="spinner-icon"></span>
			</span>
		</div>
	</div>

</div>
