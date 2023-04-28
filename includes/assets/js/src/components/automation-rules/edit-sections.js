import EditSection from './edit-section';

/**
 * Displays a section edit zones.
 *
 * @param {Object} props
 * @param {Object} props.settings
 * @param {Array} props.availableSmartTags
 * @param {Object} props.automationRule
 * @param {Function} props.setAutomationRule
 * @return {JSX.Element}
 */
export default function EditSections({ settings, availableSmartTags, automationRule, setAutomationRule }) {

	if ( ! settings || ! automationRule ) {
		return null;
	}

	const settingKeys = Object.keys( settings );

	if ( ! settingKeys.length ) {
		return null;
	}

	return (
		<div className="noptin-automation-rule-editor__sections">

			{settingKeys.map( ( settingKey ) => (
				<EditSection
					key={settingKey}
					sectionKey={settingKey}
					availableSmartTags={availableSmartTags}
					automationRule={automationRule}
					setAutomationRule={setAutomationRule}
					{...settings[ settingKey ]}
				/>
			))}
		</div>
	);
}
