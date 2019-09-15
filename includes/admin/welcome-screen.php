<div class="wrap about-wrap">
	<h1><?php esc_html_e( 'Welcome To Noptin',  'newsletter-optin-box' ); ?></h1>
	<div class="about-text"><?php esc_html_e( 'Noptin is very fast, lightweight and makes it easy to collect email addresses from your website visitors.',  'newsletter-optin-box' ); ?></div>

	<div class="changelog nmi-api-section" style="max-width: 560px;">
		<p><?php printf(
				esc_html__( 'Use the %s block to add a newsletter subscription form to your post content.',  'newsletter-optin-box' ),
				sprintf( '<a target="_blank" href="https://noptin.com/guide/newsletter-subscription-block/?utm_medium=plugin-dashboard&utm_campaign=welcome&utm_source=%s">Newsletter Optin</a>', get_home_url() )
			); ?>
		</p>
		<p><?php printf(
			esc_html__( 'Use the %s widget to add a newsletter subscription form to your widget areas.',  'newsletter-optin-box' ),
			sprintf( '<a target="_blank" href="https://noptin.com/guide/displaying-opt-in-forms-in-a-widget/?utm_medium=plugin-dashboard&utm_campaign=welcome&utm_source=%s"> Noptin New Form</a>', get_home_url() )
			); ?>
		</p>
	</div>

	<div class="changelog nmi-list-section" style="max-width: 560px;">
		<h3 style="text-align: left;color: #1B5E20;"><?php esc_html_e( 'Using the Opt-in Form Editor',  'newsletter-optin-box' ); ?></h3>
		<p><?php printf(
				esc_html__( 'Noptin also comes with an %s that you can use to build a more advanced email subscription form and embed it in a post, widget or popup lightbox.',  'newsletter-optin-box' ),
				sprintf( '<a target="_blank" href="https://noptin.com/guide/opt-in-forms-editor/?utm_medium=plugin-dashboard&utm_campaign=welcome&utm_source=%s">opt-in form editor</a>', get_home_url() )
			); ?>
		</p>

		<p><?php esc_html_e( 'To use the editor:-',  'newsletter-optin-box' ) ?></p>
		<li><?php esc_html_e( 'First, click on Noptin on the left navigation menu',  'newsletter-optin-box' ) ?></li>
		<li><?php esc_html_e( 'Finally, click on the Add New button to create a form and open it in the editor',  'newsletter-optin-box' ) ?></li>
	</div>
</div>
