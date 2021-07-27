<?php if ( ! empty( $preview_text ) ) { ?>
	<?php if ( ! class_exists( 'Email_Customizer_Mailer' ) ) { ?>
	<div class="preheader" style="display: none; max-width: 0; max-height: 0; overflow: hidden; font-size: 1px; line-height: 1px; color: #fff; opacity: 0;">
		<?php echo $preview_text; ?>
	</div>
	<?php

		} else {
			Email_Customizer_Mailer::$preview_text = $preview_text;
		}

}
