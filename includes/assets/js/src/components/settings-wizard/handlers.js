import apiFetch from '@wordpress/api-fetch';

/**
 * Saves settings via the REST API (noptin/v1/settings).
 *
 * @param {Object} settings
 * @param {Function} next
 */
export function saveSettings( settings, next ) {

	apiFetch( {
		path: '/noptin/v1/settings',
		method: 'POST',
		data: {settings},
	} ).catch( ( err ) => {
		console.error( err );
	} );

	next( settings );
}

/**
 * Saves a subscriber to https://noptin.com/wp-json/noptin/v1/subscribers.
 *
 * @param {Object} subscriber
 * @param {Function} next
 */
export function saveSubscriber( subscriber, next ) {

	if ( subscriber.noptin_signup_email.length > 0 ) {
		window.fetch( 'https://noptin.com/wp-json/noptin/v1/subscribers', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({
				email: subscriber.noptin_signup_email,
				name: subscriber.noptin_signup_name,
				source: 'settings-wizard',
			}),
		} ).catch( ( err ) => {
			console.error( err );
		} );
	}

	next( subscriber );
}
