<?php
/**
 * Admin View: Page - Admin Tools
 */

defined( 'ABSPATH' ) || exit;

?>
<table class="noptin-admin-table noptin-admin-tools-table widefat" cellspacing="0">
	<tbody class="noptin-tools">
		<?php foreach ( $tools as $tool_action => $tool ) : ?>
			<tr class="noptin-tool-row noptin-tool-<?php echo sanitize_html_class( $tool_action ); ?>">
				<th>
					<strong class="name"><?php echo esc_html( $tool['name'] ); ?></strong>
					<p class="description"><?php echo wp_kses_post( $tool['desc'] ); ?></p>
				</th>
				<td class="noptin-run-tool">
					<a
						href="<?php echo isset( $tool['url'] ) ? esc_url( $tool['url'] ) : esc_url( wp_nonce_url( admin_url( 'admin.php?page=noptin-tools&tool=' . $tool_action ), 'noptin_tool' ) ); ?>"
						class="button button-large noptin-button-tool-<?php echo esc_attr( $tool_action ); ?>"
						<?php if ( ! empty( $tool['confirm'] ) ) : ?>
						onclick="return confirm('<?php echo esc_attr( $tool['confirm'] ); ?>')"
						<?php endif; ?>
					><?php echo esc_html( $tool['button'] ); ?></a>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
