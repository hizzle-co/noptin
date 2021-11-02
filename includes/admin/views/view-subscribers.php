<?php

	$table = new Noptin_Subscribers_Table();
	$table->prepare_items();

	$data       = '';
	$data_array = apply_filters( 'noptin_subscribers_page_extra_ajax_data', $_GET );
	foreach( $data_array as $key => $value ) {

		if ( is_scalar( $value ) ) {
			$value = esc_attr( urldecode( $value ) );
			$key   = esc_attr( $key );
			$data .= " data-$key='$value'";
		}

	}

?>

<div class="wrap noptin-subscribers-page" id="noptin-wrapper">

	<h1 class="wp-heading-inline">
		<span><?php echo esc_html( get_admin_page_title() ); ?></span>
		<a href="<?php echo esc_url( add_query_arg( 'add', 'true', admin_url( 'admin.php?page=noptin-subscribers' ) ) ); ?>" class="page-title-action"><?php _e( 'Add New', 'newsletter-optin-box' ); ?></a>
	</h1>

	<?php include plugin_dir_path( __FILE__ ) . 'subscriber-tabs.php' ?>

	<?php noptin()->admin->show_notices(); ?>

	<form id="noptin-subscribers-table" method="POST" action="<?php echo remove_query_arg( array( 'delete-subscriber', '_wpnonce' ) ); ?>">
		<?php $table->search_box( __( 'Search Subscribers', 'newsletter-optin-box' ), 'noptin_search_subscribers'); ?>
		<?php $table->display(); ?>
	</form>

	<p class="description"><?php
		printf(
			__( 'Store more information about your subscribers by %1$screating custom fields%2$s.', 'newsletter-optin-box' ),
			'<a href="' . esc_url_raw( admin_url( 'admin.php?page=noptin-settings&tab=fields' ) ) . '">',
			'</a>'
		);
	?></p>

	<div id='noptin-subscribers-page-data' <?php echo $data; ?>></div>
</div>
