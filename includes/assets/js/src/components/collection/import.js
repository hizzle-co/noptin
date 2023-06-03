/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { useState, useEffect, useMemo, useCallback } from "@wordpress/element";
import { Notice, FormFileUpload, Tip, TextControl, ToggleControl, SelectControl, BaseControl, Flex, FlexItem, CardBody, Button, FlexBlock } from "@wordpress/components";
import { upload } from "@wordpress/icons";
import { __ } from "@wordpress/i18n";
import Papa from 'papaparse';
import { compact } from 'lodash';

/**
 * Local dependencies.
 */
import { useSchema } from "../../store-data/hooks";
import { useRoute } from "./hooks";
import Wrap from "./wrap";

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
 * Allows the user to map CSV headers to fields.
 *
 * @param {Object} props
 * @param {Object} props.file The file to import.
 * @param {Object} props.schema The schema of the collection.
 * @param {Array} props.ignore The fields to ignore.
 * @param {Array} props.hidden The fields to hide.
 * @param {Function} props.back The callback to call when clicking on the back button.
 * @param {Function} props.setHeaders A callback to call when the headers are set.
 */
const MapHeaders = ( { file, schema, ignore, hidden, back, setHeaders } ) => {

	// Prepare state.
	const [ fileHeaders, setFileHeaders ] = useState( [] );
	const [ mappedHeaders, setMappedHeaders ] = useState( {} );
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
					<Flex key={ field.value } style={{marginBottom: '1.6rem'}} wrap>

						<FlexBlock>
							<SelectControl	
								label={ field.label }
								value={ value }
								options={ selectValues }
								onChange={ ( newValue ) => {
									setMappedHeaders({
										...mappedHeaders,
										[ field.value ]: {
											...header,
											mapped: ! ( [ '', '-1', '-2'].includes( value ) ),
											value: newValue,
										}
									});
								} }
							/>
						</FlexBlock>

						{ '-1' === value && (
							<FlexBlock>
								<TextControl
									label={ __( 'Enter value', 'newsletter-optin-box' ) }
									value={  header.customValue || '' }
									onChange={ ( newValue ) => {
										setMappedHeaders({
											...mappedHeaders,
											[ field.value ]: {
												...header,
												customValue: newValue,
											}
										});
									} }
								/>
							</FlexBlock>
						) }

					</Flex>
				);
			})}

			<Button variant="primary" onClick={ () => setHeaders( mappedHeaders ) }>
				{ __( 'Import', 'newsletter-optin-box' ) }
			</Button>
		</>
	)
}

/**
 * Handles the actual import.
 *
 * @param {Object} props
 * @param {Object} props.file The file to import.
 * @param {Object} props.headers Known fields to file headers mapping.
 * @param {Function} props.back The callback to call when clicking on the back button.
 * @param {string} props.id_prop The property to use as ID.
 */
const HandleImport = ( { file, headers, back, id_prop } ) => {

	const [ error, setError ] = useState( null );
	const [ loading, setLoading ] = useState( true );

	// Parses a record.
	const parseRecord = useCallback(( record ) => {
		const parsed = {};

		Object.keys( headers ).forEach(( key ) => {

			// Abort if the header is not mapped.
			if ( '' === headers[ key ].value ) {
				return;
			}

			// Are we mapping the field?
			if ( headers[ key ].mapped ) {
				parsed[ key ] = record[ headers[ key ].value ];
			} else if ( undefined !== headers[ key ].customValue ) {
				parsed[ key ] = headers[ key ].customValue;
			}

			// If the field is a boolean, convert it.
			if ( headers[ key ].is_boolean ) {
				parsed[ key ] = [ '0', '', 'false', 'FALSE', 'no'].includes( parsed[ key ] ) ? false : true;
			}

		});

		return parsed;
	}, [ headers ]);

	// Parse the file.
	useEffect(() => {

		Papa.parse( file, {
			header: true,
			skipEmptyLines: 'greedy',
			step: (results, parser) => {
	
				console.log( parseRecord( results.data ) );

			},
			complete: ( results ) => {
				console.log( results );
			},
			error(error, file) {
				console.log(error);
			},
		});

	}, [ file ]);

	return null;
}

/**
 * Displays the import tool.
 *
 * @param {Object} props
 * @param {Object} props.file The file to import.
 * @param {Object} props.schema The schema of the collection.
 * @param {Function} props.back The callback to call when clicking on the back button.
 */
const ImportFile = ( { file, schema: { schema, ignore, hidden, id_prop }, back } ) => {

	const [ mappedHeaders, setMappedHeaders ] = useState( null );

	// If we have no headers, map them.
	if ( ! mappedHeaders ) {
		return (
			<MapHeaders
				file={ file }
				schema={ schema }
				ignore={ ignore }
				hidden={ hidden }
				back={ back }
				setHeaders={ setMappedHeaders }
			/>
		);
	}

	// Display the importer.
	return <HandleImport file={ file } headers={ mappedHeaders } back={ back } id_prop={ id_prop } />;
}

/**
 * Displays the file input.
 *
 * @param {Object} props
 */
const FileInput = ( { onUpload } ) => {

	return (
		<>
			<BaseControl
				label={ __( 'This tool allows you to import existing records from a CSV file.', 'newsletter-optin-box' ) }
				help={ __( 'The first row of the CSV file should contain the field names/headers.', 'newsletter-optin-box' ) }
				className="noptin-collection__upload-wrapper"
			>
				<FormFileUpload
					accept="text/csv"
					onChange={ ( event ) => onUpload( event.currentTarget.files[0] ) }
					icon={upload}
					variant="primary"
				>
					{ __( 'Select a CSV file', 'newsletter-optin-box' ) }
				</FormFileUpload>

			</BaseControl>

			<Tip>
				{ __( ' Have a different file type?', 'newsletter-optin-box' ) }&nbsp;
				<Button
					variant="link"
					href="https://convertio.co/csv-converter/"
					target="_blank"
					text={ __( 'Convert it to CSV', 'newsletter-optin-box' ) }
				/>
			</Tip>
		</>
	);
}

/**
 * Allows the user to import new records.
 *
 * @param {Object} props
 * @param {Object} props.component
 * @param {string} props.component.title
 */
export default function Import( { component: { title } } ) {

	// Fetch the schema.
	const { namespace, collection } = useRoute();
	const schema = useSchema(namespace, collection);
	const [ file, setFile ] = useState( null );

	return (
		<Wrap title={title}>

			<CardBody>
				{ file ? (
					<ImportFile file={ file } schema={ schema.data } back={ () => setFile( null )} />
				) : (
					<FileInput onUpload={ setFile } />
				) }
			</CardBody>
		</Wrap>
	);

}
