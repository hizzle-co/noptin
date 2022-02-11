<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var Noptin_Automated_Email $campaign
 */

?>

<div class="noptin-is-conditional noptin-show-if-automation-is-normal">
	<?php
		$content = wp_kses_post( $campaign->get_content() );

		wp_editor(
            $content,
			'noptin-automation-email-content',
			array(
				'media_buttons'    => true,
				'drag_drop_upload' => true,
				'textarea_rows'    => 15,
				'textarea_name'    => 'noptin_automation[content_normal]',
				'tabindex'         => 4,
				'tinymce'          => array(
					'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect',
				),
			)
		);
	?>
</div>

<div class="noptin-is-conditional noptin-show-if-automation-is-plain_text">
	<p><textarea name="noptin_automation[content_plain_text]" id="noptin-automation-email-content-plain-text" rows="15" class="widefat"><?php echo esc_textarea( $campaign->get_content( 'plain_text' ) ); ?></textarea></p>
	<p class="description"><?php _e( 'Any HTML will be stripped from your email.', 'newsletter-optin-box' ); ?></p>
</div>

<div class="noptin-is-conditional noptin-show-if-automation-is-raw_html">
	<p><textarea name="noptin_automation[content_raw_html]" id="noptin-automation-email-content-raw-html" rows="15" class="widefat"><?php echo esc_textarea( $campaign->get_content( 'raw_html' ) ); ?></textarea></p>
	<p class="description"><?php _e( 'Paste your HTML email above.', 'newsletter-optin-box' ); ?></p>
</div>
