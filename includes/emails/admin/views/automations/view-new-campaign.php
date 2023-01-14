<?php
	defined( 'ABSPATH' ) || exit;
	$woocommerce_automations = array();
?>
<div class="noptin-email-types">

		<?php
			foreach ( noptin()->emails->automated_email_types->types as $automated_email_type ) :

				if ( 0 === strpos( $automated_email_type->type, 'automation_rule_' ) ) {
					continue;
				}

				if ( 0 === strpos( $automated_email_type->type, 'woocommerce' ) ) {
					$woocommerce_automations[] = $automated_email_type;
					continue;
				}

		?>

			<div class="card noptin-email-type">
				<div class="noptin-email-type-image"><?php $automated_email_type->the_image(); ?></div>
				<div class="noptin-email-type-content">
					<h3><?php echo esc_html( $automated_email_type->get_name() ); ?></h3>
					<p><?php echo wp_kses_post( $automated_email_type->get_description() ); ?></p>
					<div class="noptin-email-type-action">
						<a href="<?php echo esc_url( $automated_email_type->new_campaign_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Set up', 'newsletter-optin-box' ); ?></a>
					</div>
				</div>
			</div>

		<?php endforeach; ?>

		<?php if ( ! defined( 'NOPTIN_WELCOME_EMAILS_FILE' ) ) : ?>

			<div class="card noptin-email-type">
				<div class="noptin-email-type-image">
					<svg xmlns="http://www.w3.org/2000/svg" fill="#008000" viewBox="0 0 122.88 122.88"><path d="M61.44,0A61.46,61.46,0,1,1,18,18,61.21,61.21,0,0,1,61.44,0ZM32.22,79.39,52.1,59.46,32.22,43.25V79.39ZM54.29,61.24,33.79,81.79H88.91L69.33,61.24l-6.46,5.51h0a1.42,1.42,0,0,1-1.8,0l-6.78-5.53Zm17.18-1.82L90.66,79.55V43.07L71.47,59.42ZM34,41.09l27.9,22.76L88.65,41.09Zm65.4-17.64a53.72,53.72,0,1,0,15.74,38,53.56,53.56,0,0,0-15.74-38Z"/></svg>
				</div>
				<div class="noptin-email-type-content">
					<h3><?php esc_html_e( 'Welcome New Subscribers', 'newsletter-optin-box' ); ?></h3>
					<p><?php esc_html_e( 'Introduce yourself to new subscribers or set up a series of welcome emails to act as an email course.', 'newsletter-optin-box' ); ?></p>
					<p style="color: #a00;"><em><?php esc_html_e( 'Not available in your plan', 'newsletter-optin-box' ); ?></em></p>
					<div class="noptin-email-type-action">
						<a href="https://noptin.com/ultimate-addons-pack/?utm_medium=plugin-dashboard&utm_campaign=automated-emails&utm_source=welcome_email" class="button" target="_blank"><?php esc_html_e( 'Upgrade', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt" style="vertical-align: middle;"></i></a>
					</div>
				</div>
			</div>

			<div class="card noptin-email-type">
				<div class="noptin-email-type-image">
					<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 256 255" version="1.1"><g fill="#464342"><path d="M18.1239675,127.500488 C18.1239675,170.795707 43.284813,208.211252 79.7700163,225.941854 L27.5938862,82.985626 C21.524813,96.5890081 18.1239675,111.643057 18.1239675,127.500488 L18.1239675,127.500488 Z M201.345041,121.980878 C201.345041,108.462829 196.489366,99.1011382 192.324683,91.8145041 C186.780098,82.8045528 181.583089,75.1745041 181.583089,66.1645528 C181.583089,56.1097886 189.208976,46.7501789 199.950569,46.7501789 C200.435512,46.7501789 200.89548,46.8105366 201.367935,46.8375935 C181.907772,29.0091707 155.981008,18.1239675 127.50465,18.1239675 C89.2919675,18.1239675 55.6727154,37.7298211 36.1147317,67.4258211 C38.6809756,67.5028293 41.0994472,67.5569431 43.1536911,67.5569431 C54.5946016,67.5569431 72.3043902,66.1687154 72.3043902,66.1687154 C78.2007154,65.8211382 78.8958699,74.4814309 73.0057886,75.1786667 C73.0057886,75.1786667 67.0803252,75.8759024 60.4867642,76.2213984 L100.318699,194.699447 L124.25574,122.909138 L107.214049,76.2172358 C101.323967,75.8717398 95.744,75.1745041 95.744,75.1745041 C89.8497561,74.8290081 90.540748,65.8169756 96.4349919,66.1645528 C96.4349919,66.1645528 114.498602,67.5527805 125.246439,67.5527805 C136.685268,67.5527805 154.397138,66.1645528 154.397138,66.1645528 C160.297626,65.8169756 160.990699,74.4772683 155.098537,75.1745041 C155.098537,75.1745041 149.160585,75.8717398 142.579512,76.2172358 L182.107577,193.798244 L193.017756,157.340098 C197.746472,142.211122 201.345041,131.34465 201.345041,121.980878 L201.345041,121.980878 Z M129.42361,137.068228 L96.6056585,232.43135 C106.404423,235.31187 116.76722,236.887415 127.50465,236.887415 C140.242211,236.887415 152.457366,234.685398 163.827512,230.68722 C163.534049,230.218927 163.267642,229.721496 163.049106,229.180358 L129.42361,137.068228 L129.42361,137.068228 Z M223.481756,75.0225691 C223.95213,78.5066667 224.218537,82.2467642 224.218537,86.2699187 C224.218537,97.3694959 222.145561,109.846894 215.901659,125.448325 L182.490537,222.04774 C215.00878,203.085008 236.881171,167.854829 236.881171,127.502569 C236.883252,108.485724 232.025496,90.603187 223.481756,75.0225691 L223.481756,75.0225691 Z M127.50465,0 C57.2003902,0 0,57.1962276 0,127.500488 C0,197.813073 57.2003902,255.00722 127.50465,255.00722 C197.806829,255.00722 255.015545,197.813073 255.015545,127.500488 C255.013463,57.1962276 197.806829,0 127.50465,0 L127.50465,0 Z M127.50465,249.162927 C60.4243252,249.162927 5.84637398,194.584976 5.84637398,127.500488 C5.84637398,60.4201626 60.4222439,5.84637398 127.50465,5.84637398 C194.582894,5.84637398 249.156683,60.4201626 249.156683,127.500488 C249.156683,194.584976 194.582894,249.162927 127.50465,249.162927 L127.50465,249.162927 Z"/></g></svg>
				</div>
				<div class="noptin-email-type-content">
					<h3><?php esc_html_e( 'Welcome New Users', 'newsletter-optin-box' ); ?></h3>
					<p><?php esc_html_e( 'Welcome new users to your website, introduce yourself, etc', 'newsletter-optin-box' ); ?></p>
					<p style="color: #a00;"><em><?php esc_html_e( 'Not available in your plan', 'newsletter-optin-box' ); ?></em></p>
					<div class="noptin-email-type-action">
						<a href="https://noptin.com/ultimate-addons-pack/?utm_medium=plugin-dashboard&utm_campaign=automated-emails&utm_source=welcome_users_email" class="button" target="_blank"><?php esc_html_e( 'Upgrade', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt" style="vertical-align: middle;"></i></a>
					</div>
				</div>
			</div>

			<div class="card noptin-email-type">
				<div class="noptin-email-type-image">
					<svg xmlns="http://www.w3.org/2000/svg" fill="#008000" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="122.879px" height="122.891px" viewBox="0 0 122.879 122.891" enable-background="new 0 0 122.879 122.891" xml:space="preserve"><g><path d="M89.767,18.578c3.848,0,7.332,1.561,9.854,4.082c2.521,2.522,4.082,6.007,4.082,9.855s-1.561,7.332-4.082,9.854 c-2.522,2.522-6.007,4.082-9.854,4.082c-3.849,0-7.333-1.56-9.854-4.082c-2.522-2.522-4.082-6.006-4.082-9.854 s1.56-7.333,4.082-9.855C82.434,20.138,85.918,18.578,89.767,18.578L89.767,18.578z M122.04,56.704l-65.337,65.337 c-1.132,1.133-2.969,1.133-4.101,0L0.849,70.287c-1.132-1.131-1.132-2.967,0-4.1L66.186,0.85C66.752,0.284,67.494,0,68.236,0v0 h50.051c1.602,0,2.9,1.298,2.9,2.9c0,0.048-0.002,0.097-0.004,0.145l1.694,51.517c0.026,0.83-0.301,1.589-0.845,2.134 L122.04,56.704L122.04,56.704z M54.652,115.889l62.406-62.407L115.49,5.8H69.438L7.001,68.238L54.652,115.889L54.652,115.889z M96.244,26.037c-1.657-1.657-3.948-2.683-6.478-2.683c-2.53,0-4.82,1.025-6.478,2.683c-1.658,1.657-2.684,3.948-2.684,6.478 s1.025,4.82,2.684,6.478c1.657,1.658,3.947,2.683,6.478,2.683c2.529,0,4.82-1.025,6.478-2.683s2.683-3.948,2.683-6.478 S97.901,27.694,96.244,26.037L96.244,26.037z"/></g></svg>
				</div>
				<div class="noptin-email-type-content">
					<h3><?php esc_html_e( 'Subscriber Tag', 'newsletter-optin-box' ); ?></h3>
					<p><?php esc_html_e( 'Send an email to a subscriber when they are tagged or untagged.', 'newsletter-optin-box' ); ?></p>
					<p style="color: #a00;"><em><?php esc_html_e( 'Not available in your plan', 'newsletter-optin-box' ); ?></em></p>
					<div class="noptin-email-type-action">
						<a href="https://noptin.com/ultimate-addons-pack/?utm_medium=plugin-dashboard&utm_campaign=automated-emails&utm_source=subscriber_tag" class="button" target="_blank"><?php esc_html_e( 'Upgrade', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt" style="vertical-align: middle;"></i></a>
					</div>
				</div>
			</div>

			<div class="card noptin-email-type">
				<div class="noptin-email-type-image">
					<svg xmlns="http://www.w3.org/2000/svg" fill="#008000" viewBox="0 0 122.88 107.3"><path d="M65.58,90.82l10.49,9.91L65.58,90.82Zm-2.52,5.76a3.06,3.06,0,0,1,0,6.12H7.12a7.09,7.09,0,0,1-5-2.1h0a7.1,7.1,0,0,1-2.09-5V7.12a7.06,7.06,0,0,1,2.1-5h0A7.1,7.1,0,0,1,7.12,0H91.63a7.1,7.1,0,0,1,5,2.09l.21.23a7.16,7.16,0,0,1,1.88,4.8V49a3.06,3.06,0,0,1-6.12,0V7.12a1,1,0,0,0-.22-.62l-.08-.08a1,1,0,0,0-.7-.3H7.12a1,1,0,0,0-.7.3h0a1,1,0,0,0-.29.7V95.58a1,1,0,0,0,.3.7h0a1,1,0,0,0,.7.29ZM95.44,67.42c3.54-3.7,6-6.89,11.5-7.52,10.24-1.17,19.65,9.32,14.47,19.64-1.47,2.94-4.47,6.44-7.78,9.87C110,93.18,106,96.87,103.14,99.67l-7.69,7.63-6.36-6.12C81.44,93.82,69,84.54,68.55,73.06c-.28-8,6.07-13.2,13.37-13.11,6.53.09,9.29,3.33,13.52,7.47Zm-71.66,0A3.62,3.62,0,1,1,20.16,71a3.62,3.62,0,0,1,3.62-3.62Zm14.71,7.27a3.15,3.15,0,0,1,0-6.19h11.8a3.15,3.15,0,0,1,0,6.19ZM23.78,46a3.62,3.62,0,1,1-3.62,3.61A3.62,3.62,0,0,1,23.78,46Zm14.71,6.94a3.1,3.1,0,0,1,0-6.11H62.58a3.1,3.1,0,0,1,0,6.11ZM23.78,24.6a3.62,3.62,0,1,1-3.62,3.62,3.62,3.62,0,0,1,3.62-3.62Zm14.71,6.65a2.84,2.84,0,0,1-2.57-3.05,2.85,2.85,0,0,1,2.57-3.06H72.38A2.85,2.85,0,0,1,75,28.2a2.84,2.84,0,0,1-2.57,3.05Z"/></svg>
				</div>
				<div class="noptin-email-type-content">
					<h3><?php esc_html_e( 'Subscriber List', 'newsletter-optin-box' ); ?></h3>
					<p><?php esc_html_e( 'Send an email to a subscriber when they join or leave a list.', 'newsletter-optin-box' ); ?></p>
					<p style="color: #a00;"><em><?php esc_html_e( 'Not available in your plan', 'newsletter-optin-box' ); ?></em></p>
					<div class="noptin-email-type-action">
						<a href="https://noptin.com/ultimate-addons-pack/?utm_medium=plugin-dashboard&utm_campaign=automated-emails&utm_source=subscriber_list" class="button" target="_blank"><?php esc_html_e( 'Upgrade', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt" style="vertical-align: middle;"></i></a>
					</div>
				</div>
			</div>

		<?php endif; ?>

	</div>

<?php if ( ! empty( $woocommerce_automations ) ) : ?>
	<h2 style="margin-bottom: 0;margin-top: 3rem;font-size: 29px;">WooCommerce</h2>
	<div class="noptin-email-types">
		<?php foreach ( $woocommerce_automations as $automated_email_type ) : ?>
			<div class="card noptin-email-type">
				<div class="noptin-email-type-image"><?php $automated_email_type->the_image(); ?></div>
				<div class="noptin-email-type-content">
					<h3><?php echo esc_html( $automated_email_type->get_name() ); ?></h3>
					<p><?php echo wp_kses_post( $automated_email_type->get_description() ); ?></p>
					<div class="noptin-email-type-action">
						<a href="<?php echo esc_url( $automated_email_type->new_campaign_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Set up', 'newsletter-optin-box' ); ?></a>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
