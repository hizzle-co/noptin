import { compact } from 'lodash';
import { __ } from "@wordpress/i18n";
import { useState, useEffect, useMemo } from "@wordpress/element";
import { Notice, Tip, TextControl, ToggleControl, SelectControl, Flex, Button, FlexItem } from "@wordpress/components";
import Papa from 'papaparse';

/**
 * Normalizes a string to make it possible to guess the header.
 *
 * @param {string} string The string to normalize.
 * @returns {string} The normalized string.
 */
const normalizeString = ( string ) => {

	// Strip all non-alphanumeric characters.
	string = string.replace( /[^a-zA-Z0-9]/g, '' );

	// Convert to lowercase.
	string = string.toLowerCase();

	// If begins with cf_ or meta_, remove it.
	if ( string.startsWith( 'cf' ) ) {
		string = string.slice( 2 );
	}

	if ( string.startsWith( 'meta' ) ) {
		string = string.slice( 4 );
	}

	return string;
}

/**
 * Allows the user to map a single header.
 *
 * @param {Object} props
 * @param {Array} props.options The available options.
 * @param {Object} props.field The field.
 * @param {string} props.value The current value.
 * @param {Function} props.setValue The function to set the value.
 * @param {string} props.customValue The current custom value.
 * @param {Function} props.setCustomValue The function to set the custom value.
 */
const MapHeader = ( { options, field, value, setValue, customValue, setCustomValue } ) => {

	const [ filteredOptions, setFilteredOptions ] = useState( options );

	// Filters the available options.
	const filterOptions = ( search ) => {
		setFilteredOptions(
			options.filter( ( option ) =>
				option.label
					.toLowerCase()
					.includes( search.toLowerCase() )
			)
		)
	}

	return (
		<Flex style={{marginTop: '1.6rem'}} gap={4} justify="flex-start" wrap>

			<FlexItem style={{width: '320px'}}>
				<SelectControl
            		label={ field.label }
					value={ value }
           			onChange={ setValue }
            		options={ options }
					__next36pxDefaultSize
					__nextHasNoMarginBottom
        		/>
			</FlexItem>

			{ '-1' === value && (
				<FlexItem style={{width: '320px'}}>
					<TextControl
						label={ __( 'Enter value', 'newsletter-optin-box' ) }
						placeholder={ __( 'Enter a value to assign all imported records', 'newsletter-optin-box' ) }
						value={ customValue }
						onChange={ setCustomValue }
						__next36pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</FlexItem>
			) }

		</Flex>
	);
}

/**
 * Allows the user to map CSV headers to fields.
 *
 * @param {Object} props
 * @param {Object} props.file The file to import.
 * @param {Object} props.schema The schema of the collection.
 * @param {Array} props.ignore The fields to ignore.
 * @param {Array} props.hidden The fields to hide.
 * @param {Function} props.back The callback to call when clicking on the back button.
 * @param {Function} props.onContinue A callback to call when the headers are set.
 */
const MapHeaders = ( { file, schema, ignore, hidden, back, onContinue } ) => {

	// Prepare state.
	const [ fileHeaders, setFileHeaders ] = useState( [] );
	const [ mappedHeaders, setMappedHeaders ] = useState( {} );
	const [ updateRecords, setUpdateRecords ] = useState( false );
	const [ error, setError ] = useState( null );

	const fields = useMemo( () => compact( schema.map((field) => {

		// If the field is hidden or ignored, don't show it.
		if ( field.readonly || ignore.includes( field.name ) || hidden.includes( field.name ) ) {
			return null;
		}

		return {
			value: field.name,
			label: field.label,
			is_boolean: field.is_boolean,
		};
	})), [ schema, ignore, hidden ]);

	// Parse the first few lines of the file to get the headers.
	useEffect(() => {
		Papa.parse( file, {
			header: true,
			skipEmptyLines: 'greedy',
			preview: 5,
			complete: ( results ) => {
				setFileHeaders( results.meta.fields );
			},
			error( error ) {
				setError( error );
			},
		});
	}, [ file ]);

	// Try guessing the headers.
	useEffect(() => {
		if ( ! fileHeaders.length ) {
			return;
		}

		const guessedHeaders = {};

		// Loop through the headers.
		fileHeaders.forEach(( header ) => {

			const normalizedHeader = normalizeString( header );

			// Loop through the fields.
			const match = fields.find((field) => {

				const normalizedKey = normalizeString( field.value );
				const normalizedLabel = normalizeString( field.label );

				return normalizedHeader === normalizedKey || normalizedHeader === normalizedLabel;
			});

			if ( match ) {
				guessedHeaders[ match.value ] = {
					mapped: true,
					value: header,
					is_boolean: match.is_boolean,
				}
			}
		});

		setMappedHeaders( guessedHeaders );

	}, [ fileHeaders, fields ]);

	const selectValues = useMemo(() => {

		const values = [
			{
				value: '',
				label: __( 'Ignore Field', 'newsletter-optin-box' ),
			},
			{
				value: '-1',
				label: __( 'Manually enter value', 'newsletter-optin-box' ),
			},
			{
				value: '-2',
				label: __( 'Map Field', 'newsletter-optin-box' ),
				disabled: true,
			},
		];

		fileHeaders.forEach( header => {
			values.push({
				value: header,
				label: header,
			})
		});

		return values;
	}, [ fileHeaders ]);

	// If we have an error, display it.
	if ( error ) {

		return (
			<Notice status="error" isDismissible={false}>
				<p>{ error.message }</p>
				<Button variant="link" onClick={ back }>
					{ __( 'Try again', 'newsletter-optin-box' ) }
				</Button>
			</Notice>
		);
	}

	return (
		<>
			<Tip>
				{ __( 'Map the headers of your CSV file to known fields.', 'newsletter-optin-box' ) }
			</Tip>

			{ fields.map((field) => {
				const header = mappedHeaders[ field.value ] || { is_boolean: field.is_boolean };
				const value  = header.value || '';

				return (
					<MapHeader
						key={ field.value }
						options={ selectValues }
						field={ field }
						value={ value }
						setValue={ ( newValue ) => {
							setMappedHeaders({
								...mappedHeaders,
								[ field.value ]: {
									...header,
									mapped: ! ( [ '', '-1', '-2'].includes( value ) ),
									value: newValue,
								}
							});
						} }
						customValue={ header.customValue || '' }
						setCustomValue={ ( newValue ) => {
							setMappedHeaders({
								...mappedHeaders,
								[ field.value ]: {
									...header,
									customValue: newValue,
								}
							});
						} }
					/>
				);
			})}

			<div style={{marginTop: '1.6rem'}}>
				<ToggleControl
					label={ __( 'Update existing records', 'newsletter-optin-box' ) }
					checked={ updateRecords }
					onChange={ ( newValue ) => setUpdateRecords( newValue ) }
					__nextHasNoMarginBottom
				/>
			</div>

			<Button style={{marginTop: '1.6rem'}} variant="primary" onClick={ () => onContinue( mappedHeaders, updateRecords ) }>
				{ __( 'Import', 'newsletter-optin-box' ) }
			</Button>

			<Button style={{marginTop: '1.6rem'}} variant="link" onClick={ back }>
				{ __( 'Back', 'newsletter-optin-box' ) }
			</Button>
		</>
	);
}

export default MapHeaders;
