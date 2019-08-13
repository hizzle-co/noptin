<div class="wrap noptin-welcome">
	<div class="noptin-main-header">
		<h1>Noptin v1.0.5</h1>
		<a href="https://github.com/hizzle-co/noptin/issues/new/choose">Report a bug or request a feature</a>
	</div>

	<div class="noptin-header">
		<h2>Subscribers</h2>
		<hr/>
		<span title="These are subscribers you gained using Noptin" class="noptin-tip dashicons dashicons-info"></span>
	</div>

	<div class="noptin-cards-container">
		<ul class="noptin-cards-list">
				<li class="noptin-card">
					<span class="noptin-card-label">Total</span>
					<span class="noptin-card-value"><?php echo $subscribers_total; ?></span>
				</li>
				<li class="noptin-card">
					<span class="noptin-card-label">Today</span>
					<span class="noptin-card-value"><?php echo $subscribers_today_total; ?></span>
				</li>
				<li class="noptin-card">
					<span class="noptin-card-label">Growth</span>
					<span class="noptin-card-value"><?php echo $subscribers_growth_rate; ?> <span class="noptin-small">per day</span></span>
				</li>
		</ul>
		<div class="noptin-card-footer-links"><a href="<?php echo $subscribers_url; ?>">View all subscribers</a></div>
	</div>


	<div class="noptin-header">
		<h2>Active Opt-in Forms</h2>
		<hr/>
		<span title="These are the forms that are showing on the front-end" class="noptin-tip dashicons dashicons-info"></span>
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
		<h3>New Form Editor</h3>
		<p>You can now design a beautiful opt-in form and embed it on any widget area, post, or display it in a popup. <a href="https://noptin.com/guide/opt-in-forms-editor/">Learn More</a>.</p>
		<hr/>
		<p>Hundreds of hours went into this update. If you love it, Consider <a href="https://wordpress.org/support/plugin/newsletter-optin-box/reviews/?filter=5">giving us a 5* rating on WordPress.org</a>. It takes less than 5 minutes.</p>
	</div>


</div>
