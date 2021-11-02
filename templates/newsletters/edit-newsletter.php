<div class="wrap noptin-edit-campaign-page" id="noptin-wrapper">
	<?php
		printf(
			'<h1 class="title">%s<a class="page-title-action" href="%s">&nbsp;%s</a></h1>',
			esc_html__( 'Edit Newsletter','newsletter-optin-box' ),
			esc_url(
				add_query_arg(
					array(
						'sub_section' => false,
						'id' => false,
					)
				)
			),
			esc_html__( 'Go Back','newsletter-optin-box' )
		);
	?>

	<form name="noptin-edit-newsletter" method="post">
		<input type="hidden" name="noptin_admin_action" value="noptin_edit_newsletter">
		<input type="hidden" name="campaign_id" value="<?php echo esc_attr( $campaign->ID ); ?>">
		<?php
			wp_nonce_field( 'noptin-edit-newsletter', 'noptin-edit-newsletter-nonce' );
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce' );
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce' );
		?>		

		<div id="poststuff" style="margin-top: 24px;">
			<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

				<div id="post-body-content">

					<div id="titlediv">
						<div id="titlewrap">
							<label class="screen-reader-text" id="title-prompt-text" for="title"><?php _e( 'Email Subject', 'newsletter-optin-box' ); ?></label>
							<input type="text" name="email_subject" size="30" value="<?php echo esc_attr( $campaign->post_title ); ?>" placeholder="<?php esc_attr_e( "Enter your Email's Subject", 'newsletter-optin-box' ); ?>" id="title" spellcheck="true" autocomplete="off">
						</div>
					</div>

				</div>

				<div id="postbox-container-1" class="postbox-container">
    				<?php do_meta_boxes( 'noptin_page_noptin-newsletter', 'side', $campaign ); ?>
				</div>

				<div id="postbox-container-2" class="postbox-container">

					<?php
						/**
						 * Fires before printing the first row in the newsletter campaign editor
						 *
						 * @param object $campaign current campaign object
						 */
						//do_action( 'noptin_before_newsletter_editor_fields', $campaign );
					?>

					<?php do_meta_boxes( 'noptin_page_noptin-newsletter', 'normal', $campaign ); ?>

					<?php do_meta_boxes( 'noptin_page_noptin-newsletter', 'advanced', $campaign ); ?>

					<?php
						/**
						 * Fires after printing the first row in the newsletter campaign editor
						 *
						 * @param object $campaign current campaign object
						 */
						//do_action( 'noptin_after_newsletter_editor_fields', $campaign );
					?>

				</div>
			</div>
		</div>
	</form>
</div>
<script>jQuery(document).ready(function(){ postboxes.add_postbox_toggles('noptin_newsletters'); });</script>
<?php
