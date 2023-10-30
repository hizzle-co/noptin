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
import { useMemo, useCallback } from '@wordpress/element';
import { useMergeTags } from './setting';
import getEnumBadge from './collection/records-table/enum-colors';
import { Badge } from './styled-components';

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
function usePlaceholder( array, placeholder ) {
	return useMemo( () => {

		return [
			{
				label: placeholder,
				value: '',
				disabled: true,
			},
			...array,
		];
	}, [ array, placeholder ] );
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
 * @param {Number} props.index
 * @param {Object} props.availableSmartTags
 * @param {Function} props.updateRule
 * @param {Function} props.removeRule
 * @param {Boolean} props.isLastRule
 * @return {JSX.Element}
 */
export function ConditionalLogicRule({ rule, comparisons, availableSmartTags, index, updateRule, removeRule }) {

	const updateValue = useCallback( ( value ) => updateRule( index, 'value', value ), [ index, updateRule ] );
	const updateCondition = useCallback( ( value ) => updateRule( index, 'condition', value ), [ index, updateRule ] );
	const localRemoveRule = useCallback( () => removeRule( index ), [ index, removeRule ] );

	// Container for the matching smart tag.
	const smartTag = useMemo( () => {

		const tag = rule.type;

		if ( availableSmartTags[tag] !== undefined ) {
			return availableSmartTags[tag];
		}

		// Convert first occurrence of _ to .
		const altTag = tag.replace( '_', '.', 1 );
		if ( availableSmartTags[altTag] !== undefined ) {
			return availableSmartTags[altTag];
		}

		for ( const [key, value] of Object.entries( availableSmartTags ) ) {
			// Check without prefix.
			if ( key.indexOf( '.' ) !== -1 ) {
				const withoutPrefix = key.split( '.' ).slice( 1 );
				if ( withoutPrefix.join( '.' ) === tag ) {
					return value;
				}
			}

			// Converts a space or comma separated list to array.
			const split = ( string ) => Array.isArray( string ) ? string : string.split( /[\s,]+/ );

			// Check deprecated alternatives.
			if ( value.deprecated && split(value.deprecated).includes( tag ) ) {
				return value;
			}
		}

		return null;
	}, [ rule.type, availableSmartTags ] );

	// Returns the label to use for the rule type.
	const ruleLabel = useMemo( () => {

		// Abort if rule.type is not in available smart tags.
		if ( ! smartTag ) {
			return rule.type;
		}

		const group = smartTag.group || 'General';
		const label = smartTag.label || rule.type;

		return `${group} >> ${label}`;
	}, [ rule.type, availableSmartTags ] );

	// Contains available options.
	const availableOptions = usePlaceholder(
		useOptions( smartTag?.options ),
		__( 'Select a value', 'newsletter-optin-box' )
	);

	// Checks whether the selected condition type has options.
	const hasOptions = availableOptions.length > 1;

	// Contains data type.
	const dataType = smartTag?.conditional_logic || 'string';

	// Sets available comparisons for the selected condition.
	const availableComparisons = usePlaceholder(
		useMemo( () => {
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
		}, [ dataType, comparisons ] ),
		__( 'Select a comparison', 'newsletter-optin-box' )
	);

	const skipValue = 'is_empty' === rule.condition || 'is_not_empty' === rule.condition;

	return (
		<Flex className="noptin-component__field-lg" wrap expanded>

			<FlexBlock>
				<Badge {...getEnumBadge( ruleLabel )}>{ ruleLabel }</Badge>
			</FlexBlock>

			<FlexBlock>
				<Flex justify="flex-end" wrap>
					<FlexItem>
						<SelectControl
							label={ __( 'Comparison', 'newsletter-optin-box' ) }
							hideLabelFromVision={ true }
							value={ rule.condition ? rule.condition : 'is' }
							options={ availableComparisons }
							onChange={ updateCondition }
							size="default"
							__nextHasNoMarginBottom
						/>
					</FlexItem>

					{ ! skipValue && (
						<FlexBlock>

							{hasOptions && (
								<SelectControl
									label={ __( 'Value', 'newsletter-optin-box' ) }
									hideLabelFromVision={ true }
									value={ rule.value ? rule.value : '' }
									options={availableOptions}
									onChange={ updateValue }
									size="default"
									__nextHasNoMarginBottom
								/>
							)}

							{!hasOptions && (
								<TextControl
									type={ 'number' === dataType ? 'number' : 'text' }
									label={ __( 'Value', 'newsletter-optin-box' ) }
									hideLabelFromVision={ true }
									value={ rule.value ? rule.value : '' }
									onChange={ updateValue }
									__nextHasNoMarginBottom
								/>
							)}
						</FlexBlock>
					)}

					<FlexItem>
						<Button onClick={ localRemoveRule } icon="trash" variant="tertiary" isDestructive />
					</FlexItem>
				</Flex>
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
function useOptions( options ) {

	return useMemo( () => {

		if ( ! options ) {
			return [];
		}

		// Arrays.
		if ( Array.isArray( options ) ) {
			return options.map( ( option, index ) => {
				return {
					label: option,
					value: index,
				};
			} );
		}

		// Objects.
		return Object.keys( options ).map( ( key ) => {
			return {
				label: options[ key ],
				value: key,
			};
		} );
	}, [ options ] );
}

/**
 * Displays the available conditional logic rules.
 *
 * @param {Object} props
 * @param {Array} props.rules
 * @param {Object} props.comparisons
 * @param {Array} props.availableSmartTags
 * @param {Function} props.setConditionalLogicAttribute
 * @return {JSX.Element}
 */
export function ConditionalLogicRules({ rules, comparisons, availableSmartTags, setConditionalLogicAttribute }) {

	// Filter available smart rules.
	const theRules = useMemo( () => {

		if ( ! Array.isArray( rules ) ) {
			return [];
		}

		return rules.filter( rule => rule.type && rule.type !== '' );
	}, [ availableSmartTags ] );

	// Filter available smart tags to only include those that support conditional logic.
	const filteredSmartTags = useMemo( () => {
		const types = {};

		availableSmartTags.forEach( ( smartTag ) => {
			if ( smartTag.conditional_logic ) {
				types[ smartTag.smart_tag ] = {
					...smartTag,
					key: smartTag.smart_tag,
					type: smartTag.conditional_logic,
				};
			}
		} );

		return types;
	}, [ availableSmartTags ] );

	/**
	 * Removes a rule from the conditional logic.
	 *
	 * @param {Number} index
	 */
	const removeRule = useCallback( ( index ) => {
		const newRules = [ ...theRules ];
		newRules.splice( index, 1 );
		setConditionalLogicAttribute( 'rules', newRules );
	}, [ theRules, setConditionalLogicAttribute ] );

	/**
	 * Updates a rule in the conditional logic.
	 *
	 * @param {Number} index
	 * @param {String} key
	 * @param {String} value
	 */
	const updateRule = useCallback(( index, key, value ) => {
		const newRules = [ ...theRules ];
		newRules[ index ][ key ] = value;
		setConditionalLogicAttribute( 'rules', newRules );
	}, [ theRules, setConditionalLogicAttribute ] );

	// Merge tags array.
	const mergeTagsArray = useMemo( () => Object.values( filteredSmartTags ), [ filteredSmartTags ] );

	/**
	 * Adds a new conditional logic rule.
	 */
	const addRule = useCallback( ( smartTag ) => {
		const smartTagObject = filteredSmartTags[smartTag];
		const options     = smartTagObject?.options || [];
		const placeholder = smartTagObject?.placeholder || '';
		let value       = ( Array.isArray( options ) && options.length) ? Object.keys( options )[0] : placeholder;

		// If the smartTag has a default value.
		if ( smartTagObject?.default ) {
			value = smartTagObject.default;
		}

		const newRules = [ ...theRules ];
		newRules.push( {
			type: smartTag,
			condition: 'is',
			value,
		} );
		setConditionalLogicAttribute( 'rules', newRules );
	}, [ theRules ] );

	// Button to add a new condition.
	const text         = theRules.length ? __( 'Add a rule', 'newsletter-optin-box' ) : __( 'Add another rule', 'newsletter-optin-box' );
	const addCondition = useMergeTags({
		availableSmartTags: mergeTagsArray,
		onMergeTagClick: addRule,
		raw: true,
		icon: 'plus',
		label:  text,
		text,
		toggleProps: { variant: 'secondary' }
	});

	return (
		<div className="noptin-conditional-logic-rules">
			{theRules.map( ( rule, index ) => (
				<ConditionalLogicRule
					key={ index }
					rule={ rule }
					index={ index }
					updateRule={ updateRule }
					removeRule={ removeRule }
					comparisons={ comparisons }
					availableSmartTags={filteredSmartTags}
				/>
			) )}
			{addCondition}
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
						ruleCount={ Array.isArray(value.rules) ? value.rules.length : 0 }
						type={ value.type }
						action={ value.action }
						setConditionalLogicAttribute={ setConditionalLogicAttribute }
					/>

					<ConditionalLogicRules
						rules={ value.rules }
						comparisons={ comparisons }
						availableSmartTags={ availableSmartTags }
						setConditionalLogicAttribute={ setConditionalLogicAttribute }
					/>
				</>
			)}
		</div>
	);
}
