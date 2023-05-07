import { CardBody, ToggleControl, Button, Spinner } from '@wordpress/components';
import Section from '../section';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Local dependencies
 */
import List from '../list';

/**
 * Formats a date object.
 *
 * @param {Object} date
 * @return {string}
 */
function formatDate( date ) {

	if ( ! date ) {
		return '-';
	}

	const theDate = new Date();

	if ( 'string' === typeof date ) {
		theDate.setTime( Date.parse( date ) );
	} else {

		// Date contains date string and timezone.
		theDate.setTime( Date.parse( date.date ) );
	}

	return theDate.toLocaleString();
}

/**
 * Save the automation rule.
 *
 * @param {Object} props
 * @param {Object} props.automationRule The automation rule.
 * @param {Function} props.setError
 * @param {Function} props.setSuccess
 * @param {Function} props.setAutomationRule
 * @param {Boolean} props.isSaving
 * @param {Function} props.setIsSaving
 * @return {JSX.Element}
 */
export default function Save({automationRule, setError, setSuccess, setAutomationRule, isSaving, setIsSaving }) {

	const api_route = automationRule.id > 0 ? `/noptin/v1/automation_rules/${automationRule.id}` : '/noptin/v1/automation_rules/';

	/**
	 * Saves the automation rule.
	 *
	 * @returns {Promise<void>}
	 */
	const saveAutomationRule = () => {
		setIsSaving(true);
		setError( null );
		setSuccess( null );

		// Save the automation rule.
		apiFetch( {
			path: api_route,
			method: 'POST',
			data: automationRule,
		} )

		// Update the state on success.
		.then( ( res ) => {

			// Display a success message.
			setSuccess( __( 'Automation rule saved.', 'newsletter-optin-box' ) );

			// Update the automation rule.
			setAutomationRule({
				...automationRule,
				...res,
			});

			return res;
		} )

		// Update the url on success.
		.then( ( res ) => {
			if ( res.id > 0 ) {
				window.history.replaceState( {}, '', `?page=noptin-automation-rules&noptin_edit_automation_rule=${res.id}` );
			}
		} )

		// Display an error on failure.
		.catch( ( err ) => {
			if ( err.message ) {
				setError( err.message );
			} else {
				setError( __( 'An error occurred while saving.', 'newsletter-optin-box' ) );
			}
		} )

		// Reset the state.
		.finally( () => {
			setIsSaving(false);
		} );

	}

	/**
	 * Key value pairs of the automation rule details.
	 */
	const details = [
		{
			label: __( 'ID', 'newsletter-optin-box' ),
			value: automationRule.id ? automationRule.id : __( 'New', 'newsletter-optin-box' ),
		},
		{
			label: __( 'Status', 'newsletter-optin-box' ),
			value: (
				<ToggleControl
					label={automationRule.status ? __( 'Active', 'newsletter-optin-box' ) : __( 'Inactive', 'newsletter-optin-box' )}
					checked={automationRule.status ? true : false}
					className="noptin-mb0"
					onChange={(value) => {
						setAutomationRule({
							...automationRule,
							status: value,
						});
					}}
					__nextHasNoMarginBottom
				/>
			),
		},
	]

	if ( automationRule.id > 0 ) {
		details.push(
			{
				label: __( 'Times Run', 'newsletter-optin-box' ),
				value: automationRule.times_run,
			},
			{
				label: __( 'Created at', 'newsletter-optin-box' ),
				value: formatDate( automationRule.created_at ),
			},
			{
				label: __( 'Updated at', 'newsletter-optin-box' ),
				value: formatDate( automationRule.updated_at ),
			},
		);

		if ( ! Array.isArray( automationRule.metadata ) ) {
			Object.keys( automationRule.metadata ).forEach( ( key ) => {

				// Skip if value is not scalar.
				if ( typeof automationRule.metadata[key] !== 'string' && typeof automationRule.metadata[key] !== 'number' ) {
					return;
				}

				// Add the item to the details.
				details.push( {
					label: key,
					value: automationRule.metadata[key],
				} );
			} );
		}
	}

	return (
		<Section title={__( 'Automation Rule', 'newsletter-optin-box' )}>

			<CardBody>

				<List items={details} />

				<Button
					variant="primary"
					onClick={saveAutomationRule}
					disabled={isSaving}
					isPressed={isSaving}
				>
					{!isSaving && __('Save Automation', 'newsletter-optin-box')}
					{isSaving && __('Saving...', 'newsletter-optin-box')}
					{isSaving && <Spinner /> }
				</Button>

			</CardBody>

		</Section>
	);
}
