<?php

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var Noptin_Newsletter_Email $campaign
	 */

	$noptin_screen_id = get_current_screen()->id;
	$email_type       = $campaign->get_email_type();
?>

<style>
	<?php
		foreach ( array_keys( get_noptin_email_types() ) as $key ) {
			echo '.noptin-edit-email:not([data-type="' . esc_html( sanitize_html_class( $key ) ) . '"]) .noptin-show-if-email-is-' . esc_html( sanitize_html_class( $key ) ) . ' { display: none !important; }';
		}
	?>
</style>

<form name="noptin-edit-newsletter" class="noptin-newsletter-email noptin-edit-email" data-type="<?php echo esc_attr( $email_type ); ?>" method="post">

	<input type="hidden" name="noptin_admin_action" value="noptin_save_edited_newsletter">
	<input type="hidden" name="noptin_is_newsletter" value="1">
	<input type="hidden" name="noptin_email[status]" value="<?php echo esc_attr( $campaign->status ); ?>">

	<?php if ( $campaign->exists() ) : ?>
		<input type="hidden" name="noptin_email[id]" value="<?php echo esc_attr( $campaign->id ); ?>">
		<input type="hidden" name="noptin_email[parent_id]" value="<?php echo esc_attr( $campaign->parent_id ); ?>">
	<?php endif; ?>

	<?php
		wp_nonce_field( 'noptin-edit-newsletter', 'noptin-edit-newsletter-nonce' );
		wp_nonce_field( 'noptin-admin-nonce', 'noptin-admin-nonce' );
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce' );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce' );
	?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-<?php echo 1 === get_current_screen()->get_columns() ? '1' : '2'; ?>">

			<div id="post-body-content">

				<div id="titlediv">
					<div id="titlewrap">
						<label class="screen-reader-text" id="title-prompt-text" for="title"><?php esc_html_e( 'Email Subject', 'newsletter-optin-box' ); ?></label>
						<input type="text" name="noptin_email[subject]" size="30" value="<?php echo esc_attr( $campaign->subject ); ?>" placeholder="<?php esc_attr_e( 'Email Subject', 'newsletter-optin-box' ); ?>" id="title" spellcheck="true" autocomplete="off">
					</div>
				</div>

			</div>

			<div id="postbox-container-1" class="postbox-container">
				<?php do_meta_boxes( $noptin_screen_id, 'side', $campaign ); ?>
			</div>

			<div id="postbox-container-2" class="postbox-container">

				<?php
					/**
					 * Fires before printing the first metabox in the email campaign editor
					 *
					 */
					do_action( 'noptin_before_email_editor_fields', $campaign );

					// Print normal metaboxes.
					do_meta_boxes( $noptin_screen_id, 'normal', $campaign );

					// Print advanced metaboxes.
					do_meta_boxes( $noptin_screen_id, 'advanced', $campaign );

					/**
					 * Fires after printing the last metabox in the email campaign editor
					 *
					 */
					do_action( 'noptin_after_email_editor_fields', $campaign );
				?>

			</div>
		</div>
	</div>
</form>

<script>jQuery(document).ready(function(){ postboxes.add_postbox_toggles('noptin_newsletter'); });</script>
<?php
