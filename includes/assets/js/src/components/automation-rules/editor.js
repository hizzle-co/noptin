/**
 * WordPress dependencies
 */
import { Flex, FlexBlock, FlexItem, Notice, Spinner, SlotFillProvider } from '@wordpress/components';
import { useState, useEffect, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Save from './save';
import EditSections from './edit-sections';
import { MergeTagsThickboxModal } from '../merge-tags';
import ErrorBoundary from '../collection/error-boundary';

/**
 * Displays the editor heading.
 *
 * @param {Object} props
 * @param {number} props.id The automation rule ID.
 * @param {string} props.createNewUrl The create new url.
 * @return {JSX.Element}
 */
function EditorHeading( { id, createNewUrl } ) {

	const isSaved = !!( id && parseInt( id ) > 0 );

	return (
		<h1 className="wp-heading-inline">
			{isSaved && (
				<>
					<span>{__( 'Edit Automation Rule', 'newsletter-optin-box' )}</span>
					<a href={createNewUrl} className="page-title-action">{__( 'Add New', 'newsletter-optin-box' )}</a>
				</>
			)}
			{!isSaved && (
				<span>{__( 'Add Automation Rule', 'newsletter-optin-box' )}</span>
			)}
		</h1>
	);
}

/**
 * Returns a list of available smart tags.
 *
 * @param {Object} smartTags
 * @param {Object} trigger_settings
 * @return {Array}
 */
export function getAvailableSmartTags( smartTags, trigger_settings ) {
	const tags = [];

	if ( !smartTags ) {
		return tags;
	}

	Object.keys( smartTags ).forEach( key => {

		const smartTag = smartTags[key];

		// Check if conditions have been met.
		if ( smartTag.conditions ) {

			// Check if all conditions have been met.
			const condition_matched = smartTag.conditions.every( condition => {

				if ( Array.isArray( condition.value ) ) {
					var matched = condition.value.some( val => val == trigger_settings[condition.key] );
				} else {
					var matched = condition.value == trigger_settings[condition.key];
				}

				const should_match = condition.operator === 'is';

				return matched === should_match;
			} );

			if ( !condition_matched ) {
				return;
			}
		}

		let label = key;

		if ( smartTag.label ) {
			label = smartTag.label;
		} else if ( smartTag.description ) {
			label = smartTag.description;
		}

		tags.push( {
			smart_tag: key,
			label,
			example: smartTag.example ? smartTag.example : '',
			description: smartTag.description ? smartTag.description : '',
			placeholder: smartTag.placeholder ? smartTag.placeholder : '',
			conditional_logic: smartTag.conditional_logic ? smartTag.conditional_logic : false,
			options: smartTag.options ? smartTag.options : [],
		} )
	} );

	return tags;
}

/**
 * Displays the automation rule editor.
 *
 * @param {Object} props
 * @param {number} props.id The automation rule ID.
 * @param {string} props.action The automation rule action.
 * @param {string} props.trigger The automation rule trigger.
 * @param {string} props.createNewUrl The create new url.
 * @returns {JSX.Element}
 */
export default function AutomationRuleEditor( { id, action, trigger, settings, smartTags, createNewUrl } ) {

	// Default automation rule.
	const defaultAutomationRule = {
		action_id: action,
		trigger_id: trigger,
		action_settings: {},
		status: true,
		trigger_settings: {},
	}

	// On mount, read default values from the settings.
	useEffect( () => {
		if ( settings ) {
			Object.values( settings ).forEach( settingGroup => {

				if ( !['trigger_settings', 'action_settings'].includes( settingGroup.prop ) || !settingGroup.settings ) {
					return;
				}

				// Loop through the settings and pluck the default values.
				Object.keys( settingGroup.settings ).forEach( setting => {
					const settingData = settingGroup.settings[setting];

					if ( typeof settingData.default !== 'undefined' ) {
						defaultAutomationRule[settingGroup.prop][setting] = settingData.default;
					}
				} );
			} );
		}
	}, [settings] );

	// Prepare the app.
	const [automationRule, setAutomationRule] = useState( { ...defaultAutomationRule } );
	const [saving, setSaving] = useState( false );
	const [loading, setLoading] = useState( 1 );
	const [error, setError] = useState( null );
	const [success, setSuccess] = useState( null );
	const availableSmartTags = useMemo( () => getAvailableSmartTags( smartTags, automationRule.trigger_settings || {} ), [smartTags, automationRule.trigger_settings] );

	// Action ID.
	const isLoading = loading > 0;

	/**
	 * Load the automation rule whenever the rule ID changes.
	 */
	useEffect( () => {
		if ( id > 0 ) {

			// Set loading to true.
			setLoading( loading + 1 );
			setError( null );
			setSuccess( null );

			// Fetch the automation rule.
			apiFetch( {
				path: `/noptin/v1/automation_rules/${id}`,
			} ).then( ( response ) => {
				if ( response ) {
					setAutomationRule( response );
				}
			} ).catch( ( err ) => {
				setAutomationRule( null );
				setError( err.message );
			} ).finally( () => {
				setLoading( loading - 1 );
			} );
		} else {
			setLoading( loading - 1 );
		}
	}, [id] );

	const style = {
		opacity: isLoading || saving ? 0.5 : 1,
		pointerEvents: isLoading || saving ? 'none' : 'auto',
	};

	return (
		<div className="noptin-automation-rule__editor" style={style}>
			<ErrorBoundary>
				<SlotFillProvider>
					<EditorHeading id={automationRule.id} createNewUrl={createNewUrl} />
					<Flex wrap align="top">

						<FlexBlock className="noptin-es6-editor__main">

							{error && (
								<Notice status="error" onDismiss={() => { setError( null ) }}>
									{error}
								</Notice>
							)}

							{success && (
								<Notice status="success" onDismiss={() => { setSuccess( null ) }}>
									{success}
								</Notice>
							)}

							{isLoading && <Spinner />}

							{!isLoading && <EditSections settings={settings} automationRule={automationRule} setAutomationRule={setAutomationRule} availableSmartTags={availableSmartTags} />}

						</FlexBlock>

						<FlexItem className="noptin-component-editor__sidebar">
							<Save
								automationRule={automationRule}
								setAutomationRule={setAutomationRule}
								setError={setError}
								setSuccess={setSuccess}
								isSaving={saving}
								setIsSaving={setSaving}
							/>
						</FlexItem>

					</Flex>
					{!isLoading && <MergeTagsThickboxModal availableSmartTags={availableSmartTags} />}
				</SlotFillProvider>
			</ErrorBoundary>
		</div>
	);
}
