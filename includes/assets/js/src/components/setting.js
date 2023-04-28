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
	Tip,
	Button,
	Flex,
	FlexBlock,
	FlexItem,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useState } from '@wordpress/element';

/**
 * Local dependancies.
 */
import ConditionalLogicEditor from './conditional-logic-editor';
import { MergeTagsModal } from './merge-tags';

/**
 * Input types.
 */
const inputTypes = ['number', 'search', 'email', 'password', 'tel', 'url'];

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

	// Are we showing the modal?
	const [showSmartTags, setShowSmartTags] = useState(false);

	// Closes the modal.
	const closeModal = useCallback(() => {
		setShowSmartTags(false);
	}, [setShowSmartTags]);

	// Handle merge tag click.
	const handleMergeTagClick = useCallback((mergeTag) => {

		if ( onMergeTagClick ) {
			onMergeTagClick(mergeTag);
			closeModal();
		}
	});

	// If we have merge tags, show the merge tags button.
	let suffix = null;
	let modal  = null;

	if ( Array.isArray(availableSmartTags) && availableSmartTags.length > 0 ) {

		// If we are showing the modal, show the modal.
		modal = (
			<MergeTagsModal
				isOpen={showSmartTags}
				closeModal={closeModal}
				availableSmartTags={availableSmartTags}
				onMergeTagClick={handleMergeTagClick}
			/>
		);

		suffix = (
			<Button
				icon="shortcode"
				variant="tertiary"
				isPressed={showSmartTags}
				label={__( 'Insert merge tag', 'newsletter-optin-box' )}
				onClick={() => {
					setShowSmartTags(true);
				}}
				showTooltip
			/>
		);
	}

	return [suffix, modal];
}

/**
 * Displays an input setting
 *
 * @param {Object} props
 * @param {Function} props.attributes The attributes
 * @param {Object} props.setting The setting object.
 * @param {Array} props.availableSmartTags The available smart tags.
 * @return {JSX.Element}
 */
function InputSetting({ setting, availableSmartTags, ...attributes }) {

	// On add merge tag...
	const onMergeTagClick = useCallback((mergeTag) => {

		// Add the merge tag to the value.
		if ( attributes.onChange ) {
			attributes.onChange(attributes.value ? attributes.value + mergeTag : mergeTag);
		}
	}, [attributes.value, attributes.onChange]);

	// Merge tags.
	const [suffix, modal] = useMergeTags( availableSmartTags, onMergeTagClick );

	if ( setting.disabled ) {
		attributes.readOnly = true;
		attributes.onFocus = (e) => e.target.select();
	}

	return (
		<>
			{modal}
			<InputControl
				{...attributes}
				type={inputTypes.includes( setting.type ) ? setting.type : 'text'}
				placeholder={setting.placeholder ? setting.placeholder : ''}
				suffix={suffix}
				isPressEnterToChange
				__nextHasNoMarginBottom
				__next36pxDefaultSize
			/>
		</>
	);
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

	const [currentField, setCurrentField] = useState(false);

	// On add merge tag...
	const onMergeTagClick = useCallback((mergeTag) => {

		if ( Array.isArray( currentField ) ) {
			value = [...value];
			value[currentField[0]][currentField[1]] += mergeTag;
			onChange(value);
		}
	}, [currentField, value, onChange]);

	// Merge tags.
	const [suffix, modal] = useMergeTags( availableSmartTags, onMergeTagClick );

	// The base props.
	const { baseControlProps, controlProps } = useBaseControlProps( attributes );

	// Ensure the value is an array.
	if ( ! Array.isArray( value ) ) {
		value = [];
	}

	// Displays a single field in the repeater.
	const Field = useCallback(({ item, field, index, onChange }) => {
		return (
			<FlexBlock>
				<InputControl
					label={field.label}
					type={field.type}
					value={item[field.id] === undefined ? '' : item[field.id]}
					placeholder={sprintf( __( 'Enter %s', 'noptin-addons-pack' ), field.label )}
					className="noptin-component__field noptin-condition-field"
					suffix={suffix}
					onChange={onChange}
					onFocus={() => { setCurrentField([index, field.id])}}
					isPressEnterToChange
					__nextHasNoMarginBottom
					__next36pxDefaultSize
				/>
			</FlexBlock>
		);
	}, [suffix]);

	// Displays a single Item.
	const Item = useCallback(({ item, index }) => {
		return (
			<Flex className="noptin-repeater-item" wrap>

				{keyValueRepeaterFields.map((field, fieldIndex) => (
					<Field
						key={fieldIndex}
						index={index}
						item={item}
						field={field}
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
					/>
				</FlexItem>
			</Flex>
		);
	}, [value, onChange]);

	// Render the control.
	return (
		<BaseControl { ...baseControlProps }>
			{modal}

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
	const help = setting.description ? <div dangerouslySetInnerHTML={ { __html: setting.description } } /> : '';

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

		// If we have a placeholder, add it to the options.
		if ( setting.placeholder ) {
			options.unshift({
				label: setting.placeholder,
				value: '',
				disabled: true,
			});
		}

		return <SelectControl {...defaultAttributes} options={options}  __nextHasNoMarginBottom __next36pxDefaultSize />;
	}

	// Conditional logic editor.
	if ( setting.el === 'conditional_logic' ) {
		return (
			<ConditionalLogicEditor {...defaultAttributes} availableSmartTags={availableSmartTags} comparisons={setting.comparisons} />
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
				availableSmartTags={availableSmartTags}
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
				availableSmartTags={availableSmartTags}
			/>
		);
	}

	return settingKey;
}
