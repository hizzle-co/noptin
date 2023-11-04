/**
 * External dependencies
 */
import { useState } from "@wordpress/element";
import { CardBody, Flex, FlexItem, Slot } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import styled from '@emotion/styled';
import { useParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import Wrap from "../wrap";
import { OverviewSection } from "./overview-section";
import { withSchema } from "../page";
import { useRecord } from "../../../store-data/hooks";
import { EditSchemaForm } from "../create-record";

/**
 * Displays an overview section.
 */
export const Section = styled( FlexItem )`
	width: 400px;
	max-width: 100%;
`

const Wrapper = ( { children, title, isInner } ) => {

	if ( isInner ) {
		return children;
	}

	return (
		<Wrap title={title}>
			<CardBody>
				{children}
			</CardBody>
		</Wrap>
	);
}

/**
 * Allows the user to edit a single record.
 *
 * @param {Object} props
 */
const EditRecordOverview = withSchema( ( { namespace, collection, id, schema, isInner, basePath } ) => {

	// Prepare the state.
	const [error, setError] = useState( null );
	const [saving, setSaving] = useState( false );
	const [edits, setEdits] = useState( {} );
	const record = useRecord( namespace, collection, id );

	// A function to save a record.
	const onSaveRecord = ( e ) => {

		e?.preventDefault();

		// Save once.
		if ( saving ) {
			return;
		}

		setSaving( true );

		record.save( edits )
			.then( () => {
				setEdits( {} );
			} )
			.catch( ( error ) => {
				setError( error );
			} )
			.finally( () => {
				setSaving( false );
			} );
	}

	// Sets edited attributes.
	const setAttributes = ( atts ) => {
		setEdits( { ...edits, ...atts } );

		if ( error ) {
			setError( null );
		}
	}

	const fillName = ( name ) => isInner ? `${name}--inner` : name;

	// Display the add record form.
	return (
		<Wrapper title={schema.labels?.edit_item || __( 'Edit Item', 'newsletter-optin-box' )} isInner={isInner}>

			<Flex align="flex-start" wrap>
				<Section>
					<EditSchemaForm
						record={{ ...record.data, ...edits }}
						error={error}
						onSubmit={onSaveRecord}
						submitText={saving ? __( 'Saving...', 'newsletter-optin-box' ) : __( 'Save Changes', 'newsletter-optin-box' )}
						onChange={setAttributes}
						namespace={namespace}
						collection={collection}
						loading={saving}
						isInner={isInner}
						slotName={`${namespace}_${collection}_record_overview_below`}
						{...schema}
					/>
				</Section>
				<Section>
					<Slot name={fillName( `${namespace}_${collection}_record_overview_upsell` )} />
					<OverviewSection
						namespace={namespace}
						collection={collection}
						id={id}
						basePath={basePath}
					/>
				</Section>
			</Flex>

		</Wrapper>
	);

} )

/**
 * Allows the user to edit a single record.
 *
 * @param {Object} props
 */
export const RecordOverview = () => {

	// Prepare the state.
	const { namespace, collection, id } = useParams();

	// Display the add record form.
	return <EditRecordOverview namespace={namespace} collection={collection} basePath="" id={id} />
}

/**
 * Allows the user to edit a single inner record.
 *
 * @param {Object} props
 */
export const InnerRecordOverview = () => {

	// Prepare the state.
	const { innerNamespace, innerCollection, innerId, id, tab } = useParams();
	const basePath = `${id}/${tab}`;

	return <EditRecordOverview namespace={innerNamespace} collection={innerCollection} id={innerId} basePath={basePath} isInner />

}
