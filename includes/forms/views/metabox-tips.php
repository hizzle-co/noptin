<?php defined( 'ABSPATH' ) || exit; ?>
<p><?php esc_html_e( 'We have tutorials on how to...', 'newsletter-optin-box' ); ?></p>
<ol>

	<li>
		<?php
			printf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( noptin_get_guide_url( 'Subscription Forms', '/subscription-forms/newsletter-subscription-shortcode/' ) ),
				esc_html__( 'Use the subscription form shortcode.', 'newsletter-optin-box' )
			);
		?>
	</li>

	<li>
		<?php
			printf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( noptin_get_guide_url( 'Subscription Forms', '/subscription-forms/newsletter-subscription-widget/' ) ),
				esc_html__( 'Display this form in a widget.', 'newsletter-optin-box' )
			);
		?>
	</li>

	<li>
		<?php
			printf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( noptin_get_guide_url( 'Subscription Forms', '/subscription-forms/newsletter-subscription-block/' ) ),
				esc_html__( 'Use the subscription form block.', 'newsletter-optin-box' )
			);
		?>
	</li>

	<li>
		<?php
			printf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( noptin_get_guide_url( 'Subscription Forms', '/subscription-forms/preventing-spam-sign-ups/' ) ),
				esc_html__( 'Prevent spam sign-ups.', 'newsletter-optin-box' )
			);
		?>
	</li>

	<li>
		<?php
			printf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( noptin_get_guide_url( 'Subscription Forms', '/subscription-forms/unsubscribe-forms/' ) ),
				esc_html__( 'Create unsubscribe forms', 'newsletter-optin-box' )
			);
		?>
	</li>

</ol>
