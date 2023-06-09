/**
 * External dependencies
 */
import { forwardRef, useState } from "@wordpress/element";
import { Spinner, CardBody, Tip, Flex, FlexItem } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import styled from '@emotion/styled';

/**
 * Internal dependencies
 */
import { useRecord, useSchema } from "../../../store-data/hooks";
import { useRoute, useCurrentPath } from "../hooks";
import Wrap from "../wrap";
import EditForm from "./edit-form";
import OverviewSection from "./overview-section";
import { BlockButton } from "../../styled-components";
import UpsellCard from "../../upsell-card";

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
 * @param {Object} props.tab
 * @param {String} props.tab.title
 */
const RecordOverview = ( { tab: {title} }, ref ) => {

	// Prepare the state.
	const path = useCurrentPath();
	const { namespace, collection, args } = useRoute();

	const [ error, setError ]   = useState( null );
	const [ saving, setSaving ] = useState( false );
	const [ edits, setEdits ]   = useState( {} );
	const schema                = useSchema( namespace, collection );
	const record                = useRecord( namespace, collection, args.id );

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

	// Record actions.
	const actions = (
		<BlockButton variant="primary" onClick={ onSaveRecord } isBusy={ saving }>
			{ saving ? __( 'Saving...', 'newsletter-optin-box' ) : __( 'Save Changes', 'newsletter-optin-box' ) }
			{ saving && <Spinner /> }
		</BlockButton>
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
				<Flex align="flex-start" wrap>
					<Section>
						<EditForm
							schema={ schema.data }
							record={{ ...record.record, ...edits }}
							error={ error }
							onSaveRecord={ onSaveRecord }
							setAttributes={ setAttributes }
						/>
						{ actions }

						{ path.schema?.tip && (
							<Tip>
								<span dangerouslySetInnerHTML={{ __html: path.schema.tip }} />
							</Tip>
						) }

					</Section>
					<Section>
						<OverviewSection
							namespace={ namespace }
							collection={ collection }
							recordID={ args.id }
							upsell={ <UpsellCard upsell={path.schema?.upsell} /> }
						/>
					</Section>
				</Flex>
			</CardBody>

		</Wrap>
	);

}

export default forwardRef( RecordOverview );
