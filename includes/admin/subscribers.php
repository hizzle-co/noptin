<form action="" method="post">

	<?php wp_nonce_field( 'noptin', 'noptin_nonce' ); ?>

	<div class="noptin-subscribers wrap">
		<h1 class="wp-heading-inline"><?php _e( 'Email Subscribers',  'newsletter-optin-box')?></h1>
		<p><a href="https://noptin.com/product/mailchimp/" target="_blank">Connect your Mailchimp Account </a> to automatically add new email subscribers to your Mailchimp list.</p>
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
			<div class="tablenav-pages one-page"><span class="displaying-num"><a href="<?php echo $download_url;?>" class="button button-primary noptin-download"><?php esc_html_e('Download CSV',  'newsletter-optin-box'); ?></a></span></div>
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
					<th scope="col" id="title"><?php esc_html_e('Email Address',  'newsletter-optin-box')?></th>
					<th scope="col" id="author"><?php esc_html_e('First Name',  'newsletter-optin-box')?></th>
					<th scope="col" id="categories"><?php esc_html_e('Last Name',  'newsletter-optin-box')?></th>
					<th scope="col" id="date"><?php esc_html_e('Subscribed On',  'newsletter-optin-box')?></th>
				</tr>
			</thead>

			<tbody id="the-list">

				<?php foreach( $subscribers as $subscriber ){ ?>
				<tr>
					<th scope="row" class="check-column">
						<input type="checkbox" name="email[]" value="<?php echo esc_attr($subscriber->id); ?>">
					</th>
					<td><?php echo sanitize_text_field($subscriber->email); ?></td>
					<td><?php echo sanitize_text_field($subscriber->first_name); ?></td>
					<td><?php echo sanitize_text_field($subscriber->second_name); ?></td>
					<td><?php echo sanitize_text_field($subscriber->date_created); ?></td>
				</tr>
				<?php }?>

			</tbody>
			<tfoot>
				<td id="cb2" class="manage-column column-cb check-column">
					<input id="cb-select-all-1" type="checkbox"></td>
				<th scope="col" id="title"><?php esc_html_e('Email Address',  'newsletter-optin-box')?></th>
				<th scope="col" id="author"><?php esc_html_e('First Name',  'newsletter-optin-box')?></th>
				<th scope="col" id="categories"><?php esc_html_e('Last Name',  'newsletter-optin-box')?></th>
				<th scope="col" id="date"><?php esc_html_e('Subscribed On',  'newsletter-optin-box')?></th>
				</tr>
			</tfoot>

		</table>
	</div>
</form>
