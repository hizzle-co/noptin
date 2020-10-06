<?php

	$integrations = array();

	// Registration form.
	$integrations[] = array(

		'link' => sprintf(
			'https://noptin.com/guide/getting-email-subscribers/wordpress-registration-forms/?utm_source=%s&utm_medium=plugin-dashboard&utm_campaign=welcome>',
			urlencode( esc_url( get_home_url() ) )
		),

		'text' => sprintf(
			/* Translators: %1$s Opening strong tag, %2$s Closing strong tag. */
			esc_html__( 'Adding a newsletter subscription checkbox to your %1$sWordPress user registration forms%2$s.', 'newsletter-optin-box' ),
			'<strong>',
			'</strong>'
		)
	);

	// Comment form.
	$integrations[] = array(

		'link' => sprintf(
			'https://noptin.com/guide/getting-email-subscribers/wordpress-comment-forms/?utm_source=%s&utm_medium=plugin-dashboard&utm_campaign=welcome>',
			urlencode( esc_url( get_home_url() ) )
		),

		'text' => sprintf(
			/* Translators: %1$s Opening strong tag, %2$s Closing strong tag. */
			esc_html__( 'Adding a newsletter subscription checkbox to your %1$sWordPress comment forms%2$s.', 'newsletter-optin-box' ),
			'<strong>',
			'</strong>'
		)
	);

	// WooCommerce.
	$integrations[] = array(

		'link' => sprintf(
			'https://noptin.com/guide/getting-email-subscribers/woocommerce-checkout/?utm_source=%s&utm_medium=plugin-dashboard&utm_campaign=welcome>',
			urlencode( esc_url( get_home_url() ) )
		),

		'text' => sprintf(
			/* Translators: %1$s Opening strong tag, %2$s Closing strong tag. */
			esc_html__( 'Collecting newsletter subscribers on your %1$sWooCommerce checkout pages%2$s.', 'newsletter-optin-box' ),
			'<strong>',
			'</strong>'
		)
	);

	// EDD.
	$integrations[] = array(

		'link' => sprintf(
			'https://noptin.com/guide/getting-email-subscribers/edd-checkout/?utm_source=%s&utm_medium=plugin-dashboard&utm_campaign=welcome>',
			urlencode( esc_url( get_home_url() ) )
		),

		'text' => sprintf(
			/* Translators: %1$s Opening strong tag, %2$s Closing strong tag. */
			esc_html__( 'Collecting newsletter subscribers on your %1$sEDD checkout pages%2$s.', 'newsletter-optin-box' ),
			'<strong>',
			'</strong>'
		)
	);

	// Ninja Forms.
	$integrations[] = array(

		'link' => sprintf(
			'https://noptin.com/guide/getting-email-subscribers/ninja-forms/?utm_source=%s&utm_medium=plugin-dashboard&utm_campaign=welcome>',
			urlencode( esc_url( get_home_url() ) )
		),

		'text' => sprintf(
			/* Translators: %1$s Opening strong tag, %2$s Closing strong tag. */
			esc_html__( 'Using %1$sNinja Forms%2$s to create a newsletter subscription form.', 'newsletter-optin-box' ),
			'<strong>',
			'</strong>'
		)
	);

	// Gravity Forms.
	$integrations[] = array(

		'link' => sprintf(
			'https://noptin.com/guide/getting-email-subscribers/gravity-forms/?utm_source=%s&utm_medium=plugin-dashboard&utm_campaign=welcome>',
			urlencode( esc_url( get_home_url() ) )
		),

		'text' => sprintf(
			/* Translators: %1$s Opening strong tag, %2$s Closing strong tag. */
			esc_html__( 'Using %1$sGravity Forms%2$s to create a newsletter subscription form.', 'newsletter-optin-box' ),
			'<strong>',
			'</strong>'
		)
	);

	// Elementor.
	$integrations[] = array(

		'link' => sprintf(
			'https://noptin.com/guide/getting-email-subscribers/elementor-forms/?utm_source=%s&utm_medium=plugin-dashboard&utm_campaign=welcome>',
			urlencode( esc_url( get_home_url() ) )
		),

		'text' => sprintf(
			/* Translators: %1$s Opening strong tag, %2$s Closing strong tag. */
			esc_html__( 'Using %1$sElementor%2$s to create a newsletter subscription form.', 'newsletter-optin-box' ),
			'<strong>',
			'</strong>'
		)
	);

	// WPForms.
	$integrations[] = array(

		'link' => sprintf(
			'https://noptin.com/guide/getting-email-subscribers/wpforms/?utm_source=%s&utm_medium=plugin-dashboard&utm_campaign=welcome>',
			urlencode( esc_url( get_home_url() ) )
		),

		'text' => sprintf(
			/* Translators: %1$s Opening strong tag, %2$s Closing strong tag. */
			esc_html__( 'Using %1$sWPForms%2$s to create a newsletter subscription form.', 'newsletter-optin-box' ),
			'<strong>',
			'</strong>'
		)
	);

	// Contact Form 7.
	$integrations[] = array(

		'link' => sprintf(
			'https://noptin.com/guide/getting-email-subscribers/contact-form-7/?utm_source=%s&utm_medium=plugin-dashboard&utm_campaign=welcome>',
			urlencode( esc_url( get_home_url() ) )
		),

		'text' => sprintf(
			/* Translators: %1$s Opening strong tag, %2$s Closing strong tag. */
			esc_html__( 'Using %1$sContact Form 7%2$s to create a newsletter subscription form.', 'newsletter-optin-box' ),
			'<strong>',
			'</strong>'
		)
	);
?>
<div class="wrap about-wrap">
	<h1><?php esc_html_e( 'Welcome To Noptin', 'newsletter-optin-box' ); ?></h1>
	<div class="about-text"><?php esc_html_e( 'Noptin is a fast and lightweight newsletter plugin.', 'newsletter-optin-box' ); ?></div>

	<div class="changelog nmi-api-section" style="max-width: 560px;">
		<h3 style="text-align: left;color: #1B5E20;"><?php esc_html_e( 'Getting Started', 'newsletter-optin-box' ); ?></h3>

		<p>
			<?php
				printf(
					/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
					esc_html__( 'If you\'re migrating from another newsletter service or plugin, start by %1$simporting your existing newsletter subscribers into Noptin%2$s', 'newsletter-optin-box' ),
					sprintf(
						'<a target="_blank" style="text-decoration: none;" href="https://noptin.com/guide/getting-email-subscribers/importing-subscribers/?utm_source=%s&utm_medium=plugin-dashboard&utm_campaign=welcome">',
						urlencode( esc_url( get_home_url() ) )
					),
					'</a>'
				);
			?>
		</p>

		<p style="margin-top: 20px; margin-bottom: 20px;">
			<?php esc_html_e( 'Next, start collecting new email subscribers by:-', 'newsletter-optin-box' ); ?>
		</p>

		<ul style="margin-left: 20px;">

			<?php foreach( $integrations as $integration ) : ?>
				<li style="margin-bottom: 16px; list-style: square;">
					<a target="_blank" style="text-decoration: none;" href="<?php echo esc_url( $integration['link'] ); ?>"><?php echo $integration['text'] ?></a>
				</li>
			<?php endforeach; ?>

		</ul>

		<p>
			<?php
				printf(
					/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
					esc_html__( 'In addition to the integrations above, Noptin also comes with an %1$sadvanced newsletter subscription form builder%2$s that you can use to build your newsletter subscription forms.', 'newsletter-optin-box' ),
					sprintf(
						'<a target="_blank" style="text-decoration: none;" href="https://noptin.com/guide/getting-email-subscribers/noptin-forms/?utm_medium=plugin-dashboard&utm_campaign=welcome&utm_source=%s">',
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
