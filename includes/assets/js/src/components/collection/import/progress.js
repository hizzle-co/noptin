/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { useState, useCallback, useEffect } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";
import Papa from 'papaparse';
import { Notice } from "@wordpress/components";

/**
 * Converts an array of strings to bytes.
 */
const toBytes = ( arr ) => {
	return arr.reduce( ( acc, str ) => {

		if ( typeof TextEncoder === 'undefined' ) {
			return acc + str.length + 1;
		}

		if ( TextEncoder ) {
			return acc + ( new TextEncoder().encode( str ) ).length + 1;
		}
	}, 0 );
};

/**
 * Import batches, grouped by file name.
 */
const importBatches = {};

/**
 * Imports the file and displays the progress.
 *
 * @param {Object} props
 * @param {Object} props.file The file to import.
 * @param {Object} props.headers Known fields to file headers mapping.
 * @param {Function} props.back The callback to call when clicking on the back button.
 * @param {string} props.id_prop The property to use when checking for duplicates.
 */
const Progress = ( { file, headers, back, id_prop, updateRecords, namespace, collection } ) => {

	const [ errors, setErrors ] = useState( [] );
	const [ done, setDone ] = useState( false );
	const [ updated, setUpdated ] = useState( 0 );
	const [ failed, setFailed ] = useState( 0 );
	const [ created, setCreated ] = useState( 0 );
	const [ imported, setImported ] = useState( 0 );

	// Imports a batch of records.
	const importBatch = ( cb = () => {} ) => {
		console.log( importBatches[ file.name ] );

		setTimeout(() => {
			cb();
		}, 1000 );

	};

	// Set import batches.
	useEffect(() => {
		importBatches[ file.name ] = [];
	}, [file]);

	// Parse the file.
	useEffect(() => {

		let theParser;

		/**
		 * Parses a record.
		 *
		 * @param {Object} record The raw record.
		 * @returns {Object} The parsed record.
		 */
		const parseRecord = ( record ) => {
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
		};

		Papa.parse( file, {
			header: true,
			skipEmptyLines: 'greedy',
			chunks(results, parser) {

				// If this is the first step, store the parser.
				if ( ! theParser ) {
					theParser = parser;
				}

				if ( 0 === imported ) {
					setImported( imported + toBytes( results.meta.fields ) );
				}

				// Calculate the number of bytes of Object.values( results.data );
				const bytes = toBytes( Object.values( results.data ) );

				// Update the progress.
				setImported( imported + bytes );

				// Parse the record.
				const record = parseRecord( results.data );

				// Add to batch.
				importBatches[ file.name ].push( record );

				// If batch is 10+...
				if ( importBatches[ file.name ].length > 9 ) {

					// Pause the parser.
					parser.pause();

					// and import the batch.
					importBatch( () => {
						importBatches[ file.name ] = [];
						parser.resume();
					} );

				}
			},
			complete( results ) {
				console.log( results );
				importBatch( () => setDone( true ) );
			},
			error( error ) {
				setErrors( [ ...errors, error ] );
			},
		});

		return () => theParser && theParser.abort();
	}, [ file, headers ]);

	// Progress wrapper styles.
	const progressWrapperStyles = {
		width: '100%',
		height: '20px',
		background: '#eee',
		marginBottom: '20px',
	};

	// Progress inner styles.
	const progressInnerStyles = {
		width: imported ? `${ ( imported / file.size ) * 100 }%` : '0%',
		height: '100%',
		background: '#007cba',
	};

	return (
		<div className="noptin-import-progress">

			{ ! done && (
				<p>
					{ sprintf( __( 'Importing %s...', 'newsletter-optin-box' ), file.name ) }
				</p>
			) }

			{ done && (
				<p>
					{ sprintf( __( 'Done processing %s.', 'newsletter-optin-box' ), file.name ) }
				</p>
			) }

			{ errors.map(( error, index ) => (
				<Notice status="error" isDismissible={ false } key={ index }>{ error.message }</Notice>
			)) }

			{ ! done && (
				<div style={progressWrapperStyles}>
					<div style={progressInnerStyles}></div>
				</div>
			) }

			{ done && (
				<div className="noptin-import-summary">
					<p>
						{ sprintf( __( 'Imported %s records.', 'newsletter-optin-box' ), imported ) }
					</p>
					<p>
						{ sprintf( __( 'Updated %s records.', 'newsletter-optin-box' ), updated ) }
					</p>
					<p>
						{ sprintf( __( 'Failed to import %s records.', 'newsletter-optin-box' ), failed ) }
					</p>
				</div>
			) }

			{ ! done && (
				<div className="noptin-import-actions">
					<button className="button button-secondary" onClick={ back }>
						{ __( 'Back', 'newsletter-optin-box' ) }
					</button>
				</div>
			) }

		</div>
	);

}

export default Progress;
