<?php

	$table = new Noptin_Subscribers_Table();
	$table->prepare_items();

?>

<div class="wrap noptin-subscribers-page" id="noptin-wrapper">

	<h1 class="wp-heading-inline">
		<span><?php echo esc_html( get_admin_page_title() ); ?></span>
		<a href="<?php echo esc_url( add_query_arg( 'add', 'true', admin_url( 'admin.php?page=noptin-subscribers' ) ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'newsletter-optin-box' ); ?></a>
	</h1>

	<?php require plugin_dir_path( __FILE__ ) . 'subscriber-tabs.php'; ?>

	<?php noptin()->admin->show_notices(); ?>

	<div id="noptin-records__overview-app" data-namespace="noptin" data-collection="subscribers"></div>

	<form id="noptin-subscribers-table" class="noptin-enhanced-table" method="GET" action="<?php echo esc_url( add_query_arg( 'page', 'noptin-subscribers', admin_url( 'admin.php' ) ) ); ?>">
		<input type="hidden" name="page" value="noptin-subscribers" />
		<?php $table->search_box( __( 'Search Subscribers', 'newsletter-optin-box' ), 'post' ); ?>
		<?php $table->display(); ?>
	</form>

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
