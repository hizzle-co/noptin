<?php $preview = is_object( $campaign ) ? get_post_meta( $campaign->ID, 'preview_text', true ) : ''; ?>

<textarea style="width: 100%;" name="preview_text" id="noptin-email-preview" class="regular-textarea" rows="3"><?php echo esc_textarea( $preview ); ?></textarea>

<p class="description"><?php esc_html_e( 'Some email clients display this text next to the email subject.', 'newsletter-optin-box' ); ?></span>