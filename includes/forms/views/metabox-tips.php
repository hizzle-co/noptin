<?php defined( 'ABSPATH' ) || exit; ?>
<p><?php esc_html_e( 'We have tutorials on how to...', 'newsletter-optin-box' ); ?></p>
<ol>

	<li>
		<?php
			printf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( noptin_get_upsell_url( '/guide/subscription-forms/newsletter-subscription-shortcode/', 'shortcode', 'subscription-forms' ) ),
				esc_html__( 'Use the subscription form shortcode.', 'newsletter-optin-box' )
			);
		?>
	</li>

	<li>
		<?php
			printf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( noptin_get_upsell_url( '/guide/subscription-forms/newsletter-subscription-widget/', 'widget', 'subscription-forms' ) ),
				esc_html__( 'Display this form in a widget.', 'newsletter-optin-box' )
			);
		?>
	</li>

	<li>
		<?php
			printf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( noptin_get_upsell_url( '/guide/subscription-forms/newsletter-subscription-widget/', 'block', 'subscription-forms' ) ),
				esc_html__( 'Use the subscription form block.', 'newsletter-optin-box' )
			);
		?>
	</li>

	<li>
		<?php
			printf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( noptin_get_upsell_url( '/guide/subscription-forms/preventing-spam-sign-ups/', 'spam', 'subscription-forms' ) ),
				esc_html__( 'Prevent spam sign-ups.', 'newsletter-optin-box' )
			);
		?>
	</li>

	<li>
		<?php
			printf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( noptin_get_upsell_url( '/guide/subscription-forms/preventing-spam-sign-ups/', 'unsubscribe-forms', 'subscription-forms' ) ),
				esc_html__( 'Create unsubscribe forms', 'newsletter-optin-box' )
			);
		?>
	</li>

</ol>
