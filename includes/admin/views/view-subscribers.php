<?php

	$table = new Noptin_Subscribers_Table();
	$table->prepare_items();

?>

<div class="wrap noptin-subscribers-page" id="noptin-wrapper">

	<h1 class="wp-heading-inline">
		<span><?php echo esc_html( get_admin_page_title() ); ?></span>
		<a href="<?php echo esc_url( add_query_arg( 'add', 'true', admin_url( 'admin.php?page=noptin-subscribers' ) ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'newsletter-optin-box' ); ?></a>
	</h1>

	<?php noptin()->admin->show_notices(); ?>

	<div
		id="noptin-records__overview-app"
		data-namespace="noptin"
		data-collection="subscribers"
		data-title="<?php esc_attr_e( 'Subscribers', 'newsletter-optin-box' ); ?>"
	>
		<!-- Display a loading animation while the app is loading -->
		<div class="loading">
			<?php esc_html_e( 'Loading...', 'newsletter-optin-box' ); ?>
			<span class="spinner"></span>
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
