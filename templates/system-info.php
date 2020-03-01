<?php
/**
 * Admin View: Page - Admin Tools > System Info
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="notice">
	<p><?php _e( 'Please copy and paste this information in your ticket when contacting support:', 'newsletter-optin-box' ); ?> </p>
	<p class="submit"><a href="#" onClick="document.getElementById('noptin-system-report-text').style.display = 'block'; document.getElementById('noptin-system-report-text').focus(); document.getElementById('noptin-system-report-text').select(); return false;" class="button-primary noptin-get-system-report"><?php _e( 'Get system report', 'newsletter-optin-box' ); ?></a></p>
	<textarea rows="10" id="noptin-system-report-text" readonly="readonly" style="width: 100%;overflow: auto;display:none;"><?php echo strip_tags( html_entity_decode( $text ) ); ?></textarea>
</div>

<?php foreach ( $info as $category => $data ) : ?>

	<table class="noptin-admin-table noptin-admin-tools-table widefat" cellspacing="0">

		<thead>
			<tr>
				<th colspan="2"><strong><?php echo esc_html( $category ); ?></strong></th>
			</tr>
		</thead>

		<tbody>

			<?php foreach ( $data as $key => $value ) : ?>
				<tr>
					<th><?php echo wp_kses( $key, 'user_description' ); ?></th>
					<td><?php echo wp_kses( $value, 'user_description' ); ?></td>
				</tr>
			<?php endforeach; ?>

		</tbody>
	</table>
<?php endforeach; ?>
