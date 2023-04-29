import {
	TextControl,
	SelectControl,
	ToggleControl,
	Flex,
	FlexItem,
	FlexBlock,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';

// Action.
const ifOptions = [
	{
		label: __( 'Only run if', 'newsletter-optin-box' ),
		value: 'allow',
	},
	{
		label: __( 'Do not run if', 'newsletter-optin-box' ),
		value: 'prevent',
	},
]

// Matches.
const typeOptions = [
	{
		label: __( 'all', 'newsletter-optin-box' ),
		value: 'all',
	},
	{
		label: __( 'any', 'newsletter-optin-box' ),
		value: 'any',
	},
]

/**
 * Adds a placeholder to the beginning of an array.
 *
 * @param {Array} array
 * @param {String} placeholder
 * @return {Array}
 */
function addPlaceholder( array, placeholder ) {
	return [
		{
			label: placeholder,
			value: '',
			disabled: true,
		},
		...array,
	];
}

/**
 * Displays the conditional logic editor type selector.
 *
 * @param {Object} props
 * @param {String} props.type
 * @param {String} props.action
 * @param {Function} props.setConditionalLogicAttribute
 * @return {JSX.Element}
 */
export function ConditionalLogicTypeSelector({ type, action, ruleCount, setConditionalLogicAttribute }) {

	const hasMultiple = ruleCount > 1;

	return (
		<Flex className="noptin-component__field-lg" wrap>
			<FlexItem>
				<SelectControl
					label={ __( 'If', 'newsletter-optin-box' ) }
					hideLabelFromVision={ true }
					value={ action ? action : 'allow' }
					options={ifOptions}
					onChange={ ( val ) => setConditionalLogicAttribute( 'action', val ) }
					size="default"
					__nextHasNoMarginBottom
				/>
			</FlexItem>

			{hasMultiple && (
				<>
					<FlexItem>
						<SelectControl
							label={ __( 'all', 'newsletter-optin-box' ) }
							hideLabelFromVision={ true }
							value={ type ? type : 'all' }
							options={ typeOptions }
							onChange={ ( val ) => setConditionalLogicAttribute( 'type', val ) }
							size="default"
							__nextHasNoMarginBottom
						/>
					</FlexItem>
					<FlexBlock>
						{__( 'of the following rules are true:', 'newsletter-optin-box' )}
					</FlexBlock>
				</>
			)}
		</Flex>
	);
}

/**
 * Displays a single conditional logic rule.
 *
 * @param {Object} props
 * @param {Object} props.rule
 * @param {Object} props.comparisons
 * @param {String} props.availableConditionTypes
 * @param {Function} props.updateRule
 * @param {Function} props.removeRule
 * @param {Boolean} props.isLastRule
 * @param {Boolean} props.isFirstRule
 * @param {String} props.conditionType
 * @return {JSX.Element}
 */
export function ConditionalLogicRule({ rule, comparisons, availableConditionTypes, updateRule, removeRule, conditionType, isLastRule, isFirstRule }) {

	// Fetches a condition type.
	const getConditionType = ( type ) => availableConditionTypes[ type ];

	// Retrieves the selected condition type.
	const selectedConditionType = useMemo( () => getConditionType( rule.type ) || {}, [ availableConditionTypes, rule.type ] );

	// Contains available options.
	const availableOptions = useMemo( () => prepareOptions( selectedConditionType.options ), [ selectedConditionType ] );

	// Checks whether the selected condition type has options.
	const hasOptions = availableOptions.length > 0;

	// Contains data type.
	const dataType = useMemo( () => selectedConditionType.type ? selectedConditionType.type : 'string', [ selectedConditionType ] );

	// Sets available comparisons for the selected condition.
	const availableComparisons = useMemo( () => {
		const types = [];

		// Filter object of available condition types to include where key === rule.type.
		Object.keys( comparisons ).forEach( key => {
			let comparison_type = comparisons[key].type;

			if ( hasOptions ) {

				if ( 'string' === dataType && 'is' != key  && 'is_not' != key ) {
					return;
				}

				if ( 'is_empty' === key || 'is_not_empty' === key || 'is_between' === key ) {
					return;
				}
			}

			if ( 'any' === comparison_type || comparison_type == dataType ) {
				types.push(
					{
						label: comparisons[ key ].name,
						value: key,
					}
				);
			}
		});
		return types;
	}, [ dataType ] );

	// Sets the default type and the available comparisons.
	let defaultConditionType = '';
	const conditionOptions = [];

	Object.keys( availableConditionTypes ).forEach( ( key ) => {
		const conditionType = availableConditionTypes[ key ];

		if ( '' === defaultConditionType ) {
			defaultConditionType = conditionType.type;
		}

		conditionOptions.push( {
			label: conditionType.label,
			value: key,
		} );
	} );

	// Handles an update and sets any default values.
	const handleUpdate = ( key, value ) => {
		updateRule( key, value );

		if ( 'type' !== key && '' === rule.type ) {
			updateRule( 'type', defaultConditionType );
		}

		if ( 'condition' !== key && '' === rule.condition ) {
			updateRule( 'condition', 'is' );
		}

		if ( 'type' === key ) {
			updateRule( 'condition', 'is' );
			updateRule( 'value', '' );
		}
	}

	const skipValue = 'is_empty' === rule.condition || 'is_not_empty' === rule.condition;
	const showSelect = hasOptions && ! skipValue;
	const showInput = ! hasOptions && ! skipValue;

	return (
		<Flex className="noptin-component__field-lg" wrap>

			<FlexItem>
				<SelectControl
					label={ __( 'Condition Type', 'newsletter-optin-box' ) }
					hideLabelFromVision={ true }
					value={ rule.type ? rule.type : defaultConditionType }
					options={addPlaceholder( conditionOptions, __( 'Select a condition', 'newsletter-optin-box' ) )}
					onChange={ ( val ) => handleUpdate( 'type', val ) }
					size="default"
					__nextHasNoMarginBottom
				/>
			</FlexItem>

			<FlexItem>
				<SelectControl
					label={ __( 'Comparison', 'newsletter-optin-box' ) }
					hideLabelFromVision={ true }
					value={ rule.condition ? rule.condition : 'is' }
					options={addPlaceholder( availableComparisons, __( 'Select a comparison', 'newsletter-optin-box' ) )}
					onChange={ ( val ) => handleUpdate( 'condition', val ) }
					size="default"
					__nextHasNoMarginBottom
				/>
			</FlexItem>

			<FlexItem>

				{showSelect && (
					<SelectControl
						label={ __( 'Value', 'newsletter-optin-box' ) }
						hideLabelFromVision={ true }
						value={ rule.value ? rule.value : '' }
						options={addPlaceholder( availableOptions, __( 'Select a value', 'newsletter-optin-box' ) )}
						onChange={ ( val ) => updateRule( 'value', val ) }
						size="default"
						__nextHasNoMarginBottom
					/>
				)}

				{showInput && (
					<TextControl
						type={ 'number' === dataType ? 'number' : 'text' }
						label={ __( 'Value', 'newsletter-optin-box' ) }
						hideLabelFromVision={ true }
						value={ rule.value ? rule.value : '' }
						onChange={ ( val ) => updateRule( 'value', val ) }
						__nextHasNoMarginBottom
					/>
				)}
			</FlexItem>

			<FlexItem>
				<Button onClick={ removeRule } icon="trash"/>
			</FlexItem>

			<FlexBlock>
				{ ! isLastRule && (
					<>
						{conditionType === 'any' && __( 'or', 'newsletter-optin-box' )}
						{conditionType === 'all' && __( 'and', 'newsletter-optin-box' )}
					</>
				)}
			</FlexBlock>
		</Flex>
	);
}

/**
 * Prepares the available options for the selected condition.
 *
 * @param {Array|Object} options
 * @return {Array}
 */
function prepareOptions( options ) {

	const prepared = [];

	if ( ! options ) {
		return prepared;
	}

	// Arrays.
	if ( Array.isArray( options ) ) {
		options.forEach( ( option, index ) => {
			prepared.push( {
				label: option,
				value: index,
			} );
		} );

		return prepared;
	}

	// Objects.
	Object.keys( options ).forEach( ( key ) => {
		prepared.push( {
			label: options[ key ],
			value: key,
		} );
	});

	return prepared;
}

/**
 * Displays the available conditional logic rules.
 *
 * @param {Object} props
 * @param {Array} props.rules
 * @param {String} props.conditionType
 * @param {Object} props.comparisons
 * @param {Array} props.availableSmartTags
 * @param {Function} props.setConditionalLogicAttribute
 * @return {JSX.Element}
 */
export function ConditionalLogicRules({ rules, conditionType, comparisons, availableSmartTags, setConditionalLogicAttribute }) {

	const theRules = Array.isArray( rules ) ? rules : [];

	/**
	 * Removes a rule from the conditional logic.
	 *
	 * @param {Number} index
	 */
	const removeRule = ( index ) => {
		const newRules = [ ...theRules ];
		newRules.splice( index, 1 );
		setConditionalLogicAttribute( 'rules', newRules );
	};

	/**
	 * Updates a rule in the conditional logic.
	 *
	 * @param {Number} index
	 * @param {String} key
	 * @param {String} value
	 */
	const updateRule = ( index, key, value ) => {
		const newRules = [ ...theRules ];
		newRules[ index ][ key ] = value;
		setConditionalLogicAttribute( 'rules', newRules );
	};

	// Sets available condition types.
	const availableConditionTypes = useMemo( () => {
		const types = {};

		availableSmartTags.forEach( ( smartTag ) => {
			if ( smartTag.conditional_logic ) {
				types[ smartTag.smart_tag ] = {
					key: smartTag.smart_tag,
					label: smartTag.label,
					options: smartTag.options,
					type: smartTag.conditional_logic,
					placeholder: smartTag.placeholder ? smartTag.placeholder : '',
				};
			}
		} );
	
		return types;
	}, [ availableSmartTags ] );

	/**
	 * Adds a new conditional logic rule.
	 */
	const addRule = () => {
		const type        = Object.keys(availableConditionTypes)[0];
		const options     = availableConditionTypes[type].options;
		const placeholder = availableConditionTypes[type].placeholder ? availableConditionTypes[type].placeholder : '';
		const value       = ( Array.isArray( options ) && options.length) ? Object.keys( options )[0] : placeholder;

		const newRules = [ ...theRules ];
		newRules.push( {
			type,
			condition: 'is',
			value,
		} );
		setConditionalLogicAttribute( 'rules', newRules );
	};

	const count = theRules.length;
	return (
		<div className="noptin-conditional-logic-rules">
			{theRules.map( ( rule, index ) => (
				<ConditionalLogicRule
					key={ index }
					rule={rule}
					updateRule={ ( key, value ) => updateRule( index, key, value ) }
					removeRule={ () => removeRule( index ) }
					availableConditionTypes={ availableConditionTypes }
					isLastRule={ index === count - 1 }
					isFirstRule={ index === 0 }
					conditionType={ conditionType }
					comparisons={ comparisons }
				/>
			) )}
			<Button
				className="noptin-add-conditional-rule"
				onClick={ addRule }
				variant="secondary"
			>
				{ 0 === count ? __( 'Add a rule', 'newsletter-optin-box' ) : __( 'Add another rule', 'newsletter-optin-box' )}
			</Button>
		</div>
	);
}

/**
 * Displays the conditional logic editor.
 *
 * @param {Object} props
 * @param {String} props.className The class name.
 * @param {String} props.label The label.
 * @param {String} props.prop The prop to update.
 * @param {Array} props.availableSmartTags The available smart tags.
 * @param {Object} props.comparisons The available comparisons.
 * @param {Object} props.value The current value.
 * @param {String} props.toggleText The toggle text.
 * @param {Function} props.onChange
 * @return {JSX.Element}
 */
export default function ConditionalLogicEditor({ onChange, value, comparisons, toggleText, availableSmartTags, className }) {

	// If value is not an Object, set it to the default.
	if ( typeof value !== 'object' ) {
		value = {
			enabled: false,
			action: 'allow',
			rules: [{condition: 'is', type: 'date', value: ''}],
			type: 'all',
		};
	}

	// Sets conditional logic attribute.
	const setConditionalLogicAttribute = ( prop, val ) => {
		onChange( {
			...value,
			[ prop ]: val,
		} );
	};

	return (
		<div className={ className }>
			<ToggleControl
				checked={ value.enabled ? true : false }
				onChange={ ( val ) => setConditionalLogicAttribute( 'enabled', val ) }
				className="noptin-component__field"
				label={ toggleText ? toggleText : __( 'Optionally enable/disable this trigger depending on specific conditions.', 'newsletter-optin-box' ) }
				__nextHasNoMarginBottom
			/>

			{ value.enabled && (
				<>

					<ConditionalLogicTypeSelector
						ruleCount={ value.rules ? value.rules.length : 0 }
						type={ value.type }
						action={ value.action }
						setConditionalLogicAttribute={ setConditionalLogicAttribute }
					/>

					<ConditionalLogicRules
						rules={ value.rules }
						conditionType={ value.type }
						comparisons={ comparisons }
						availableSmartTags={ availableSmartTags }
						setConditionalLogicAttribute={ setConditionalLogicAttribute }
					/>
				</>
			)}
		</div>
	);
}
