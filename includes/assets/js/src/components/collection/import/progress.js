/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { useState, useCallback, useEffect } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";
import Papa from 'papaparse';
import { Spinner, Button, Flex, FlexItem, } from "@wordpress/components";

/**
 * Internal dependancies.
 */
import { useRoute } from "../hooks";
import StatCard from "../stat-card";
import { ErrorNotice, HeadingText, ProgressBar } from "../../styled-components";

/**
 * parses a CSV file.
 *
 * @param {Object} file The file to parse.
 * @param {Function} cb The callback to call when done.
 */
const parseCSV = ( file, onComplete, onError ) => {

	Papa.parse( file, {
		header: true,
		skipEmptyLines: 'greedy',
		complete( results ) {
			onComplete( results );
		},
		error( error ) {
			onError( error );
		},
	});
}

/**
 * List of chunks to import.
 */
const chunks = [];

/**
 * Imports chunks of records.
 */
const importChunks = ( props ) => {

	// Prepare args.
	const {
		path,
		addProcessed,
		addError,
		addSkipped,
		addUpdated,
		addCreated,
		addFailed,
		setDone,
		updateRecords,
	} = props;

	// Get the next chunk.
	const chunk = chunks.shift();

	// If there's no chunk, we're done.
	if ( ! chunk ) {
		return setDone( true );
	}

	// Import the chunk.
	apiFetch({
		path: `${path}/batch`,
		method: 'POST',
		data: {
			import: chunk,
			update: updateRecords,
		},
	}).then(( res ) => {

		if ( res.import && res.import.length ) {

			res.import.forEach(( record ) => {

				if ( record.skipped ) {
					addSkipped( 1 );
				}

				if ( record.updated ) {
					addUpdated( 1 );
				}

				if ( record.created ) {
					addCreated( 1 );
				}

				if ( record.is_error ) {
					addFailed( 1 );
					addError( record );
				}
			});
		}
	}).catch(( error ) => {
		addFailed( chunk.length );
		addError( error );
	}).finally(() => {
		addProcessed( chunk.length );
		importChunks( props );
	});

}

/**
 * Imports the file and displays the progress.
 *
 * @param {Object} props
 * @param {Object} props.file The file to import.
 * @param {Object} props.headers Known fields to file headers mapping.
 * @param {Function} props.back The callback to call when clicking on the back button.
 */
const Progress = ( { file, headers, back, updateRecords } ) => {

	// Prepare route.
	const { namespace, collection, navigate } = useRoute();

	// Processing errors.
	const [ errors, setErrors ] = useState( [] );

	// Whether we're done.
	const [ done, setDone ] = useState( false );

	// Whether we've parsed the file.
	const [ parsed, setParsed ] = useState( false );

	// Total number of records.
	const [ total, setTotal ] = useState( 0 );

	// Number of processed records.
	const [ processed, setProcessed ] = useState( 0 );

	// Number of updated records.
	const [ updatedRecords, setUpdatedRecords ] = useState( 0 );

	// Number of created records.
	const [ createdRecords, setCreatedRecords ] = useState( 0 );

	// Number of failed records.
	const [ failedRecords, setFailedRecords ] = useState( 0 );

	// Number of skipped records.
	const [ skippedRecords, setSkippedRecords ] = useState( 0 );

	/**
	 * Parses a record.
	 *
	 * @param {Object} record The raw record.
	 * @returns {Object} The parsed record.
	 */
	const parseRecord = useCallback( ( record ) => {
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
	}, [ headers ] );

	/**
	 * Adds a chunk of records to the list of chunks to import.
	 *
	 * @param {Array} records The records to process.
	 */
	const addChunk = useCallback( ( records ) => {

		// Parse the records.
		chunks.push( records.map(( record ) => parseRecord( record ) ) );
	}, [ parseRecord ] );

	// Parse the file.
	useEffect(() => {

		// Parse the file.
		parseCSV(
			file,
			( results ) => {

				// Set parsed flag.
				setParsed( true );

				// Set total.
				setTotal( results.data.length );

				// Create chunks of 10 records per chunk.
				const batchSize = 10;
				for ( let i = 0; i < results.data.length; i += batchSize ) {
					addChunk( results.data.slice( i, i + batchSize ) );
				}

				// Import the chunks.
				importChunks({
					path: `/${namespace}/${collection}`,
					addProcessed: ( increment ) => setProcessed( processed + increment ),
					addError: ( error ) => setErrors( [ ...errors, error ] ),
					addSkipped: ( increment ) => setSkippedRecords( skippedRecords + increment ),
					addUpdated: ( increment ) => setUpdatedRecords( updatedRecords + increment ),
					addCreated: ( increment ) => setCreatedRecords( createdRecords + increment ),
					addFailed: ( increment ) => setFailedRecords( failedRecords + increment ),
					setDone,
					updateRecords,
				});
			},
			( error ) => setErrors( [error] ),
		);
	}, [ file, headers ]);

	// Abort if the file is not parsed.
	if ( ! parsed ) {
		return (
			<HeadingText as="h3">
				{__( 'Parsing', 'newsletter-optin-box' )}
				<code>{file.name}</code>...
				&nbsp;
				<Spinner />
			</HeadingText>
		)
	}

	// Abort if total == 0.
	if ( 0 === total ) {
		return (
			<ErrorNotice>
				{ sprintf( __( 'No records found in %s.', 'newsletter-optin-box' ), file.name ) }
			</ErrorNotice>
		)
	}

	return (
		<div className="noptin-import-progress">

			{ ! done && (
				<>
					<HeadingText as="h3">
						{__( 'Importing', 'newsletter-optin-box' )}
							<code>{file.name}</code>...
							&nbsp;
						<Spinner />
					</HeadingText>

					<ProgressBar total={total} processed={processed} />
				</>
			) }

			{ done && (
				<HeadingText as="h3">
					{__( 'Processed', 'newsletter-optin-box' )}
						<code>{file.name}</code>
				</HeadingText>
			) }

			<Flex justify="flex-start" style={{ margin: '1.6rem 0' }} gap={ 4 } wrap>

				<FlexItem>
					<StatCard
						value={ total }
						label={ __( 'Records Found', 'newsletter-optin-box' ) }
						status="light"
					/>
				</FlexItem>

				{ createdRecords > 0 && (
					<FlexItem>
						<StatCard
							value={ createdRecords }
							label={ __( 'Records Created', 'newsletter-optin-box' ) }
							status="success"
						/>
					</FlexItem>
				)}

				{ updatedRecords > 0 && (
					<FlexItem>
						<StatCard
							value={ updatedRecords }
							label={ __( 'Records Updated', 'newsletter-optin-box' ) }
							status="success"
						/>
					</FlexItem>
				)}

				{ failedRecords > 0 && (
					<FlexItem>
						<StatCard
							value={ failedRecords }
							label={ __( 'Records Failed', 'newsletter-optin-box' ) }
							status="error"
						/>
					</FlexItem>
				)}

				{ skippedRecords > 0 && (
					<FlexItem>
						<StatCard
							value={ skippedRecords }
							label={ __( 'Records Skipped', 'newsletter-optin-box' ) }
							status="info"
						/>
					</FlexItem>
				)}
			</Flex>

			{ done && (
				<Button
					variant="primary"
					text={ __( 'View Records', 'newsletter-optin-box' ) }
					onClick={ () => navigate( `/${namespace}/${collection}` ) }
				/>
			) }

			{ errors.map(( error, index ) => <ErrorNotice key={ index }>{ error.message }</ErrorNotice>) }

		</div>
	);

}

export default Progress;
