/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { useState, useEffect, useMemo } from "@wordpress/element";
import { Notice, TextControl, ToggleControl, CardBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

/**
 * Local dependencies.
 */
import {getSchema} from "./get-schema";
import Wrap from "./wrap";

/**
 * Allows the user to export all records.
 *
 * @param {Object} props
 * @param {string} props.namespace
 * @param {string} props.collection
 */
export default function CreateRecord( { namespace, collection, title } ) {

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

						<h3>
							{ __( 'Select the fields to export', 'noptin' ) }
						</h3>

						{ schema.map( ( field ) => (
							<ToggleControl
								key={field.name}
								label={field.label === field.description ? field.label : `${field.label} (${field.description})`}
								checked={ toExport.includes( field.name ) }
								onChange={ () => {
									if ( toExport.includes( field.name ) ) {
										setToExport( toExport.filter( ( name ) => name !== field.name ) );
									} else {
										setToExport( [ ...toExport, field.name ] );
									}
								} }
							/>
						) ) }
					</div>
				) }
			</CardBody>
		</Wrap>
	);

}
