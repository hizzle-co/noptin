/**
 * External dependencies
 */
import { useDispatch } from "@wordpress/data";
import { useState, useCallback, useEffect } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";
import Papa from 'papaparse';
import { Spinner, Button, Flex, FlexItem, } from "@wordpress/components";

/**
 * Internal dependancies.
 */
import { useNavigateCollection } from "../hooks";
import StatCard from "../stat-card";
import { ErrorNotice, HeadingText, ProgressBar, BlockButton } from "../../styled-components";

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
	} );
}

/**
 * React component that animates from one number to another.
 *
 * @param {Object} props
 * @param {Number} props.from The value to animate from.
 * @param {Number} props.to The value to animate to.
 * @param {Number} props.duration The duration of the animation.
*/
const AnimatedNumber = ( { from = 0, to, duration = 5000 } ) => {

	// Prepare state.
	const [value, setValue] = useState( from );

	// Update the value.
	useEffect( () => {

		// Abort if the values are the same.
		if ( from === to ) {
			setValue( to );
			return;
		}

		let animationFrameId;
		const startTime = Date.now();

		const updateNumber = () => {
			const currentTime = Date.now();
			const elapsedTime = currentTime - startTime;

			if ( elapsedTime < duration ) {
				const newValue =
					from +
					( ( to - from ) * elapsedTime ) / duration;
				setValue( Math.round( newValue ) );

				// Request next frame
				animationFrameId = requestAnimationFrame( updateNumber );
			} else {
				setValue( to );
			}
		};

		// Start the animation
		animationFrameId = requestAnimationFrame( updateNumber );

		// Clean up the animation frame on component unmount
		return () => cancelAnimationFrame( animationFrameId );
	}, [from, to, duration] );

	return <span>{Math.round( value )}</span>;
}

/**
 * Imports the file and displays the progress.
 *
 * @param {Object} props
 * @param {Object} props.file The file to import.
 * @param {Object} props.headers Known fields to file headers mapping.
 * @param {Function} props.back The callback to call when clicking on the back button.
 */
const Progress = ( { file, headers, back, updateRecords, namespace, collection, basePath = '' } ) => {

	// Prepare route.
	const navigateHome = useNavigateCollection();

	// Processing errors.
	const [errors, setErrors] = useState( [] );

	// Whether we're done.
	const [done, setDone] = useState( false );
	const [lastDuration, setLastDuration] = useState( 5000 );

	// Paused.
	const [paused, setPaused] = useState( false );

	// Whether we've parsed the file.
	const [parsed, setParsed] = useState( false );

	// Total number of records.
	const [total, setTotal] = useState( 0 );

	// Number of processed records.
	const [processed, setProcessed] = useState( 0 );

	// Number of updated records.
	const [updatedRecords, setUpdatedRecords] = useState( 0 );
	const [previousUpdatedRecords, setPreviousUpdatedRecords] = useState( 0 );

	// Number of created records.
	const [createdRecords, setCreatedRecords] = useState( 0 );
	const [previousCreatedRecords, setPreviousCreatedRecords] = useState( 0 );

	// Number of failed records.
	const [failedRecords, setFailedRecords] = useState( 0 );
	const [previousFailedRecords, setPreviousFailedRecords] = useState( 0 );

	// Number of skipped records.
	const [skippedRecords, setSkippedRecords] = useState( 0 );
	const [previousSkippedRecords, setPreviousSkippedRecords] = useState( 0 );

	// The chunks to import.
	const [chunks, setChunks] = useState( [] );

	// Dispatch.
	const dispatch = useDispatch( `${namespace}/${collection}` );

	/**
	 * Parses a record.
	 *
	 * @param {Object} record The raw record.
	 * @returns {Object} The parsed record.
	 */
	const parseRecord = useCallback( ( record ) => {
		const parsed = {};

		Object.keys( headers ).forEach( ( key ) => {

			// Abort if the header is not mapped.
			if ( '' === headers[key].value ) {
				return;
			}

			// Are we mapping the field?
			if ( headers[key].mapped ) {
				parsed[key] = record[headers[key].value];
			} else if ( undefined !== headers[key].customValue ) {
				parsed[key] = headers[key].customValue;
			}

			// If the field is a boolean, convert it.
			if ( headers[key].is_boolean ) {
				parsed[key] = ['0', '', 'false', 'FALSE', 'no'].includes( parsed[key] ) ? false : true;
			}

		} );

		return parsed;
	}, [headers] );

	// Import chunks.
	useEffect( () => {

		// Abort if paused.
		if ( paused ) {
			return;
		}

		const startTime = new Date().getTime();
		const newChunks = [...chunks];

		// Get the next chunk.
		const chunk = newChunks.shift();

		// If there's no chunk, we're done.
		if ( !chunk ) {

			if ( false === done ) {
				setDone( true );
			}

			return;
		}

		if ( true === done ) {
			setDone( false );
		}

		// Import the chunk.
		const batchAction = {
			import: chunk,
			update: updateRecords,
		};

		dispatch.batchAction( batchAction, dispatch )
			.then( ( { result } ) => {

				let skipped = 0;
				let updated = 0;
				let created = 0;
				let failed = 0;
				let errors = [];

				if ( result?.import && result.import.length ) {

					result.import.forEach( ( record ) => {

						if ( record.data?.skipped ) {
							skipped++;
						}

						if ( record.data?.updated ) {
							updated++;
						}

						if ( record.data?.created ) {
							created++;
						}

						if ( record.is_error ) {
							failed++;
							errors.push( record.data );
						}
					} );
				}

				setPreviousSkippedRecords( skippedRecords );
				setSkippedRecords( skippedRecords + skipped );
				setPreviousUpdatedRecords( updatedRecords );
				setUpdatedRecords( updatedRecords + updated );
				setPreviousCreatedRecords( createdRecords );
				setCreatedRecords( createdRecords + created );
				setPreviousFailedRecords( failedRecords );
				setFailedRecords( failedRecords + failed );
				setErrors( [...errors, ...errors] );
			} ).catch( ( error ) => {
				newFailed = failedRecords + chunk.length;
				setFailedRecords( failedRecords + chunk.length );
				setErrors( [...errors, error] );
			} ).finally( () => {
				setProcessed( processed + chunk.length );

				// Check if we've processed everything.
				if ( newChunks.length === 0 ) {
					setChunks( newChunks );
					return;
				}

				// If import took less than 20 seconds, adjust the number of records per chunk,
				// so that we speed up the import.
				const endTime = new Date().getTime();
				const diff = ( endTime - startTime ) / 1000;

				setLastDuration( Math.ceil( diff * 1000 ) );

				if ( diff < 20 ) {
					const newChunkSize = Math.ceil( chunk.length / diff ) * 20;

					if ( newChunkSize < 100 ) {
						const newNewChunks = [];

						// Flatten the old chunks.
						const oldChunks = newChunks.flat();

						// Create new chunks.
						for ( let i = 0; i < oldChunks.length; i += newChunkSize ) {
							const records = oldChunks.slice( i, i + newChunkSize );
							newNewChunks.push( records );
						}

						// Set the new chunks.
						setChunks( newNewChunks );
						return;
					}
				}

				setChunks( newChunks );
			} );
	}, [chunks, paused] );

	// Parse the file.
	useEffect( () => {

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
				const newChunks = [];
				for ( let i = 0; i < results.data.length; i += batchSize ) {
					const records = results.data.slice( i, i + batchSize );
					newChunks.push( records.map( ( record ) => parseRecord( record ) ) );
				}

				// Set the chunks.
				setChunks( newChunks );

				// Start the import.
				setPaused( false );
			},
			( error ) => setErrors( [error] ),
		);
	}, [file, headers] );

	// Abort if the file is not parsed.
	if ( !parsed ) {
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
				{sprintf( __( 'No records found in %s.', 'newsletter-optin-box' ), file.name )}
			</ErrorNotice>
		)
	}

	return (
		<div className="noptin-import-progress">

			{!done && (
				<>
					<HeadingText as="h3">
						{__( 'Importing', 'newsletter-optin-box' )}
						<code>{file.name}</code>...
						&nbsp;
						{!paused && <Spinner />}

						&nbsp; <Button variant="link" onClick={() => setPaused( !paused )}>
							{paused ? __( 'Resume', 'newsletter-optin-box' ) : __( 'Pause', 'newsletter-optin-box' )}
						</Button>

					</HeadingText>

					{!paused && <ProgressBar total={total} processed={processed} />}
				</>
			)}

			{done && (
				<HeadingText as="h3">
					{__( 'Processed', 'newsletter-optin-box' )}
					<code>{file.name}</code>
				</HeadingText>
			)}

			<Flex justify="flex-start" style={{ margin: '1.6rem 0' }} gap={4} wrap>

				<FlexItem>
					<StatCard
						value={<AnimatedNumber to={total} duration={ 3000 } />}
						label={__( 'Records Found', 'newsletter-optin-box' )}
						status="light"
					/>
				</FlexItem>

				{createdRecords > 0 && (
					<FlexItem>
						<StatCard
							value={
								<AnimatedNumber
									from={previousCreatedRecords}
									to={createdRecords}
									duration={ ( done || paused ) ? 300 : lastDuration }
								/>
							}
							label={__( 'Records Created', 'newsletter-optin-box' )}
							status="success"
						/>
					</FlexItem>
				)}

				{updatedRecords > 0 && (
					<FlexItem>
						<StatCard
							value={
								<AnimatedNumber
									from={previousUpdatedRecords}
									to={updatedRecords}
									duration={ ( done || paused ) ? 300 : lastDuration }
								/>
							}
							label={__( 'Records Updated', 'newsletter-optin-box' )}
							status="success"
						/>
					</FlexItem>
				)}

				{failedRecords > 0 && (
					<FlexItem>
						<StatCard
							value={
								<AnimatedNumber
									from={previousFailedRecords}
									to={failedRecords}
									duration={ ( done || paused ) ? 300 : lastDuration }
								/>
							}
							label={__( 'Records Failed', 'newsletter-optin-box' )}
							status="error"
						/>
					</FlexItem>
				)}

				{skippedRecords > 0 && (
					<FlexItem>
						<StatCard
							value={
								<AnimatedNumber
									from={previousSkippedRecords}
									to={skippedRecords}
									duration={ ( done || paused ) ? 300 : lastDuration }
								/>
							}
							label={__( 'Records Skipped', 'newsletter-optin-box' )}
							status="info"
						/>
					</FlexItem>
				)}
			</Flex>

			{done && (
				<BlockButton
					variant="primary"
					text={__( 'View Records', 'newsletter-optin-box' )}
					onClick={() => navigateHome( basePath )}
					maxWidth="200px"
				/>
			)}

			{errors.length > 0 && (
				<HeadingText as="h3">
					{__( 'Errors', 'newsletter-optin-box' )}&nbsp;
					{done && (
						<Button onClick={() => setErrors( [] )} variant="link">
							{__( 'Clear', 'newsletter-optin-box' )}
						</Button>
					)}
				</HeadingText>
			)}

			{errors.map( ( error, index ) => <ErrorNotice key={index}>{error.message}</ErrorNotice> )}

		</div>
	);

}

export default Progress;
