/**
 * External dependencies
 */
import { forwardRef, useState } from "@wordpress/element";
import { Spinner, CardBody, Button, Flex, FlexItem } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import styled from '@emotion/styled';

/**
 * Internal dependencies
 */
import { useRecord, useSchema } from "../../../store-data/hooks";
import { useRoute } from "../hooks";
import Wrap from "../wrap";
import EditForm from "./edit-form";
import OverviewSection from "./overview-section";

/**
 * Displays an overview section.
 */
const Section = styled( FlexItem )`
	width: 400px;
	max-width: 100%;
`

/**
 * Allows the user to edit a single record.
 *
 * @param {Object} props
 * @param {Object} props.tab
 * @param {String} props.tab.title
 */
const RecordOverview = ( { tab: {title} }, ref ) => {

	// Prepare the state.
	const { namespace, collection, navigate, args } = useRoute();

	const STORE_NAME                = `${namespace}/${collection}`;
	const [ error, setError ]       = useState( null );
	const [ saving, setSaving ]     = useState( false );
	const [ edits, setEdits ]       = useState( {} );
	const schema                    = useSchema( namespace, collection );
	const record                    = useRecord( namespace, collection, args.id );

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

	// A function to delete a record.
	const onDeleteRecord = () => {

		// Confirm.
		if ( ! confirm( __( 'Are you sure you want to delete this record?', 'newsletter-optin-box' ) ) ) {
			return;
		}

		// Delete the record.
		record.delete();

		// Navigate back to the list.
		navigate( STORE_NAME );
	}

	// Record actions.
	const actions = (
		<Flex gap={2} justify="start" wrap>
			{ Object.keys( edits ).length > 0 && (
				<FlexItem>
					<Button variant="primary" onClick={ onSaveRecord } isBusy={ saving }>
						{ saving ? __( 'Saving...', 'newsletter-optin-box' ) : __( 'Save', 'newsletter-optin-box' ) }
						{ saving && <Spinner /> }
					</Button>
				</FlexItem>
			) }
			<FlexItem>
				<Button variant="secondary" onClick={ onDeleteRecord } isDestructive>
					{ __( 'Delete', 'newsletter-optin-box' ) }
				</Button>
			</FlexItem>
		</Flex>
	);

	// Sets edited attributes.
	const setAttributes = ( atts ) => {
		setEdits( { ...edits, ...atts } );

		if ( error ) {
			setError( null );
		}
	}

	// Display the add record form.
	return (
		<Wrap title={title} ref={ ref }>

			<CardBody>
				<Flex align="start" wrap>
					<Section>
						<EditForm
							schema={ schema.data }
							record={{ ...record.record, ...edits }}
							error={ error }
							onSaveRecord={ onSaveRecord }
							setAttributes={ setAttributes }
						/>
						{ actions }
					</Section>
					<Section>
						<OverviewSection namespace={ namespace } collection={ collection } recordID={ args.id } />
					</Section>
				</Flex>
			</CardBody>

		</Wrap>
	);

}

export default forwardRef( RecordOverview );
