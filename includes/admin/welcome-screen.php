<div class="wrap about-wrap">
	<h1><?php esc_html_e( 'Welcome To Noptin', 'newsletter-optin-box' ); ?></h1>
	<div class="about-text"><?php esc_html_e( 'Noptin is fast, lightweight and makes it easy to collect email addresses from your website visitors.', 'newsletter-optin-box' ); ?></div>

	<div class="changelog nmi-api-section" style="max-width: 560px;">
		<h3 style="text-align: left;color: #1B5E20;"><?php esc_html_e( 'Getting Started', 'newsletter-optin-box' ); ?></h3>
		<p>
		<?php
		printf(
			/* Translators: %s The name of the block. */
			esc_html__( 'Use the %s block to add a newsletter subscription form to your post content.', 'newsletter-optin-box' ),
			sprintf( '<a target="_blank" href="https://noptin.com/guide/email-forms/newsletter-subscription-block/?utm_medium=plugin-dashboard&utm_campaign=welcome&utm_source=%s">Newsletter Optin</a>', esc_url( get_home_url() ) )
		);
		?>
		</p>
		<p>
		<?php
		printf(
			/* Translators: %s The name of the widget. */
			esc_html__( 'Use the %s widget to add a newsletter subscription form to your widget areas.', 'newsletter-optin-box' ),
			sprintf( '<a target="_blank" href="https://noptin.com/guide/email-forms/displaying-opt-in-forms-in-a-widget/?utm_medium=plugin-dashboard&utm_campaign=welcome&utm_source=%s"> Noptin New Form</a>', esc_url( get_home_url() ) )
		);
		?>
		</p>
	</div>

	<div class="changelog nmi-list-section" style="max-width: 560px;">
		<h3 style="text-align: left;color: #1B5E20;"><?php esc_html_e( 'Using the Opt-in Form Editor', 'newsletter-optin-box' ); ?></h3>
		<p>
		<?php
			printf(
				/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
				esc_html__( 'Noptin also comes with an %1$sopt-in forms editor%2$s that you can use to build a more advanced email subscription form and embed it in a post, widget or popup lightbox.', 'newsletter-optin-box' ),
				sprintf(
					'<a target="_blank" href="https://noptin.com/guide/email-forms/opt-in-forms-editor/?utm_medium=plugin-dashboard&utm_campaign=welcome&utm_source=%s">',
					urlencode( esc_url( get_home_url() ) )
				),
				'</a>'
			);
		?>
		</p>
	</div>

	<div class="changelog nmi-api-section" style="max-width: 560px;">
		<h3 style="text-align: left;color: #1B5E20;"><?php esc_html_e( 'Email Campaigns', 'newsletter-optin-box' ); ?></h3>
		<p>
		<?php
		printf(
			/* Translators: %1$s and %3$s Opening link tag, %2$s and %4$s Closing link tag. */
			esc_html__( 'You can send your email subscribers %1$sone time emails%2$s or %3$sautomated emails%4$s.', 'newsletter-optin-box' ),
			sprintf( '<a target="_blank" href="https://noptin.com/guide/sending-emails/?utm_medium=plugin-dashboard&utm_campaign=welcome&utm_source=%s">', esc_url( get_home_url() ) ),
			'</a>',
			sprintf( '<a target="_blank" href="https://noptin.com/guide/email-automations/?utm_medium=plugin-dashboard&utm_campaign=welcome&utm_source=%s">', esc_url( get_home_url() ) ),
			'</a>'
		);
		?>
		</p>

	</div>
</div>
