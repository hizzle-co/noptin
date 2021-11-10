<div class="wrap noptin-import-subscribers-page" id="noptin-wrapper">

	<h1 class="title"><?php esc_html_e( 'Import Subscribers', 'newsletter-optin-box' ); ?></h1>

	<?php include plugin_dir_path( __FILE__ ) . 'subscriber-tabs.php' ?>

	<form class="noptin-import-subscribers-form" method="POST">

		<header>
			<p class="description"><?php
				_e( 'This tool allows you to import newsletter subscribers from a CSV file into Noptin.', 'newsletter-optin-box' );

				printf(
					' ' . __( 'If your file is in another format such as XML or JSON, %sstart by converting it to CSV%s.', 'newsletter-optin-box' ),
					'<a href="https://convertio.co/csv-converter/">',
					'</a>'
				);
			?></p>
		</header>

		<section>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="noptin-upload"><?php _e( 'Choose a CSV file from your computer:', 'newsletter-optin-box' ); ?></label>
						</th>
						<td><input type="file" id="noptin-upload" accept=".csv" onchange="jQuery('.noptin-import-continue').removeAttr('disabled')"></td>
					</tr>
					<tr>
						<th><label for="noptin-importer-update-existing"><?php _e( 'Update existing subscribers', 'newsletter-optin-box' ); ?></label></th>
						<td>
							<input type="checkbox" id="noptin-importer-update-existing" checked="checked" value="1">
							<label for="noptin-importer-update-existing"><?php _e( 'Existing subscribers that match by email address will be updated.', 'newsletter-optin-box' ); ?></label>
						</td>
					</tr>
			</table>
		</section>

		<footer>
			<span style="display: inline-block;">
				<button type="submit" class="button button-primary noptin-import-continue" value="<?php esc_attr_e( 'Continue', 'newsletter-optin-box'); ?>" disabled><?php esc_html_e( 'Continue', 'newsletter-optin-box'); ?></button>
				<span class="spinner"></span>
			</span>
		</footer>
	</form>

</div>
