/**
 * External dependencies
 */
import { useState, useMemo } from "@wordpress/element";
import { Notice, Spinner, CardBody, CardFooter, Button } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useDispatch } from "@wordpress/data";
import { compact } from 'lodash';

/**
 * Local dependencies.
 */
import Wrap from "./wrap";
import Setting from "../setting";
import { useSchema } from "../../store-data/hooks";
import { useRoute } from "./hooks";

/**
 * Allows the user to export all records.
 *
 * @param {Object} props
 * @param {Object} props.component
 */
export default function CreateRecord( { component: { title } } ) {

	// Prepare the state.
	const { namespace, collection, navigate } = useRoute();
	const STORE_NAME              = `${namespace}/${collection}`;
	const dispatch                = useDispatch( STORE_NAME );
	const [ error, setError ]     = useState( null );
	const [ loading, setLoading ] = useState( false );
	const [ record, setRecord ]   = useState( {} );
	const schema                  = useSchema( namespace, collection );

	// A function to create a new record.
	const onCreateRecord = () => {

		// Save once.
		if ( loading ) {
			return;
		}

		setLoading ( true );

		dispatch.createRecord( record, dispatch )
			.then( ( savedRecord ) => {
				navigate( `/${STORE_NAME}/update`, { id: savedRecord?.result?.id } );
			} )
			.catch( ( error ) => {
				setError( error );
			} )
			.finally( () => {
				setLoading( false );
			} );
	}

	// onChange handler.
	const onChange = ( newProps ) => {
		setRecord( { ...record, ...newProps } );

		if ( error ) {
			setError( null );
		}
	}

	// Prepare form fields.
	const fields = useMemo( () => ( compact(
		schema.data.schema.map( ( field ) => {

			// Abort for readonly and dynamic fields.
			if ( field.readonly || field.is_dynamic ) {
				return null;
			}

			// Abort for hidden fields...
			if ( schema.data.hidden && schema.data.hidden.includes( field.name ) ) {
				return null;
			}

			// ... and fields to ignore.
			if ( schema.data.ignore && schema.data.ignore.includes( field.name ) ) {
				return null;
			}

			const preparedSetting = {
				default: field.default,
				label: field.label,
				el: 'input',
				type: 'text',
				name: field.name,
				isInputToChange: true,
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

			return preparedSetting;
		} )
	) ), [ schema.data ] );

	// Display the add record form.
	return (
		<Wrap title={title}>
			<form onSubmit={ onCreateRecord }>
				<CardBody style={{ opacity: loading ? 0.5 : 1 }}>

					{ fields.map( ( field ) => (
						<div style={ { marginBottom: '1.6rem' } } key={ field.name }>
							<Setting
								settingKey={ field.name }
								saved={ record }
								setAttributes={ onChange }
								setting={ field }
							/>
						</div>
					) ) }

					{ error && (
						<Notice status="error">
							{ error.message }
						</Notice>
					) }
				</CardBody>

				<CardFooter>
					<Button
						variant="primary"
						onClick={ onCreateRecord }
						isBusy={ loading }
						disabled={ loading }
					>
						{ __( 'Save', 'newsletter-optin-box' ) }
						{ loading && <Spinner /> }
					</Button>
				</CardFooter>
			</form>
		</Wrap>
	);

}
