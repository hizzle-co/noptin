<?php
/**
 * Displays the subscribers page.
 *
 * @since 1.2.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<style>.notice{display:none !important;}</style>

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

	<p class="description">
		<?php
			printf(
				// translators: %1$s Opening link tag, %2$s Closing link tag.
				esc_html__( 'Store more information about your subscribers by %1$screating custom fields%2$s.', 'newsletter-optin-box' ),
				'<a href="' . esc_url_raw( admin_url( 'admin.php?page=noptin-settings&tab=fields' ) ) . '">',
				'</a>'
			);
		?>
	</p>

</div>
