/**
 * External dependencies
 */
import { forwardRef, useState } from "@wordpress/element";
import { Notice, Spinner, CardBody, Flex, FlexItem, NavigableMenu, Button,
	Card,
	CardHeader,
	__experimentalText as Text,
	TabPanel,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";

/**
 * Local dependencies.
 */
import Wrap from "../wrap";
import { useRecord, useSchema } from "../../../store-data/hooks";
import { useRoute } from "../hooks";
import RecordOverview from "./overview";
import TableTab from "./table-tab";

/**
 * Displays a given tab.
 *
 * @param {Object} props
 * @param {Object} props.tab
 */
const RenderTab = ( { tab } ) => {

	if ( 'table' === tab.type ) {
		return <TableTab tab={ tab } />;
	}

	return (
		<Wrap title={ tab.title }>
			<p>Tab content</p>
		</Wrap>
    );
}

/**
 * Allows the user to edit a single record.
 *
 * @param {Object} props
 * @param {Object} props.component
 */
const UpdateRecord = ( { component: { title } }, ref ) => {

	// Prepare the state.
	const { namespace, collection, args } = useRoute();
	const schema = useSchema( namespace, collection );
	const record = useRecord( namespace, collection, args.id );

	// Show the loading indicator if we're loading the schema.
	if ( record.isResolving() ) {

		return (
			<Wrap title={ __( 'Loading', 'newsletter-optin-box' ) } ref={ ref }>
				<CardBody>
					<Spinner />
				</CardBody>
			</Wrap>
		);
	}

	// Show error if any.
	if ( record.hasResolutionFailed() ) {
		const error = record.getResolutionError();

		return (
			<Wrap title={ __( 'Error', 'newsletter-optin-box' ) } ref={ ref }>
				<CardBody>
					<Notice status="error" isDismissible={ false }>
						{ error.message || __( 'An unknown error occurred.', 'newsletter-optin-box' ) }
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	// Prepare the tabs.
	const tabs = [
		{
			title: __( 'Update Details', 'newsletter-optin-box' ),
			name: 'edit',
		}
	]

	// Displays a normal header if there are no tabs.
	if ( ! Array.isArray( schema.data.tabs ) && tabs ) {
		Object.keys( schema.data.tabs ).map( ( tab ) => (
			tabs.push( {
				...schema.data.tabs[tab],
				name: tab,
			} )
		) );
	}

	// Display the update record screen.
	return (
		<div ref={ ref }>

			{ tabs.length === 1 ? (
				<Wrap title={ tabs[0].title }>
					<RenderTab tab={ tabs[0] } />
				</Wrap>
			) : (
				<TabPanel className="hizzle-record-tabs" tabs={ tabs }>
					{ ( tab ) => <RenderTab tab={ tab } /> }
				</TabPanel>
			) }

		</div>
	);
}

export default forwardRef( UpdateRecord );

// Upsell tags and lists, 
// Edit contact details,
// Overview -> Cards for sent emails, opens, clicks, total spent, avatar, basic info, lists, tags
// Buttons to delete, save changes, and send email
