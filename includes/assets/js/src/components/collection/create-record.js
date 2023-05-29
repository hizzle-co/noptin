/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { useState } from "@wordpress/element";
import { Notice, Spinner, CardBody, CardFooter, Button } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useAtomValue } from "jotai";

/**
 * Local dependencies.
 */
import { schema, collection, namespace } from "./store";
import Wrap from "./wrap";
import Setting from "../setting";

/**
 * Allows the user to export all records.
 *
 * @param {Object} props
 * @param {Object} props.component
 */
export default function CreateRecord( { component: { title } } ) {

	// Prepare the state.
	const [ error, setError ]     = useState( null );
	const [ loading, setLoading ] = useState( false );
	const [ record, setRecord ]   = useState( {} );

	// Prepare the store.
	const currentCollection = useAtomValue( collection );
	const currentNamespace  = useAtomValue( namespace );
	const currentSchema	    = useAtomValue( schema );

	// Show error if any.
	if ( currentSchema.state === 'hasError' ) {

		return (
			<Wrap title={ title }>
				<CardBody>
					<Notice status="error" isDismissible={ false }>
						<strong>{ __( 'Error:', 'newsletter-optin-box' ) }</strong>&nbsp;
						{ currentSchema.error.message }
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	// Show the loading indicator if we're loading the schema.
	if ( currentSchema.state === 'loading' ) {

		return (
			<Wrap title={ title }>
				<CardBody>
					<Spinner />
				</CardBody>
			</Wrap>
		);
	}

	// Saves the record.
	const saveRecord = () => {

		// Abort if we're already loading.
		if ( loading ) {
			return;
		}

		setLoading( true );
		setError( null );

		apiFetch( {
			path: `${currentNamespace}/v1/${currentCollection}`,
			method: 'POST',
			data: record,
		} ).then( ( {id} ) => {
			// TODO: Switch route to /edit/:id
		} ).catch( ( error ) => {
			setError( error );
		} ).finally( () => {
			setLoading( false );
		} );
	};

	// Display the add record form.
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

				{ currentSchema.data.schema.map( ( field ) => {

					// Abort for readonly and dynamic fields.
					if ( field.readonly || field.is_dynamic ) {
						return null;
					}

					// Abort for hidden fields.
					if ( currentSchema.data.hidden && currentSchema.data.hidden.includes( field.name ) ) {
						return null;
					}

					// Fields to ignore.
					if ( currentSchema.data.ignore && currentSchema.data.ignore.includes( field.name ) ) {
						return null;
					}

					const preparedSetting = {
						default: field.default,
						label: field.label,
						el: 'input',
						type: 'text',
					};

					if ( field.enum && ! Array.isArray( field.enum ) ) {
						preparedSetting.el = 'select';
						preparedSetting.options = field.enum;
					}

					if ( field.isLongText ) {
						preparedSetting.el = 'textarea';
					}

					if ( field.is_numeric || field.is_float ) {
						preparedSetting.type = 'number';
					}

					if ( field.is_boolean ) {
						preparedSetting.type = 'toggle';
					}

					if ( field.description && field.description !== field.label ) {
						preparedSetting.description = field.description;
					}

					return (
						<div style={ { marginBottom: '1.6rem' } } key={ field.name }>
							<Setting
								settingKey={ field.name }
								saved={ record }
								setAttributes={ ( newAttributes) => setRecord( { ...record, ...newAttributes } ) }
								setting={ preparedSetting }
							/>
						</div>
					);
				} ) }

				{ error && (
					<Notice status="error">
						{ error.message }
					</Notice>
				) }
			</CardBody>

			<CardFooter>
				<Button
					variant="primary"
					onClick={ saveRecord }
					isBusy={ loading }
					disabled={ loading }
				>
					{ __( 'Save', 'newsletter-optin-box' ) }
					{ loading && <Spinner /> }
				</Button>
			</CardFooter>
		</Wrap>
	);

}
