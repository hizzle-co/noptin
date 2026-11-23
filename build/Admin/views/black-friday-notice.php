<?php
/**
 * Black Friday promotion with a per-user dismissal link.
 *
 * @var string $dismiss_url Nonce-protected dismissal URL.
 */

defined( 'ABSPATH' ) || exit;
?>
<style>
	/* The promotion appears across admin screens, independently of their bundles. */
	.notice.noptin-black-friday-notice {
		position: relative;
		display: flex;
		align-items: flex-start;
		gap: 15px;
		padding: 15px 42px 15px 15px;
		border: 0;
		border-inline-start: 3px solid #FB1A62;
		background: #fff;
		box-shadow: none;
		color: #101517;
		margin: 15px 0;
    	box-sizing: border-box;
	}
	.noptin-black-friday-notice__logo {
		flex: 0 0 48px;
		width: 48px;
		height: 48px;
		object-fit: contain;
	}
	.noptin-black-friday-notice__logo img {
		max-width: 100%;
		height: auto;
	}
	.noptin-black-friday-notice__content { min-width: 0; }
	.noptin-black-friday-notice h2 {
		margin: 3px 0 13px;
		color: inherit;
		font-size: 17px;
		line-height: 1.4;
		font-weight: 600;
	}
	.noptin-black-friday-notice p {
		margin: 0 0 9px;
		padding: 0;
		font-size: 14px;
		line-height: 1.4;
	}
	.noptin-black-friday-notice__actions {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		gap: 10px 16px;
	}
	.noptin-black-friday-notice .button-primary {
		padding: 9px 14px;
		border: 1px solid #FB1A62;
		border-radius: 5px;
		background: #FB1A62;
		color: #fff;
		font-size: 14px;
		font-weight: 600;
		line-height: 1.4;
		text-decoration: none;
	}
	.noptin-black-friday-notice .button-primary:hover,
	.noptin-black-friday-notice .button-primary:focus {
		border-color: #d91050;
		background: #d91050;
		color: #fff;
	}
	.noptin-black-friday-notice .button-primary:focus {
		box-shadow: 0 0 0 2px #fff, 0 0 0 4px #FB1A62;
	}
	.noptin-black-friday-notice__terms {
		color: #50575e;
		font-size: 13px;
		line-height: 1.5;
	}
	.noptin-black-friday-notice .notice-dismiss { text-decoration: none; }
	@media screen and (max-width: 600px) {
		.notice.noptin-black-friday-notice { gap: 12px; padding-inline-start: 12px; }
		.noptin-black-friday-notice__logo { flex-basis: 40px; width: 40px; height: 40px; border-radius: 14px; }
		.noptin-black-friday-notice h2 { margin-top: 0; font-size: 18px; }
		.noptin-black-friday-notice p { font-size: 16px; }
	}
</style>
<div class="notice noptin-notice noptin-black-friday-notice">
	<div class="noptin-black-friday-notice__logo">
		<img src="<?php echo esc_url( noptin()->white_label->get( 'logo', noptin()->plugin_url . 'includes/assets/images/logo.png' ) ); ?>" alt="Noptin Logo" />
	</div>
	<div class="noptin-black-friday-notice__content">
		<h2>Black Friday: Get 60% Off Noptin Premium</h2>
		<p>Unlock 50+ email templates, 100+ premium integrations, powerful email automations, and AI email tools. Unlimited subscribers included.</p>
		<div class="noptin-black-friday-notice__actions">
			<a href="<?php echo esc_url( noptin_get_upsell_url( 'black-friday-sale/', 'black-friday', 'black-friday-notice' ) ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">Get 60% Off</a>
			<span class="noptin-black-friday-notice__terms">
				<?php
				printf(
					'Ends in %s · 30-day money-back guarantee',
					esc_html( human_time_diff( time(), noptin_get_black_friday_sale_end()->getTimestamp() ) )
				);
				?>
			</span>
		</div>
	</div>
	<a href="<?php echo esc_url( $dismiss_url ); ?>" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'newsletter-optin-box' ); ?></span></a>
</div>
