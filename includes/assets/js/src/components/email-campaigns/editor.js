/**
 * WordPress dependencies
 */
import { Flex, FlexBlock, FlexItem, Notice, Spinner, SlotFillProvider } from '@wordpress/components';
import { useState, useEffect, useMemo, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Local dependancies.
 */
import Setting from '../setting';
import { MergeTagsThickboxModal } from '../merge-tags';
import { getAvailableSmartTags } from '../automation-rules/editor';

/**
 * Displays the email campaigns editor.
 *
 * @param {Object} props
 * @param {Object} props.settings The trigger settings.
 * @param {Object} props.smartTags The smart tags.
 * @param {Object} props.saved The saved settings.
 * @param {string} props.action The automation rule action.
 * @param {string} props.trigger The automation rule trigger.
 * @param {string} props.triggerDescription The trigger description.
 * @param {string} props.triggerName The trigger name.
 * @returns {JSX.Element}
 */
export default function EmailCampaignEditor({ action, trigger, saved, settings, smartTags, triggerDescription, triggerName}) {

	// Prepare the app.
	const availableSmartTags      = useMemo(() => getAvailableSmartTags(smartTags, saved), [smartTags, saved]);
	const settingKeys             = Object.keys( settings );
	const [ options, setOptions ] = useState( saved );

	/**
	 * Sets a saved attribute.
	 *
	 * @param {Object} attributes
	 */
	const setSaved = ( attributes ) => {
		setOptions({
			...options,
			...attributes,
		});
	}

	return (
		<div className="noptin-emails-conditional-logic__editor-inner">
			<SlotFillProvider>

				{settingKeys.map( ( settingKey ) => (
					<Setting
						key={settingKey}
						settingKey={settingKey}
						availableSmartTags={availableSmartTags}
						saved={options}
						setAttributes={setSaved}
						setting={settings[ settingKey]}
					/>
				))}

				<MergeTagsThickboxModal availableSmartTags={availableSmartTags} />

				<input
					type="hidden"
					name="noptin_trigger_settings"
					value={JSON.stringify(options)}
				/>

			</SlotFillProvider>
		</div>
	);
}
