<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap noptin-new-automation-form" id="noptin-wrapper">
	<h1 class="title"><?php _e( 'Set-up a new automated email','newsletter-optin-box' ); ?></h1>
	<?php include plugin_dir_path( dirname( __FILE__ ) ) . 'tabs.php'; ?>

	<div class="noptin-automation-types">

		<?php foreach ( noptin()->emails->automated_email_types->types as $type ) : ?>

			<div class="card noptin-automation-type">
				<div class="noptin-automation-type-image"><?php $type->the_image(); ?></div>
				<div class="noptin-automation-type-content">
					<h3><?php echo esc_html( $type->get_name() ); ?></h3>
					<p><?php echo wp_kses_post( $type->get_description() ); ?></p>
					<div class="noptin-automation-type-action">
						<a href="<?php echo esc_url( $type->new_campaign_url() ); ?>" class="button button-primary"><?php _e( 'Set up', 'newsletter-optin-box' ); ?></a>
					</div>
				</div>
			</div>

		<?php endforeach; ?>

		<?php if ( ! defined( 'NOPTIN_WELCOME_EMAILS_FILE' ) ) : ?>
		
			<div class="card noptin-automation-type">
				<div class="noptin-automation-type-image">
					<svg xmlns="http://www.w3.org/2000/svg" fill="#77a464" viewBox="0 0 122.88 122.88"><path d="M61.44,0A61.46,61.46,0,1,1,18,18,61.21,61.21,0,0,1,61.44,0ZM32.22,79.39,52.1,59.46,32.22,43.25V79.39ZM54.29,61.24,33.79,81.79H88.91L69.33,61.24l-6.46,5.51h0a1.42,1.42,0,0,1-1.8,0l-6.78-5.53Zm17.18-1.82L90.66,79.55V43.07L71.47,59.42ZM34,41.09l27.9,22.76L88.65,41.09Zm65.4-17.64a53.72,53.72,0,1,0,15.74,38,53.56,53.56,0,0,0-15.74-38Z"/></svg>
				</div>
				<div class="noptin-automation-type-content">
					<h3><?php _e( 'Welcome Email', 'newsletter-optin-box' ); ?></h3>
					<p><?php _e( 'Introduce yourself to new subscribers or set up a series of welcome emails to act as an email course.', 'newsletter-optin-box' ); ?></p>
					<p style="color: #a00;"><em><?php _e( 'Not available in your plan', 'newsletter-optin-box' ); ?></em></p>
					<div class="noptin-automation-type-action">
						<a href="https://noptin.com/product/ultimate-addons-pack?utm_medium=plugin-dashboard&utm_campaign=automated-emails&utm_source=welcome_email" class="button" target="_blank"><?php _e( 'Upgrade', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt" style="vertical-align: middle;"></i></a>
					</div>
				</div>
			</div>

			<div class="card noptin-automation-type">
				<div class="noptin-automation-type-image">
					<svg xmlns="http://www.w3.org/2000/svg" fill="#77a464" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="122.879px" height="122.891px" viewBox="0 0 122.879 122.891" enable-background="new 0 0 122.879 122.891" xml:space="preserve"><g><path d="M89.767,18.578c3.848,0,7.332,1.561,9.854,4.082c2.521,2.522,4.082,6.007,4.082,9.855s-1.561,7.332-4.082,9.854 c-2.522,2.522-6.007,4.082-9.854,4.082c-3.849,0-7.333-1.56-9.854-4.082c-2.522-2.522-4.082-6.006-4.082-9.854 s1.56-7.333,4.082-9.855C82.434,20.138,85.918,18.578,89.767,18.578L89.767,18.578z M122.04,56.704l-65.337,65.337 c-1.132,1.133-2.969,1.133-4.101,0L0.849,70.287c-1.132-1.131-1.132-2.967,0-4.1L66.186,0.85C66.752,0.284,67.494,0,68.236,0v0 h50.051c1.602,0,2.9,1.298,2.9,2.9c0,0.048-0.002,0.097-0.004,0.145l1.694,51.517c0.026,0.83-0.301,1.589-0.845,2.134 L122.04,56.704L122.04,56.704z M54.652,115.889l62.406-62.407L115.49,5.8H69.438L7.001,68.238L54.652,115.889L54.652,115.889z M96.244,26.037c-1.657-1.657-3.948-2.683-6.478-2.683c-2.53,0-4.82,1.025-6.478,2.683c-1.658,1.657-2.684,3.948-2.684,6.478 s1.025,4.82,2.684,6.478c1.657,1.658,3.947,2.683,6.478,2.683c2.529,0,4.82-1.025,6.478-2.683s2.683-3.948,2.683-6.478 S97.901,27.694,96.244,26.037L96.244,26.037z"/></g></svg>
				</div>
				<div class="noptin-automation-type-content">
					<h3><?php _e( 'Subscriber Tag', 'newsletter-optin-box' ); ?></h3>
					<p><?php _e( 'Send an email to a subscriber when you tag or untag them.', 'newsletter-optin-box' ); ?></p>
					<p style="color: #a00;"><em><?php _e( 'Not available in your plan', 'newsletter-optin-box' ); ?></em></p>
					<div class="noptin-automation-type-action">
						<a href="https://noptin.com/product/ultimate-addons-pack?utm_medium=plugin-dashboard&utm_campaign=automated-emails&utm_source=subscriber_tag" class="button" target="_blank"><?php _e( 'Upgrade', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt" style="vertical-align: middle;"></i></a>
					</div>
				</div>
			</div>

			<div class="card noptin-automation-type">
				<div class="noptin-automation-type-image">
					<svg xmlns="http://www.w3.org/2000/svg" fill="#77a464" viewBox="0 0 122.88 107.3"><path d="M65.58,90.82l10.49,9.91L65.58,90.82Zm-2.52,5.76a3.06,3.06,0,0,1,0,6.12H7.12a7.09,7.09,0,0,1-5-2.1h0a7.1,7.1,0,0,1-2.09-5V7.12a7.06,7.06,0,0,1,2.1-5h0A7.1,7.1,0,0,1,7.12,0H91.63a7.1,7.1,0,0,1,5,2.09l.21.23a7.16,7.16,0,0,1,1.88,4.8V49a3.06,3.06,0,0,1-6.12,0V7.12a1,1,0,0,0-.22-.62l-.08-.08a1,1,0,0,0-.7-.3H7.12a1,1,0,0,0-.7.3h0a1,1,0,0,0-.29.7V95.58a1,1,0,0,0,.3.7h0a1,1,0,0,0,.7.29ZM95.44,67.42c3.54-3.7,6-6.89,11.5-7.52,10.24-1.17,19.65,9.32,14.47,19.64-1.47,2.94-4.47,6.44-7.78,9.87C110,93.18,106,96.87,103.14,99.67l-7.69,7.63-6.36-6.12C81.44,93.82,69,84.54,68.55,73.06c-.28-8,6.07-13.2,13.37-13.11,6.53.09,9.29,3.33,13.52,7.47Zm-71.66,0A3.62,3.62,0,1,1,20.16,71a3.62,3.62,0,0,1,3.62-3.62Zm14.71,7.27a3.15,3.15,0,0,1,0-6.19h11.8a3.15,3.15,0,0,1,0,6.19ZM23.78,46a3.62,3.62,0,1,1-3.62,3.61A3.62,3.62,0,0,1,23.78,46Zm14.71,6.94a3.1,3.1,0,0,1,0-6.11H62.58a3.1,3.1,0,0,1,0,6.11ZM23.78,24.6a3.62,3.62,0,1,1-3.62,3.62,3.62,3.62,0,0,1,3.62-3.62Zm14.71,6.65a2.84,2.84,0,0,1-2.57-3.05,2.85,2.85,0,0,1,2.57-3.06H72.38A2.85,2.85,0,0,1,75,28.2a2.84,2.84,0,0,1-2.57,3.05Z"/></svg>
				</div>
				<div class="noptin-automation-type-content">
					<h3><?php _e( 'Subscriber List', 'newsletter-optin-box' ); ?></h3>
					<p><?php _e( 'Send an email to a subscriber when they join or leave a list..', 'newsletter-optin-box' ); ?></p>
					<p style="color: #a00;"><em><?php _e( 'Not available in your plan', 'newsletter-optin-box' ); ?></em></p>
					<div class="noptin-automation-type-action">
						<a href="https://noptin.com/product/ultimate-addons-pack?utm_medium=plugin-dashboard&utm_campaign=automated-emails&utm_source=subscriber_list" class="button" target="_blank"><?php _e( 'Upgrade', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt" style="vertical-align: middle;"></i></a>
					</div>
				</div>
			</div>

		<?php endif; ?>

	</div>

</div>
