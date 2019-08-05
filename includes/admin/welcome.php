<div class="wrap noptin-welcome">
	<div class="noptin-main-header">
		<h1>Noptin v1.0.5</h1>
		<a href="https://noptin.com/docs/changelog/#1.0.5">See What's New?</a>
	</div>

	<div class="noptin-header">
		<h2>Subscribers</h2>
		<hr/>
		<a href="https://noptin.com/docs/subscribers"><span class="dashicons dashicons-info"></span></a>
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
		<a href="https://noptin.com/docs/forms"><span class="dashicons dashicons-info"></span></a>
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
</div>
