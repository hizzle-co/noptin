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
import { useNavigateCollection } from "./hooks";
import { useSchema } from "../../store-data/hooks";
import { withSchema } from "./page";
import { Section } from "./view-record/overview";
import { BlockButton } from "../styled-components";
import { prepareField } from "./records-table/filters";

export const prepareEditableSchemaFields = ( schema, hidden, ignore ) => ( compact(
	schema.map( ( field ) => {

		// Abort for readonly and dynamic fields.
		if ( field.readonly || field.is_dynamic || 'metadata' === field.name ) {
			return null;
		}

		// Abort for hidden fields...
		if ( Array.isArray( hidden ) && hidden.includes( field.name ) ) {
			return null;
		}

		// ... and fields to ignore.
		if ( Array.isArray( ignore ) && ignore.includes( field.name ) ) {
			return null;
		}

		return prepareField( field );
	} )
) );

export const EditSchemaForm = ({record, onChange, schema, hidden, ignore, onSubmit, loading, children, isInner, slotName, submitText, error } ) => {

	// Prepare form fields.
	const fields = useMemo( () => prepareEditableSchemaFields( schema, hidden, ignore ), [schema, hidden, ignore] );

	return (
		<form style={{ opacity: loading ? 0.5 : 1 }} onSubmit={onSubmit}>

			{children}

			{fields.map( ( field ) => (
				<div style={{ marginBottom: '1.6rem' }} key={field.name}>
					<Setting
						settingKey={field.name}
						saved={record}
						setAttributes={onChange}
						setting={field}
					/>
				</div>
			) )}

			<Slot name={isInner ? `${slotName}--inner` : slotName}>
				{( fills ) => (
					fills.map( ( fill, index ) => (
						<Tip key={index}>{fill}</Tip>
					) )
				)}
			</Slot>

			<BlockButton variant="primary" onClick={onSubmit} isBusy={loading}>
				{submitText}
				{loading && <Spinner />}
			</BlockButton>

			{error && (
				<Notice status="error">
					{error.message}
				</Notice>
			)}

		</form>
	)
}

/**
 * Displays the record creation form.
 *
 */
const CreateRecordForm = withSchema( function CreateRecordForm( { namespace, collection, basePath = '', isInner = false, defaultProps = {}, schema: { schema, hidden, ignore } } ) {

	// Prepare the state.
	const dispatch = useDispatch( `${namespace}/${collection}` );
	const [error, setError] = useState( null );
	const [loading, setLoading] = useState( false );
	const [record, setRecord] = useState( {} );
	const navigateTo = useNavigateCollection();
	const newIgnore = [ ...ignore, ...Object.keys(defaultProps) ];

	// A function to create a new record.
	const handleSubmit = ( e ) => {

		e?.preventDefault();

		// Save once.
		if ( loading ) {
			return;
		}

		setLoading( true );

		dispatch.createRecord( { ...record, ...defaultProps }, dispatch )
			.then( ( savedRecord ) => {
				navigateTo( `${basePath}/${savedRecord.record.id}` );
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

	// Display the add record form.
	return (
		<EditSchemaForm
			record={record}
			onChange={onChange}
			onSubmit={handleSubmit}
			submitText={loading ? __( 'Saving...', 'newsletter-optin-box' ) : __( 'Save', 'newsletter-optin-box' )}
			schema={schema}
			hidden={hidden}
			ignore={newIgnore}
			loading={loading}
			isInner={isInner}
			slotName={`${namespace}_${collection}_record_create_below`}
			error={error}
		/>
	);

} );

/**
 * Allows the user to create new records.
 *
 */
export function CreateRecord() {

	// Prepare the state.
	const { namespace, collection } = useParams();
	const schema = useSchema( namespace, collection );

	// Display the add record form.
	return (
		<Wrap title={schema.data?.labels?.add_new_item || __( 'Add New Item', 'newsletter-optin-box' )}>
			<CardBody>
				<Flex align="flex-start" wrap>
					<Section>
						<CreateRecordForm
							namespace={namespace}
							collection={collection}
						/>
					</Section>

					<Slot name={`${namespace}_${collection}_record_create_upsell`}>
						{( fills ) => (
							fills.length > 0 && <Section>{fills}</Section>
						)}
					</Slot>
				</Flex>
			</CardBody>
		</Wrap>
	);

}

/**
 * Allows users to create new records in another collection.
 */
export function CreateInnerRecord() {

	// Prepare the state.
	const { namespace, collection, innerNamespace, innerCollection, id, tab } = useParams();

	const { data } = useSchema( namespace, collection );

	if ( ! tab || ! data ) {
		return false;
	}

	const currentTab = data.tabs[tab]; 

	return (
		<CreateRecordForm
			namespace={innerNamespace}
			collection={innerCollection}
			basePath={ `${id}/${tab}/${innerNamespace}/${innerCollection}` }
			defaultProps={{ [currentTab.filter_by]: id }}
			isInner
		>
			<Slot name={`${innerNamespace}_${innerCollection}_record_create_upsell--inner`} />
		</CreateRecordForm>
	);
}
