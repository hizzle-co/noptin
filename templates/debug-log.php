<?php
/**
 * Admin View: Page - Admin Tools > Debug Log
 */

defined( 'ABSPATH' ) || exit;

?>

<table class="noptin-admin-table noptin-admin-tools-table widefat" cellspacing="0">
	<thead>
		<tr class="noptin-tool-row>">
			<th style="padding-left: 1.5em;padding-right: 1.5em;"><strong><?php esc_html_e( 'Time', 'newsletter-optin-box' ); ?></strong></th>
			<td style="padding-left: 1.5em;padding-right: 1.5em;"><strong><?php esc_html_e( 'Message', 'newsletter-optin-box' ); ?></td>
		</tr>
	</thead>
	<tbody class="noptin-tools">
		<?php foreach ( $debug_log as $log ) : ?>
			<tr class="noptin-tool-row noptin-tool-<?php echo is_string( $log['level'] ) ? sanitize_html_class( $log['level'] ) : ''; ?>">
				<th><?php echo esc_html( $log['time'] ); ?></th>
				<td><pre><?php echo wp_kses( $log['msg'], 'user_description' ); ?></pre></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
