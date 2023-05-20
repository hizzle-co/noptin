/**
 * Wordpress dependancies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Local dependancies.
 */
import { saveSettings, saveSubscriber } from './handlers';

const confirmationMessageHeading   = __( 'Thank You', 'newsletter-optin-box' );
const confirmationMessageBody      = __( 'You have successfully subscribed to this newsletter.', 'newsletter-optin-box' );
const unsubscriptionMessageHeading = __( 'Unsubscribed', 'newsletter-optin-box' );
const unsubscriptionMessageBody    = __( "You have been unsubscribed from this mailing list and won't receive any emails from us.", 'newsletter-optin-box' );

/**
 * Returns an array of wizard steps.
 */
export const wizardSteps = [
	{
		title: __( 'Newsletter subscriptions', 'newsletter-optin-box' ),
		description: __( 'Let\'s get you started with Noptin. Configure your subscription preferences', 'newsletter-optin-box' ),
		settings: [
			{
				settingKey: 'success_message',
				label: __( 'Default Success Message', 'newsletter-optin-box' ),
				el: 'textarea',
				help: __( 'This is the message shown to people after they successfully sign up for your newsletter.', 'newsletter-optin-box' ),
				placeholder: __( 'Thanks for subscribing to our newsletter', 'newsletter-optin-box' ),
			},
			{
				settingKey: 'double_optin',
				label: __( 'Enable Double Opt-in', 'newsletter-optin-box' ),
				el: 'input',
				type: 'toggle',
				help: __( 'Require users to confirm their email address before they are added to your list', 'newsletter-optin-box' ),
			},
			{
				settingKey: 'pages_confirm_page_message',
				label: __( 'Confirmation Page Message', 'newsletter-optin-box' ),
				el: 'textarea',
				help: __( 'This is the message shown to people after they confirm their email address.', 'newsletter-optin-box' ),
				placeholder: `<h1>${ confirmationMessageHeading }</h1>\n\n<p>${ confirmationMessageBody }</p>`,
				if: 'double_optin',
			},
			{
				settingKey: 'pages_unsubscribe_page_message',
				label: __( 'Unsubscribe Page Message', 'newsletter-optin-box' ),
				el: 'textarea',
				help: __( 'This is the message shown to people after they unsubscribe from your newsletter.', 'newsletter-optin-box' ),
				placeholder: `<h1>${ unsubscriptionMessageHeading }</h1>\n\n<p>${ unsubscriptionMessageBody }</p>`,
			},
		],
		handler: saveSettings,
	},

	{
		title: __( 'Email Sending', 'newsletter-optin-box' ),
		description: __( 'Who\'s the sender of the emails you\'ll be sending?', 'newsletter-optin-box' ),
		settings: [
			{
				settingKey: 'from_name',
				label: __( '"From" Name', 'newsletter-optin-box' ),
				el: 'input',
				type: 'text',
				help: __( 'How the sender name appears in outgoing emails', 'newsletter-optin-box' ),
			},
			{
				settingKey: 'from_email',
				label: __( '"From" Email', 'newsletter-optin-box' ),
				el: 'input',
				type: 'email',
				help: __( 'How the sender email appears in outgoing emails', 'newsletter-optin-box' ),
			},
			{
				settingKey: 'reply_to',
				label: __( '"Reply-to" Email', 'newsletter-optin-box' ),
				el: 'input',
				type: 'email',
				help: __( 'Where replies to your emails should be sent', 'newsletter-optin-box' ),
			},
		],
		handler: saveSettings,
	},

	{
		title: __( 'Newsletter', 'newsletter-optin-box' ),
		description: __( 'Subscribe to our newsletter to get the latest news and updates.', 'newsletter-optin-box' ),
		settings: [
			{
				settingKey: 'noptin_signup_name',
				label: __( 'Your Name', 'newsletter-optin-box' ),
				el: 'input',
				type: 'text',
			},
			{
				settingKey: 'noptin_signup_email',
				label: __( 'Your Email Address', 'newsletter-optin-box' ),
				el: 'input',
				type: 'email',
			},
		],
		handler: saveSubscriber,
	},
];
