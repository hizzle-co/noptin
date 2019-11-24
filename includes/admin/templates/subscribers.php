<form action="" method="post">

	<?php wp_nonce_field( 'noptin', 'noptin_nonce' ); ?>

	<div class="noptin-subscribers wrap">
		<h1 class="wp-heading-inline"><?php _e( 'Email Subscribers',  'newsletter-optin-box')?><em> (<?php echo $subscribers_total;?>)</em></h1>
		<p><a href="https://noptin.com/products/" target="_blank">Check out our integrations</a></p>
		<div class="noptin-divider"></div>
		<div class="tablenav top">

			<div class="alignleft actions bulkactions">
				<label for="bulk-action-selector-top" class="screen-reader-text"><?php _e( 'Select bulk action',  'newsletter-optin-box' ); ?></label>
					<select name="action" id="bulk-action-selector-top">
					<option value="-1"><?php _e( 'Bulk Actions',  'newsletter-optin-box')?></option>
					<option value="delete"><?php _e( 'Delete',  'newsletter-optin-box')?></option>
				</select>
				<input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply',  'newsletter-optin-box')?>">
			</div>
			<div class="tablenav-pages one-page"><span class="displaying-num"><a href="<?php echo $download_url;?>" class="button button-link noptin-download"><?php esc_html_e('Download Subscribers',  'newsletter-optin-box'); ?></a></span></div>
			<br class="clear">
		</div>

		<?php if( $deleted ) { ?>
			<div class="noptin-save-saved" style="margin-top: 20px !important"><p><?php _e( 'The selected subscribers have been deleted.',  'newsletter-optin-box')?></p></div>
		<?php } ?>

		<table class="wp-list-table widefat fixed striped posts">
			<thead>
				<tr>
					<td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1"
							type="checkbox"></td>
					<th scope="col" id="title"><?php esc_html_e('Subscriber',  'newsletter-optin-box')?></th>
					<th scope="col" id="date"><?php esc_html_e('Subscription Date',  'newsletter-optin-box')?></th>
					<th scope="col" id="date"><?php esc_html_e('Status',  'newsletter-optin-box')?></th>
				</tr>
			</thead>

			<tbody id="the-list">

				<?php foreach( $subscribers as $subscriber ){ ?>
				<tr>
					<th scope="row" class="check-column">
						<input type="checkbox" name="email[]" value="<?php echo esc_attr($subscriber->id); ?>">
					</th>
					<td><strong><a href="<?php echo esc_url( add_query_arg( 'subscriber', $subscriber->id )); ?>"><?php echo sanitize_text_field($subscriber->email); ?></a></strong></td>
					<td><?php echo date( 'D, jS M Y', strtotime( $subscriber->date_created )); ?></td>
					<td><?php echo $subscriber->active == 0 ? 'Active' : 'Inactive'; ?></td>
				</tr>
				<?php }?>

			</tbody>
			<tfoot>
				<td id="cb2" class="manage-column column-cb check-column">
					<input id="cb-select-all-1" type="checkbox"></td>
					<th scope="col" id="title"><?php esc_html_e('Subscriber',  'newsletter-optin-box')?></th>
					<th scope="col" id="date"><?php esc_html_e('Subscription Date',  'newsletter-optin-box')?></th>
					<th scope="col" id="date"><?php esc_html_e('Status',  'newsletter-optin-box')?></th>
				</tr>
			</tfoot>

		</table>
		<div style="margin-top: 16px;">
			<?php

				// Previous page
				if( $page > 1 ) {

					printf(
						__('%sPrevious Page%s',  'newsletter-optin-box'),
						'<a href="' . esc_url( get_noptin_subscribers_overview_url( $page - 1 ) ) . '">',
						'</a>'
					);

				}

				// Page separators
				if( $page > 1 && $pages > $page ) {
					echo " | ";
				}

				// Next page
				if( $pages > $page ) {

					printf(
						__('%sNext Page%s',  'newsletter-optin-box'),
						'<a href="' . esc_url( get_noptin_subscribers_overview_url( $page + 1 ) ) . '">',
						'</a>'
					);

				}
			?>
		</div>
	</div>
</form>
