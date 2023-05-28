/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { useState, useEffect, useMemo } from "@wordpress/element";
import { Notice, FormFileUpload, Tip, TextControl, ToggleControl, Placeholder, BaseControl, Flex, FlexItem, CardBody, Button } from "@wordpress/components";
import { Icon, upload } from "@wordpress/icons";
import { __, sprintf } from "@wordpress/i18n";
import papaparse from "papaparse";

/**
 * Local dependencies.
 */
import {getSchema} from "./get-schema";
import Wrap from "./wrap";

/**
 * Displays the file input.
 *
 * @param {Object} props
 */
function FileInput( { onUpload } ) {

	return (
		<>
			<BaseControl
				label={ __( 'This tool allows you to import existing records from a CSV file.', 'newsletter-optin-box' ) }
				help={ __( 'The first row of the CSV file should contain the field names.', 'newsletter-optin-box' ) }
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
 * @param {string} props.namespace
 * @param {string} props.collection
 */
export default function Import( { namespace, collection, title } ) {

	// Fetch the schema.
	const [ schema, setSchema ] = useState( [] );
	const [ error, setError ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ toExport, setToExport ] = useState( [] );

	// Calculates the fields to export.
	const calculateToExport = ( schema ) => {
		const toExport = [];

		schema.map( ( field ) => {
			if ( ! field.is_dynamic ) {
				toExport.push( field.name );
			}
		});

		setToExport( toExport );
	}

	// Fetch the schema.
	useEffect( () => {
		getSchema( namespace, collection )
			.then( ( { schema } ) => {
				setSchema( schema );
				calculateToExport( schema );
			} )
			.catch( ( error ) => {
				setError( error );
			} )
			.finally( () => {
				setLoading( false );
			});
	}, [namespace, collection] );

	return (
		<Wrap title={title}>

			<CardBody>
				{ loading && (
					<Notice status="info">
						{ __( 'Loading...', 'noptin' ) }
					</Notice>
				) }

				{ error && (
					<Notice status="error">
						{ error.message }
					</Notice>
				) }

				{ ! loading && ! error && (
					<div>
						<FileInput onUpload={ ( file ) => { console.log( file ) } } />
					</div>
				) }
			</CardBody>
		</Wrap>
	);

}
