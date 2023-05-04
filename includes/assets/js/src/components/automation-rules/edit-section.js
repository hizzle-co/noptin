/**
 * WordPress dependancies.
 */
import { CardBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Local dependancies.
 */
import Setting from '../setting';
import Section from '../section';

/**
 * Displays a single edit section.
 *
 * @param {Object} props
 * @param {String} props.sectionKey
 * @param {String} props.name
 * @param {String} props.prop
 * @param {Array} props.availableSmartTags
 * @param {Object} props.automationRule
 * @param {Function} props.setAutomationRule
 * @param {Object} props.settings
 * @return {JSX.Element}
 */
export default function EditSection({ sectionKey, label, prop, availableSmartTags, settings, automationRule, setAutomationRule }) {

	const settingKeys = Object.keys( settings );

	if ( ! settingKeys.length ) {
		return null;
	}

	/**
	 * Sets automation rule attributes.
	 *
	 * @param {Object} attributes
	 */
	const setAttributes = useCallback( ( attributes ) => {
		setAutomationRule({
			...automationRule,
			...attributes,
		});
	}, [ automationRule, setAutomationRule ] );

	return (
		<Section title={label} className={`noptin-automation-rule-editor__section noptin-automation-rule-editor__section-${sectionKey}`}>

			<CardBody>

				{settingKeys.map( ( settingKey ) => (
					<Setting
						key={settingKey}
						settingKey={settingKey}
						prop={prop}
						availableSmartTags={ 'trigger_settings' === prop ? [] : availableSmartTags}
						saved={automationRule}
						setAttributes={setAttributes}
						setting={settings[ settingKey]}
					/>
				))}
			</CardBody>

		</Section>
	);
}
