/**
 * Wordpress dependancies.
 */
import {
	__experimentalInputControl as InputControl,
	BaseControl,
	useBaseControlProps,
	TextareaControl,
	SelectControl,
	ToggleControl,
	CheckboxControl,
	FormTokenField,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	Tip,
	Button,
	Flex,
	FlexBlock,
	FlexItem,
} from '@wordpress/components';
import { next } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useState, useMemo } from '@wordpress/element';

/**
 * Local dependancies.
 */
import ConditionalLogicEditor from './conditional-logic-editor';

/**
 * Input types.
 */
const inputTypes = ['number', 'search', 'email', 'password', 'tel', 'url', 'date'];

/**
 * Displays an input setting
 *
 * @param {Object} props
 * @param {Function} props.attributes The attributes
 * @param {Object} props.setting The setting object.
 * @param {Array} props.availableSmartTags The available smart tags.
 * @return {JSX.Element}
 */
function InputSetting({ setting, availableSmartTags, isPressEnterToChange, ...attributes }) {

	// If press enter to change is undefined, set it to true.
	if ( isPressEnterToChange === undefined ) {
		isPressEnterToChange = true;
	}

	// On add merge tag...
	const onMergeTagClick = useCallback((mergeTag) => {

		// Add the merge tag to the value.
		if ( attributes.onChange ) {
			attributes.onChange(attributes.value ? `${attributes.value} ${mergeTag}`.trim() : mergeTag);
		}
	}, [attributes.value, attributes.onChange]);

	// Merge tags.
	const suffix = useMergeTags( availableSmartTags, onMergeTagClick );

	if ( setting.disabled ) {
		attributes.readOnly = true;
		attributes.onFocus = (e) => e.target.select();
	}

	return (
		<>
			<InputControl
				{...attributes}
				type={inputTypes.includes( setting.type ) ? setting.type : 'text'}
				placeholder={setting.placeholder ? setting.placeholder : ''}
				suffix={suffix}
				isPressEnterToChange={isPressEnterToChange}
				__nextHasNoMarginBottom
				__next36pxDefaultSize
			/>
		</>
	);
}

/**
 * Key value repeater fields.
 */
const keyValueRepeaterFields = [
	{
		id: 'key',
		label: __( 'Key', 'noptin-addons-pack' ),
		type: 'text',
	},
	{
		id: 'value',
		label: __( 'Value', 'noptin-addons-pack' ),
		type: 'text',
	},
];

/**
 * Makes it possible to use the merge tag selector in a field.
 *
 * @param {Array} availableSmartTags The available smart tags.
 * @param {Function} onMergeTagClick The on merge tag click callback.
 * @return {Array}
 */
function useMergeTags(availableSmartTags, onMergeTagClick) {

	// Dropdown menu controls.
	const groups = useMemo(() => {

		if ( ! Array.isArray(availableSmartTags) ) {
			return {};
		}

		const groups = {};

		availableSmartTags.forEach((smartTag) => {
			const group   = smartTag.group ? smartTag.group : __( 'General', 'newsletter-optin-box' );
			groups[group] = groups[group] ? groups[group] : [];

			groups[group].push( smartTag );
		});

		return groups;
	}, [availableSmartTags]);
console.log(groups);
	const totalGroups = Object.keys(groups).length;

	// If we have merge tags, show the merge tags button.
	let inserter = null;

	if ( totalGroups > 0 ) {

		inserter = (
			<DropdownMenu
				icon="shortcode"
				label={__( 'Insert merge tag', 'newsletter-optin-box' )}
				showTooltip
			>
		 		{ ( { onClose } ) => (
		 			<>
						{ Object.keys(groups).map((group, index) => (
							<MenuGroup label={ totalGroups > 1 ? group : undefined} key={index}>
								{ groups[group].map((item) => (
									<MenuItem
										icon={ item.icon || next }
										iconPosition="left"
										onClick={ () => {
											if ( onMergeTagClick ) {
												onMergeTagClick(`[[${getMergeTagValue(item)}]]`);
											}
						
											onClose();
										}}
										key={ item.smart_tag }
									>
										{ item.label }
									</MenuItem>
								)) }
							</MenuGroup>
						)) }
		 			</>
		 		) }
		 	</DropdownMenu>
		);
	}

	return inserter;
}

/**
 * Returns a merge tag's value.
 *
 * @param {Object} smartTag The smart tag.
 * @return {Array}
 */
function getMergeTagValue(smartTag) {

	if ( smartTag.example ) {
		return smartTag.example;
	}

	if ( ! smartTag.default ) {
		return `${smartTag.smart_tag}`;
	}

	return `${smartTag.smart_tag} default="${smartTag.default}"`;
}

/**
 * Displays a key value repeater setting.
 *
 * @param {Object} props
 * @param {Function} props.attributes The attributes
 * @param {Object} props.setting The setting object.
 * @param {Array} props.availableSmartTags The available smart tags.
 * @return {JSX.Element}
 */
function KeyValueRepeater({ setting, availableSmartTags, value, onChange, ...attributes }) {

	// The base props.
	const { baseControlProps, controlProps } = useBaseControlProps( attributes );

	// Ensure the value is an array.
	if ( ! Array.isArray( value ) ) {
		value = [];
	}

	// Displays a single Item.
	const Item = useCallback(({ item, index }) => {
		return (
			<Flex className="noptin-repeater-item" wrap>

				{keyValueRepeaterFields.map((field, fieldIndex) => (
					<KeyValueRepeaterField
						key={fieldIndex}
						availableSmartTags={ availableSmartTags }
						field={field}
						value={item[field.id] === undefined ? '' : item[field.id]}
						onChange={(newValue) => {
							const newItems = [...value];
							newItems[index][field.id] = newValue;
							onChange(newItems);
						}}
					/>
				))}

				<FlexItem>
					<Button
						icon="trash"
						variant="tertiary"
						className="noptin-component__field"
						label={__( 'Delete', 'noptin-addons-pack' )}
						showTooltip
						onClick={() => {
							const newValue = [...value];
							newValue.splice(index, 1);
							onChange(newValue);
						}}
						isDestructive
					/>
				</FlexItem>
			</Flex>
		);
	}, [value, onChange]);

	// Render the control.
	return (
		<BaseControl { ...baseControlProps }>

			<div { ...controlProps }>
				{value.map((item, index) => <Item key={index} item={item} index={index} />)}
				<Button
					onClick={() => {
						const newValue = [...value];
						newValue.push({});
						onChange(newValue);
					}}
					variant="secondary"
				>
					{ setting.add_field ? setting.add_field : __( 'Add', 'newsletter-optin-box' )}
				</Button>
			</div>

		</BaseControl>
	);
}

/**
 * Displays a key value repeater setting field.
 *
 * @param {Object} props
 * @param {Function} props.onChange The on change handler
 * @param {String} props.value The current value.
 * @param {Object} props.field The field object.
 * @param {Array} props.availableSmartTags The available smart tags.
 * @return {JSX.Element}
 */
function KeyValueRepeaterField({ field, availableSmartTags, value, onChange }) {

	// On add merge tag...
	const onMergeTagClick = useCallback((mergeTag) => {

		// Add the merge tag to the value.
		if ( onChange ) {
			onChange(value ? `${value} ${mergeTag}`.trim() : mergeTag);
		}
	}, [value, onChange]);

	// Merge tags.
	const suffix = useMergeTags( availableSmartTags, onMergeTagClick );

	return (
		<FlexBlock>
			<InputControl
				label={field.label}
				type={field.type}
				value={value}
				placeholder={sprintf( __( 'Enter %s', 'noptin-addons-pack' ), field.label )}
				className="noptin-component__field noptin-condition-field"
				suffix={suffix}
				onChange={onChange}
				isPressEnterToChange
				__nextHasNoMarginBottom
				__next36pxDefaultSize
			/>
		</FlexBlock>
	);
}

/**
 * Displays a multi-checkbox setting.
 *
 * @param {Object} props
 * @param {Function} props.attributes The attributes
 * @param {Object} props.setting The setting object.
 * @return {JSX.Element}
 */
function MultiCheckbox({ setting, value, options, onChange, ...attributes }) {

	// The base props.
	const { baseControlProps, controlProps } = useBaseControlProps( attributes );

	// Ensure the value is an array.
	if ( ! Array.isArray( value ) ) {
		value = [];
	}

	// Render the control.
	return (
		<BaseControl { ...baseControlProps }>

			<div { ...controlProps }>
				{options.map((option, index) => (
					<CheckboxControl
						key={index}
						label={option.label}
						checked={value.includes(option.value)}
						onChange={(newValue) => {
							if ( newValue ) {
								onChange([...value, option.value]);
							} else {
								onChange(value.filter((v) => v !== option.value));
							}
						}}
					/>
				))}
			</div>

		</BaseControl>
	);
}

/**
 * Displays a single setting.
 *
 * @param {Object} props
 * @param {String} props.settingKey The setting key.
 * @param {String} props.prop The property to update on the object.
 * @param {Object} props.saved The object to update.
 * @param {Function} props.setAttributes The function to update the object.
 * @param {Object} props.setting The setting object.
 * @param {Array} props.availableSmartTags The available smart tags.
 * @return {JSX.Element}
 */
export default function Setting({ settingKey, setting, availableSmartTags, prop, saved, setAttributes }) {

	/**
	 * Updates an object setting.
	 *
	 * @param {mixed} value  The new value.
	 */
	const updateSetting = useCallback( ( value ) => {

		// If this is a root setting, update the object directly.
		if ( ! prop ) {
			return setAttributes({ [ settingKey ]: value });
		}

		// If this is a nested setting, update the object directly.
		const oldValue = saved[ prop ] ? saved[ prop ] : {};
		const newValue = {
			[ prop ]: {
				...oldValue,
				[ settingKey ]: value,
			},
		};

		setAttributes( newValue );
	}, [ settingKey, prop, saved, setAttributes ] );

	// Simple condition.
	if ( setting.if || setting.restrict ) {

		// Check if we're separating with period.
		const parts = setting.restrict ? setting.restrict.split( '.' ) : setting.if.split( '.' );

		// If we have two parts, we're checking a nested setting.
		if ( parts.length === 2 && ! ( saved[ parts[0] ] && saved[ parts[0] ][ parts[1] ] ) ) {
			return null;
		}

		// If we have one part, we're checking a root setting.
		if ( parts.length === 1 && ! saved[ parts[0] ] ) {
			return null;
		}
	}

	// Abort early if condition is not met.
	if ( setting.condition && ! setting.condition( saved ) ) {
		return null;
	}

	// Prepare the current value.
	let value = saved[ settingKey ];

	// If this is a nested setting, get the value from the nested object.
	if ( prop ) {
		value = saved[ prop ] ? saved[ prop ][ settingKey ] : undefined;
	}

	// If undefined, use the default value.
	if ( value === undefined || setting.disabled ) {
		value = setting.default;
	}

	// Do we have a value?
	const hasValue = value !== undefined && value !== '' && value !== null;

	// If we have options, convert from object to array.
	let options = [];
	if ( setting.options ) {
		options = Object.keys( setting.options ).map( ( key ) => {
			return {
				label: setting.options[ key ],
				value: key,
			};
		});
	}

	// Classname for the field.
	const className = setting.fullWidth ? `noptin-component__field noptin-component__field-${settingKey}` : `noptin-component__field-lg noptin-component__field-${settingKey}`;

	// Help text.
	const help = setting.description ? <span dangerouslySetInnerHTML={ { __html: setting.description } } /> : '';

	// Default attributes.
	const defaultAttributes = {
		label: setting.label,
		value: hasValue ? value : '',
		onChange: updateSetting,
		className: `${className}`,
		help: help,
	}

	// Display select control.
	if ( setting.el === 'select' ) {

		// Add a placeholder option.
		options.unshift({
			label: setting.placeholder ? setting.placeholder : __( 'Select an option', 'newsletter-optin-box' ),
			value: '',
			disabled: ! setting.canSelectPlaceholder,
		});

		return <SelectControl {...defaultAttributes} options={options}  __nextHasNoMarginBottom __next36pxDefaultSize />;
	}

	// Display a form token field.
	if ( setting.el === 'form_token' ) {
		return (
			<FormTokenField
				{...defaultAttributes}
				value={Array.isArray( defaultAttributes.value ) ? defaultAttributes.value : []}
				suggestions={Array.isArray( setting.suggestions ) ? setting.suggestions : []}
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
		);
	}

	// Displays a multi-checkbox control.
	if ( setting.el === 'multi_checkbox' || setting.el === 'multi_checkbox_alt' ) {
		return <MultiCheckbox {...defaultAttributes} options={options} />;
	}

	// Conditional logic editor.
	if ( setting.el === 'conditional_logic' ) {
		return (
			<ConditionalLogicEditor
				{...defaultAttributes}
				availableSmartTags={availableSmartTags}
				comparisons={setting.comparisons}
				toggleText={setting.toggle_text}
			/>
		);
	}

	// Text input field.
	if ( setting.el === 'input' ) {

		// Checkbox or toggle.
		if ( setting.type && ['toggle', 'switch', 'checkbox', 'checkbox_alt'].includes( setting.type ) ) {
			return (
				<ToggleControl
					{...defaultAttributes}
					checked={hasValue ? value : false}
					onChange={(newValue) => {
						updateSetting(newValue);
					}}
				/>
			);
		}

		return (
			<InputSetting
				{...defaultAttributes}
				setting={setting}
				availableSmartTags={'trigger_settings' === prop ? [] : availableSmartTags}
				isPressEnterToChange={setting.isInputToChange ? false : true}
			/>
		);
	}

	// Textarea field.
	if ( setting.el === 'textarea' ) {
		return (
			<TextareaControl
				{...defaultAttributes}
				setting={setting}
				placeholder={setting.placeholder ? setting.placeholder : ''}
				__nextHasNoMarginBottom
			/>
		);
	}

	// Paragraph.
	if ( setting.el === 'paragraph' ) {
		return (
			<div className={className}>
				<Tip>
					{setting.content}
				</Tip>
			</div>
		);
	}

	// Heading.
	if ( setting.el === 'hero' ) {
		return (
			<div className={className}>
				<h3>{setting.content}</h3>
			</div>
		);
	}

	// Key value repeater.
	if ( setting.el === 'key_value_repeater' || setting.el === 'webhook_key_value_repeater' ) {
		return (
			<KeyValueRepeater
				{...defaultAttributes}
				setting={setting}
				availableSmartTags={'trigger_settings' === prop ? [] : availableSmartTags}
			/>
		);
	}

	return settingKey;
}
