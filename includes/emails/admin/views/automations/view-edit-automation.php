<?php

	defined( 'ABSPATH' ) || exit;

	$noptin_screen_id = get_current_screen() ? get_current_screen()->id : 'noptin_page_noptin-automation';
	$email_type       = $campaign->get_email_type();
	/**
	 * @var Noptin_Automated_Email $campaign
	 */

?>

<style>
	<?php
		foreach ( array_keys( get_noptin_email_types() ) as $key ) {
			echo '.noptin-edit-email:not([data-type="' . sanitize_html_class( $key ) . '"]) .noptin-show-if-email-is-' . sanitize_html_class( $key ) . ' { display: none !important; }';
		}
	?>
</style>

<form name="noptin-edit-automation" class="noptin-automated-email noptin-edit-email" data-type="<?php echo esc_attr( $email_type ); ?>" method="post">

	<input type="hidden" name="noptin_admin_action" value="noptin_save_edited_automation">
	<input type="hidden" name="noptin_is_automation" value="1">
	<input type="hidden" name="noptin_email[automation_type]" value="<?php echo esc_attr( $campaign->type ); ?>">

	<?php if ( $campaign->exists() ) : ?>
		<input type="hidden" name="noptin_email[id]" value="<?php echo esc_attr( $campaign->id ); ?>">
	<?php endif; ?>

	<?php
		wp_nonce_field( 'noptin-edit-automation', 'noptin-edit-automation-nonce' );
		wp_nonce_field( 'noptin-admin-nonce', 'noptin-admin-nonce' );
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce' );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce' );
	?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-<?php echo ( 1 === get_current_screen()->get_columns() ) ? '1' : '2'; ?>">
			<div id="post-body-content">

				<div id="post-body-content">

					<div id="titlediv">
						<div id="titlewrap">
							<label class="screen-reader-text" id="title-prompt-text" for="title"><?php esc_html_e( 'Email Subject', 'newsletter-optin-box' ); ?></label>
							<input type="text" name="noptin_email[subject]" size="30" value="<?php echo esc_attr( $campaign->get_subject() ); ?>" placeholder="<?php esc_attr_e( 'Email Subject', 'newsletter-optin-box' ); ?>" id="title" spellcheck="true" autocomplete="off">
							<p class="description"><?php esc_html_e( 'The subject of the email.', 'newsletter-optin-box' ); ?></p>
						</div>
					</div>

				</div>

			</div>

			<div id="postbox-container-1" class="postbox-container">
    			<?php do_meta_boxes( $noptin_screen_id, 'side', $campaign ); ?>
			</div>

			<div id="postbox-container-2" class="postbox-container">

				<?php
					/**
					 * Fires before printing the first metabox in the automation campaign editor
					 *
					 * @param Noptin_Automated_Email $campaign current campaign object
					 */
					do_action( 'noptin_before_automation_editor_fields', $campaign, $automation_type );

					// Print normal metaboxes.
					do_meta_boxes( $noptin_screen_id, 'normal', $campaign );

					// Print advanced metaboxes.
					do_meta_boxes( $noptin_screen_id, 'advanced', $campaign );

					/**
					 * Fires after printing the last metabox in the automation campaign editor
					 *
					 * @param Noptin_Automated_Email $campaign current campaign object
					 */
					do_action( 'noptin_after_automation_editor_fields', $campaign, $automation_type );
				?>
			</div>
		</div>
	</div>
</form>
<script>jQuery(document).ready(function(){ postboxes.add_postbox_toggles('noptin_automated_email'); });</script>
<?php
