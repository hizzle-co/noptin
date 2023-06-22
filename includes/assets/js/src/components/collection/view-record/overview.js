/**
 * External dependencies
 */
import { useState } from "@wordpress/element";
import { Spinner, CardBody, Tip, Flex, FlexItem, Slot } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import styled from '@emotion/styled';
import { useParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { useCurrentSchema, useCurrentRecord } from "../hooks";
import Wrap from "../wrap";
import { EditForm } from "./edit-form";
import { OverviewSection } from "./overview-section";
import { BlockButton } from "../../styled-components";

/**
 * Displays an overview section.
 */
export const Section = styled( FlexItem )`
	width: 400px;
	max-width: 100%;
`

/**
 * Allows the user to edit a single record.
 *
 * @param {Object} props
 */
export const RecordOverview = () => {

	// Prepare the state.
	const { namespace, collection, id } = useParams();

	const [ error, setError ]   = useState( null );
	const [ saving, setSaving ] = useState( false );
	const [ edits, setEdits ]   = useState( {} );
	const { data }              = useCurrentSchema();
	const record                = useCurrentRecord();

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

	// Display the add record form.
	return (
		<Wrap title={ data.labels?.edit_item || __( 'Edit Item', 'newsletter-optin-box' ) }>

			<CardBody>
				<Flex align="flex-start" wrap>
					<Section>
						<EditForm
							record={{ ...record.data, ...edits }}
							error={ error }
							onSaveRecord={ onSaveRecord }
							setAttributes={ setAttributes }
						/>

						<BlockButton variant="primary" onClick={ onSaveRecord } isBusy={ saving }>
							{ saving ? __( 'Saving...', 'newsletter-optin-box' ) : __( 'Save Changes', 'newsletter-optin-box' ) }
							{ saving && <Spinner /> }
						</BlockButton>

						<Slot name={`${namespace}_${collection}_record_overview_below`}>
							{ ( fills ) => (
								fills.map( ( fill, index ) => (
									<Tip key={ index }>{ fill }</Tip>
								) )
							)}
						</Slot>

					</Section>
					<Section>
						<Slot name={`${namespace}_${collection}_record_overview_upsell`} />
						<OverviewSection
							namespace={ namespace }
							collection={ collection }
							recordID={ id }
						/>
					</Section>
				</Flex>
			</CardBody>

		</Wrap>
	);

}
