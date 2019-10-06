<div class="noptin-welcome">
	<div class="noptin-main-header">
		<h1>Noptin v1.1.1</h1>
		<a href="https://github.com/hizzle-co/noptin/issues/new/choose" target="_blank"><?php _e( 'Report a bug or request a feature',  'newsletter-optin-box' ); ?></a>
	</div>

	<div class="noptin-header">
		<h2><?php _e( 'Subscribers',  'newsletter-optin-box' ); ?></h2>
		<hr/>
		<span title="<?php esc_attr_e( 'Your email subscribers',  'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
	</div>

	<div class="noptin-cards-container">
		<ul class="noptin-cards-list">
				<li class="noptin-card">
					<span class="noptin-card-label"><?php _e( 'Total',  'newsletter-optin-box' ); ?></span>
					<span class="noptin-card-value"><?php echo $subscribers_total; ?></span>
				</li>
				<li class="noptin-card">
					<span class="noptin-card-label"><?php _e( 'Today',  'newsletter-optin-box' ); ?></span>
					<span class="noptin-card-value"><?php echo $subscribers_today_total; ?></span>
				</li>
				<li class="noptin-card">
					<span class="noptin-card-label"><?php _e( 'Growth',  'newsletter-optin-box' ); ?></span>
					<span class="noptin-card-value"><?php echo $subscribers_growth_rate; ?> <span class="noptin-small"><?php _e( 'per day',  'newsletter-optin-box' ); ?></span></span>
				</li>
		</ul>
		<div class="noptin-card-footer-links"><a href="<?php echo $subscribers_url; ?>"><?php _e( 'View all subscribers',  'newsletter-optin-box' ); ?></a> | <a href="https://noptin.com/products/" target="_blank"><?php _e( 'Connect Your Email Service Provider',  'newsletter-optin-box' ); ?></a></div>
	</div>


	<div class="noptin-header">
		<h2><?php _e( 'Active Opt-in Forms',  'newsletter-optin-box' ); ?></h2>
		<hr/>
		<span title="<?php esc_attr_e( 'Forms created via the Opt-In Forms Editor',  'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
	</div>

	<div class="noptin-cards-container">
		<ul class="noptin-cards-list">
				<li class="noptin-card">
					<span class="noptin-card-label">Popups</span>
					<span class="noptin-card-value"><?php echo $popups; ?></span>
				</li>
				<li class="noptin-card">
					<span class="noptin-card-label">In-post</span>
					<span class="noptin-card-value"><?php echo $inpost; ?></span>
				</li>
				<li class="noptin-card">
					<span class="noptin-card-label">Widgets</span>
					<span class="noptin-card-value"><?php echo $widget; ?></span>
				</li>
		</ul>
		<div class="noptin-card-footer-links"><a href="<?php echo $forms_url; ?>">View all forms</a> | <a href="<?php echo $new_form_url; ?>">Create a new form</a></div>
	</div>

	<div class="noptin-header">
		<h2>What's New</h2>
		<hr/>
	</div>

	<div class="noptin-body">
		<h3>Inline Editing</h3>
		<p>Click on the form title, description or note to edit it.</p>
		<h3>Spam Submissions</h3>
		<p>We have added several improvements to prevent spam submissions.</p>
		<h3>MailPoet Integration</h3>
		<p>Email your subscribers or add them to email sequences using our <a href="https://noptin.com/product/mailpoet/" target="_blank">MailPoet addon</a>.</p>
		<hr/>
		<p>Thousands of hours have gone into this plugin. If you love it, Consider <a href="https://wordpress.org/support/plugin/newsletter-optin-box/reviews/?filter=5" target="_blank">giving us a 5* rating on WordPress.org</a>. It takes less than 5 minutes.</p>
	</div>


</div>
