/**
 * External dependencies
 */
import { useState, useMemo } from "@wordpress/element";
import { Notice, Spinner, CardBody, Tip, Flex, Slot } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useDispatch } from "@wordpress/data";
import { compact } from 'lodash';
import { useParams } from 'react-router-dom';

/**
 * Local dependencies.
 */
import Wrap from "./wrap";
import Setting from "../setting";
import { useNavigateCollection, useCurrentSchema } from "./hooks";
import { Section } from "./view-record/overview";
import { BlockButton } from "../styled-components";

/**
 * Allows the user to export all records.
 *
 */
export default function CreateRecord() {

	// Prepare the state.
	const navigateTo = useNavigateCollection();
	const { namespace, collection } = useParams();
	const dispatch                = useDispatch( `${namespace}/${collection}` );
	const [ error, setError ]     = useState( null );
	const [ loading, setLoading ] = useState( false );
	const [ record, setRecord ]   = useState( {} );
	const schema                  = useCurrentSchema();

	// A function to create a new record.
	const onCreateRecord = ( e ) => {

		e?.preventDefault();

		// Save once.
		if ( loading ) {
			return;
		}

		setLoading ( true );

		dispatch.createRecord( record, dispatch )
			.then( ( savedRecord ) => {
				navigateTo( savedRecord?.record?.id || '' );
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
		<Wrap title={schema.data.labels?.new_item}>
			<CardBody style={{ opacity: loading ? 0.5 : 1 }}>
				<Flex align="flex-start" wrap>
					<Section>
						<form onSubmit={ onCreateRecord }>
							{ fields.map( ( field ) => (
								<div style={{ marginBottom: '1.6rem' }} key={ field.name }>
									<Setting
										settingKey={ field.name }
										saved={ record }
										setAttributes={ onChange }
										setting={ field }
									/>
								</div>
							) ) }

							<Slot name={`${namespace}_${collection}_record_create_below`}>
								{ ( fills ) => (
									fills.map( ( fill, index ) => (
										<Tip key={ index }>{ fill }</Tip>
									) )
								)}
							</Slot>

							<BlockButton variant="primary" onClick={ onCreateRecord } isBusy={ loading }>
								{ loading ? __( 'Saving...', 'newsletter-optin-box' ) : __( 'Save', 'newsletter-optin-box' ) }
								{ loading && <Spinner /> }
							</BlockButton>

							{ error && (
								<Notice status="error">
									{ error.message }
								</Notice>
							) }

						</form>
					</Section>

					<Slot name={`${namespace}_${collection}_record_create_upsell`}>
						{ ( fills ) => (
							fills.length > 0 && <Section>{fills}</Section>
						)}
					</Slot>
				</Flex>
			</CardBody>
		</Wrap>
	);

}
