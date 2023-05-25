import domReady from '@wordpress/dom-ready';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import fadeOut from './utils/fade-out';

// Init the subscribers page.
domReady(() => {

	// Check when .noptin-toggle-subscription-status checkbox changes, save via ajax.
	document.querySelectorAll('.noptin-toggle-subscription-status').forEach((checkbox) => {
		checkbox.addEventListener('change', () => {
			const isChecked = checkbox.checked;
			const row = checkbox.closest('tr');
			const subscriberID = row.dataset.id;

			apiFetch({
				path: '/noptin/v1/subscribers/' + subscriberID,
				method: 'POST',
				data: {
					status: isChecked ? 'subscribed' : 'unsubscribed',
				},
			}).catch((err) => {
				console.log(err);
			});

			// Get td.column-status and update the inner HTML
			const statusColumn = row.querySelector('.column-status');

			if ( ! statusColumn ) {
				return;
			}

			if (isChecked) {
				statusColumn.innerHTML = '<span class="noptin-badge success">' + __( 'Subscribed', 'newsletter-optin-box' ) + '</span>';
			} else {
				statusColumn.innerHTML = '<span class="noptin-badge notification">' + __( 'Unsubscribed', 'newsletter-optin-box' ) + '</span>';
			}
		});
	});

	// Delete subscriber when .noptin-record-action__delete is clicked.
	document.querySelectorAll('.noptin-record-action__delete').forEach((button) => {
		button.addEventListener('click', (e) => {
			e.preventDefault();

			// Confirm the user wants to delete the subscriber.
			if ( ! confirm( __( 'Are you sure you want to delete this subscriber?', 'newsletter-optin-box' ) ) ) {
				return;
			}

			const row = button.closest('tr');
			const subscriberID = row.dataset.id;

			apiFetch({
				path: '/noptin/v1/subscribers/' + subscriberID,
				method: 'DELETE',
			}).catch((err) => {
				console.log(err);
			});

			fadeOut(row);
		});
	});
});
