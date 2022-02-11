<?php

	defined( 'ABSPATH' ) || exit;

	$email_type = $campaign->get_email_type();
	$template = $campaign->get_template();
	/**
	 * @var Noptin_Automated_Email $campaign
	 */

?>

<style>
	<?php
		foreach ( array_keys( get_noptin_email_types() ) as $key ) {
			echo '.noptin-automated-email:not([data-type="' . sanitize_html_class( $key ) . '"]) .noptin-show-if-automation-is-' . sanitize_html_class( $key ) . ' { display: none !important; }';
		}
	?>
</style>

<div class="wrap noptin-automation-campaign-form" id="noptin-wrapper">
	<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>
	<?php if ( $campaign->exists() ) : ?>
		<a href="<?php echo esc_url( noptin_get_new_automation_url() ); ?>" class="page-title-action"><?php echo _e( 'Add New', 'newsletter-optin-box' ); ?></a>
	<?php endif; ?>
	<hr class="wp-header-end">

	<form name="noptin-edit-automation"  class="noptin-automated-email" data-type="<?php echo esc_attr( $email_type ); ?>" method="post">

		<input type="hidden" name="noptin_admin_action" value="noptin_save_edited_automation">
		<input type="hidden" name="noptin_automation[automation_type]" value="<?php echo esc_attr( $campaign->type ); ?>">

		<?php if ( $campaign->exists() ) : ?>
			<input type="hidden" name="noptin_automation[id]" value="<?php echo esc_attr( $campaign->id ); ?>">
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
								<label class="screen-reader-text" id="title-prompt-text" for="title"><?php _e( 'Email Subject', 'newsletter-optin-box' ); ?></label>
								<input type="text" name="noptin_automation[title]" size="30" value="<?php echo esc_attr( $campaign->get( 'name' ) ); ?>" placeholder="<?php esc_attr_e( 'Enter automation name', 'newsletter-optin-box' ); ?>" id="title" spellcheck="true" autocomplete="off">
							</div>
						</div>

					</div>

					<table class="form-table" role="presentation">
						<tbody>

							<tr>
								<th scope="row">
									<label for="noptin-automated-email-subject"><?php _e( 'Email Subject', 'newsletter-optin-box' ); ?></label>
								</th>
								<td>
									<input type="text" id="noptin-automated-email-subject" name="noptin_automation[subject]" value="<?php echo esc_attr( $campaign->get_subject() ); ?>" class="noptin-admin-field-big" required>
									<p class="description"><?php _e( "<strong>Tip:</strong> Keep it short and intriguing.", 'newsletter-optin-box' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="noptin-automated-email-type"><?php _e( 'Email Type', 'newsletter-optin-box' ); ?></label>
								</th>
								<td>
									<select name="noptin_automation[email_type]" id="noptin-automated-email-type" class="noptin-admin-field-big">
										<?php foreach ( get_noptin_email_types() as $key => $type ) : ?>
											<option <?php selected( $key, $email_type ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $type['label'] ); ?></option>
										<?php endforeach; ?>
									</select>
									<?php foreach ( get_noptin_email_types() as $key => $type ) : ?>
										<p class="description noptin-is-conditional noptin-show-if-automation-is-<?php echo sanitize_html_class( $key ); ?>"><strong><?php echo esc_html( $type['label'] ); ?>:</strong> <?php echo wp_kses_post( $type['description'] ); ?></p>
									<?php endforeach; ?>
								</td>
							</tr>

							<tr class="noptin-is-conditional noptin-show-if-automation-is-normal">
								<th scope="row">
									<label for="noptin-automated-email-template"><?php _e( 'Email Template', 'newsletter-optin-box' ); ?></label>
								</th>
								<td>
									<select name="noptin_automation[template]" id="noptin-automated-email-template" class="noptin-admin-field-big">
										<?php foreach ( get_noptin_email_templates() as $key => $label ) : ?>
											<option <?php selected( $key, $template ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php _e( 'Select which template to use when formating your email or select "None" to disable.', 'newsletter-optin-box' ); ?></p>
								</td>
							</tr>

							<tr class="noptin-is-conditional noptin-show-if-automation-is-normal">
								<th scope="row">
									<label for="noptin-automated-email-heading"><?php _e( 'Email Heading', 'newsletter-optin-box' ); ?></label>
								</th>
								<td>
									<input type="text" id="noptin-automated-email-heading" name="noptin_automation[heading]" value="<?php echo esc_attr( $campaign->get( 'heading' ) ); ?>" class="noptin-admin-field-big">
								</td>
							</tr>

							<tr class="noptin-is-conditional noptin-show-if-automation-is-normal">
								<th scope="row">
									<label for="noptin-automated-email-permission-text"><?php _e( 'Footer Text', 'newsletter-optin-box' ); ?></label>
								</th>
								<td>
									<textarea id="noptin-automated-email-permission-text" name="noptin_automation[footer_text]" class="noptin-admin-field-big" placeholder="<?php echo esc_attr( get_noptin_footer_text() ); ?>" rows="2"><?php echo esc_textarea( $campaign->get( 'footer_text' ) ); ?></textarea>
									<p class="description"><?php _e( 'This text appears below the main email content.', 'newsletter-optin-box' ); ?></p>
								</td>
							</tr>

						</tbody>
					</table>

				</div>

				<div id="postbox-container-1" class="postbox-container">
    				<?php do_meta_boxes( 'noptin_page_noptin-automation', 'side', $campaign ); ?>
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
						do_meta_boxes( 'noptin_page_noptin-automation', 'normal', $campaign );

						// Print advanced metaboxes.
						do_meta_boxes( 'noptin_page_noptin-automation', 'advanced', $campaign );

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
</div>
<script>jQuery(document).ready(function(){ postboxes.add_postbox_toggles('noptin_newsletters'); });</script>
<?php
