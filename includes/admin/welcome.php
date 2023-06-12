<?php

	defined( 'ABSPATH' ) || exit;

	$recommended_plugins = array(

		array(
			'slug' => 'hizzle-recaptcha',
			'name' => 'Hizzle reCAPTCHA',
			'desc' => __( 'Protects your subscription, contact, checkout, and registration forms from spammers.', 'newsletter-optin-box' ),
			'img'  => 'https://ps.w.org/hizzle-recaptcha/assets/icon-256x256.png',
			'url'  => admin_url( 'plugin-install.php?tab=plugin-information&plugin=hizzle-recaptcha&TB_iframe=true&width=772&height=600' ),
		),

		array(
			'slug' => 'email-customizer',
			'name' => 'Email Customizer',
			'desc' => __( 'Easily replace the plain text WordPress emails with beautiful HTML emails that match your brand colors.', 'newsletter-optin-box' ),
			'img'  => 'https://ps.w.org/email-customizer/assets/icon-256x256.png',
			'url'  => admin_url( 'plugin-install.php?tab=plugin-information&plugin=email-customizer&TB_iframe=true&width=772&height=600' ),
		),

		array(
			'slug' => 'hizzle-downloads',
			'name' => 'Hizzle Downloads',
			'desc' => __( 'Add downloadable files to your site and restrict access by user role or newsletter subscription status.', 'newsletter-optin-box' ),
			'img'  => 'https://s.w.org/plugins/geopattern-icon/hizzle-downloads.svg',
			'url'  => admin_url( 'plugin-install.php?tab=plugin-information&plugin=hizzle-downloads&TB_iframe=true&width=772&height=600' ),
		),
	);

	$subscribers_today_total = get_noptin_subscribers_count(
		array(
			'date_created_after'  => '-1 day',
			'date_created_before' => 'now',
		)
	);
	$subscribers_week_total  = get_noptin_subscribers_count(
		array(
			'date_created_after'  => '-7 days',
			'date_created_before' => 'now',
		)
	);

	add_thickbox();
?>

<div class="noptin-welcome">
	<div class="noptin-main-header">
		<h1>Noptin v<?php echo esc_html( noptin()->version ); ?></h1>
		<a href="https://github.com/hizzle-co/noptin/issues/new/choose" target="_blank"><?php esc_html_e( 'Report a bug or request a feature', 'newsletter-optin-box' ); ?></a>
	</div>

	<div class="noptin-body" style="margin-top: 20px;">
		<hr/>
		<p>
			<?php
				printf(
					/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
					esc_html__( 'Thousands of hours have gone into this plugin. If you love it, Consider %1$sgiving us a 5* rating on WordPress.org%2$s. It takes less than 5 minutes.', 'newsletter-optin-box' ),
					'<a href="https://wordpress.org/support/plugin/newsletter-optin-box/reviews/?filter=5" target="_blank">',
					'</a>'
				);
			?>
		</p>
	</div>

	<div class="noptin-header">
		<h2><?php esc_html_e( 'Newsletter Subscribers', 'newsletter-optin-box' ); ?></h2>
		<hr/>
		<span title="<?php esc_attr_e( 'Your email subscribers', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
	</div>

	<div class="noptin-cards-container">
		<ul class="noptin-cards-list">
				<li class="noptin-card">
					<span class="noptin-card-label"><?php esc_html_e( 'Total', 'newsletter-optin-box' ); ?></span>
					<span class="noptin-card-value"><?php echo (int) get_noptin_subscribers_count(); ?></span>
				</li>
				<li class="noptin-card">
					<span class="noptin-card-label"><?php esc_html_e( '1 Day', 'newsletter-optin-box' ); ?></span>
					<span class="noptin-card-value"><?php echo (int) $subscribers_today_total; ?></span>
				</li>
				<li class="noptin-card">
					<span class="noptin-card-label"><?php esc_html_e( '7 Days', 'newsletter-optin-box' ); ?></span>
					<span class="noptin-card-value"><?php echo (int) $subscribers_week_total; ?></span>
				</li>
		</ul>
		<div class="noptin-card-footer-links"><a href="<?php echo esc_url( admin_url( 'admin.php?page=noptin-subscribers' ) ); ?>"><?php esc_html_e( 'View all subscribers', 'newsletter-optin-box' ); ?></a> | <a href="<?php echo esc_url( get_noptin_new_newsletter_campaign_url() ); ?>"><?php esc_html_e( 'Send them an email', 'newsletter-optin-box' ); ?></a></div>
	</div>


	<div class="noptin-header">
		<h2><?php esc_html_e( 'Newsletter Subscription Forms', 'newsletter-optin-box' ); ?></h2>
		<hr/>
		<span title="<?php esc_attr_e( 'Active forms created via the Opt-In Forms Editor', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
	</div>

	<div class="noptin-cards-container">
		<ul class="noptin-cards-list">
				<?php if ( is_using_new_noptin_forms() ) : ?>
					<li class="noptin-card">
						<span class="noptin-card-label"><?php esc_html_e( 'All Forms', 'newsletter-optin-box' ); ?></span>
						<span class="noptin-card-value"><?php echo (int) $all_forms; ?></span>
					</li>
				<?php else : ?>
					<li class="noptin-card">
						<span class="noptin-card-label"><?php esc_html_e( 'Popup Forms', 'newsletter-optin-box' ); ?></span>
						<span class="noptin-card-value"><?php echo (int) $popups; ?></span>
					</li>
					<li class="noptin-card">
						<span class="noptin-card-label"><?php esc_html_e( 'Shortcode Forms', 'newsletter-optin-box' ); ?></span>
						<span class="noptin-card-value"><?php echo (int) $inpost; ?></span>
					</li>
					<li class="noptin-card">
						<span class="noptin-card-label"><?php esc_html_e( 'Widget Forms', 'newsletter-optin-box' ); ?></span>
						<span class="noptin-card-value"><?php echo (int) $widget; ?></span>
					</li>
					<li class="noptin-card">
						<span class="noptin-card-label"><?php esc_html_e( 'Sliding Forms', 'newsletter-optin-box' ); ?></span>
						<span class="noptin-card-value"><?php echo (int) $slide_in; ?></span>
					</li>
				<?php endif; ?>
		</ul>
		<div class="noptin-card-footer-links"><a href="<?php echo esc_url( get_noptin_forms_overview_url() ); ?>"><?php esc_html_e( 'View all forms', 'newsletter-optin-box' ); ?></a> | <a href="<?php echo esc_url( get_noptin_new_form_url() ); ?>"><?php esc_html_e( 'Create a new form', 'newsletter-optin-box' ); ?></a></div>
	</div>

	<div class="noptin-header">
		<h2><?php esc_html_e( 'Recommended Plugins', 'newsletter-optin-box' ); ?></h2>
		<hr/>
		<span title="<?php esc_attr_e( 'Here are some of the plugins we recommend', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
	</div>

	<div class="noptin-cards-container">
		<ul class="noptin-dashboard-recommended-plugins">

			<?php foreach ( $recommended_plugins as $recommended_plugin ) : ?>
				<li class="noptin-dashboard-recommended-plugin">
					<div class="noptin-card-content">
						<span class="noptin-card-image">
							<img src="<?php echo esc_url( $recommended_plugin['img'] ); ?>" alt="<?php echo esc_attr( $recommended_plugin['name'] ); ?>" />
						</span>
						<h3><?php echo esc_html( $recommended_plugin['name'] ); ?></h3>
						<p class="description"><?php echo esc_html( $recommended_plugin['desc'] ); ?></p>
					</div>
					<div class="noptin-card-footer">
						<a class="thickbox button" href="<?php echo esc_url( $recommended_plugin['url'] ); ?>"><?php esc_html_e( 'View Details', 'newsletter-optin-box' ); ?></a>
					</div>
				</li>
			<?php endforeach; ?>

		</ul>
	</div>

</div>

<style>

	.noptin-dashboard-recommended-plugins {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
	}

	.noptin-dashboard-recommended-plugin {
		background: #fff;
		border: 1px solid #dcdcde;
		border-radius: 2px;
		box-sizing: border-box;
		display: flex;
		flex: 1 0 auto;
		flex-direction: column;
		justify-content: space-between;
		margin: 12px 0;
		min-width: 280px;
		max-width: 100%;
		min-height: 220px;
		overflow: hidden;
		padding: 0;
		vertical-align: top;
	}

	@media screen and (min-width: 768px) {
		.noptin-dashboard-recommended-plugin {
			max-width:calc(50% - 12px);
			width: calc(50% - 12px)
		}
	}

	.noptin-dashboard-recommended-plugins .noptin-card-image {
		width: 100px;
		margin-right: 20px;
	}

	.noptin-dashboard-recommended-plugins .noptin-card-content {
		flex: 1;
		padding: 24px;
    	position: relative;
	}

	.noptin-dashboard-recommended-plugins .noptin-card-image {
		display: block;
		margin-left: 24px;
		position: absolute;
		right: 6px;
		top: 6px;
	}

	.noptin-dashboard-recommended-plugins .noptin-card-image img {
		width: 64px;
	}

	.noptin-dashboard-recommended-plugins .noptin-card-footer {
		margin-top: 20px;
		padding: 24px;
		border-top: 1px solid #dcdcde;
	}

</style>
