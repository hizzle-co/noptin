<?php

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

<div class="wrap noptin-single-subscriber-page" id="noptin-wrapper">
	<h1 class="title">
		<?php esc_html_e( 'Subscriber','newsletter-optin-box' ); ?>
		<a href="<?php echo esc_url( add_query_arg( 'add', 'true', admin_url( 'admin.php?page=noptin-subscribers' ) ) ); ?>" class="page-title-action"><?php _e( 'Add New', 'newsletter-optin-box' ); ?></a>
	</h1>

	<form name="noptin-edit-subscriber" method="post">
		<input type="hidden" name="noptin_admin_action" value="noptin_update_admin_edited_subscriber">
		<input type="hidden" name="subscriber_id" value="<?php echo esc_attr( $subscriber->id ); ?>">
		<?php
			wp_nonce_field( 'noptin-admin-update-subscriber', 'noptin-admin-update-subscriber-nonce' );
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
        	wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		?>		

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

				<div id="postbox-container-1" class="postbox-container">
    				<?php do_meta_boxes( 'noptin_page_noptin-subscribers', 'side', $subscriber ); ?>
				</div>

				<div id="postbox-container-2" class="postbox-container">
    				<?php do_meta_boxes( 'noptin_page_noptin-subscribers', 'normal', $subscriber ); ?>
					<?php do_meta_boxes( 'noptin_page_noptin-subscribers', 'advanced', $subscriber ); ?>
					<?php do_action( 'noptin_single_subscriber', (object) $subscriber->to_array(), $subscriber->get_meta() ); ?>
				</div>
			</div>
		</div>
	</form>
</div>
<script>jQuery(document).ready(function(){ postboxes.add_postbox_toggles('noptin_subscribers'); });</script>
<?php
