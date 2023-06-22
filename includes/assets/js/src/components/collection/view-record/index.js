/**
 * External dependencies
 */
import { useCallback } from "@wordpress/element";
import { Notice, Spinner, CardBody, Button, Slot } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useParams, Outlet } from 'react-router-dom';

/**
 * Local dependencies.
 */
import Wrap from "../wrap";
import { RecordOverview } from "./overview";
import TableTab from "./table-tab";
import { StyledNavigableMenu } from "../../styled-components";
import { useCurrentSchema, useCurrentRecord, useNavigateCollection } from "../hooks";

/**
 * Displays a given tab.
 *
 */
export const RenderTab = () => {

	// Prepare the state.
	const { data }    = useCurrentSchema();
	const record      = useCurrentRecord();
	const { tab, namespace, collection } = useParams();

	if ( ! Array.isArray( data.tabs ) && data.tabs[tab] ) {
		const currentTab = data.tabs[tab];

		// Tables.
		if ( 'table' === currentTab.type ) {
			return <TableTab tab={ currentTab } tabName={ tab } />;
		}

		// Allow rendering of custom tabs.
		return (
			<Slot
				name={ `${namespace}-${collection}-tab-${tab}` }
				fillProps={ { tab: currentTab, record: record.data } }
			/>
		);
	}

	return <RecordOverview />;
}

/**
 * Allows the user to view a single record.
 *
 */
export const ViewRecord = () => {

	// Prepare the state.
	const { data }    = useCurrentSchema();
	const record      = useCurrentRecord();
	const { id, tab } = useParams();
	const navigateTo  = useNavigateCollection();

	// Prepare the tabs.
	const tabs = [
		{
			title: data.labels?.edit_item || __( 'Edit Item', 'newsletter-optin-box' ),
			name: 'overview',
		}
	]

	// Displays a normal header if there are no tabs.
	if ( ! Array.isArray( data.tabs ) ) {
		Object.keys( data.tabs ).map( ( tab ) => (
			tabs.push( {
				title: data.tabs[tab].title,
				name: tab,
			} )
		) );
	}

	// Fired when a tab is selected.
	const onTabSelect = useCallback( ( tabIndex ) => {
		const newTab = tabs[ tabIndex ]?.name || 'overview';
		navigateTo( `${id}/${newTab}` );
	}, [id] );

	// Show the loading indicator if we're loading the record.
	if ( record.isResolving ) {

		return (
			<Wrap title={ data.labels?.edit_item || __( 'Edit Item', 'newsletter-optin-box' ) }>
				<CardBody>
					{__( 'Loading', 'newsletter-optin-box' )}
					<Spinner />
				</CardBody>
			</Wrap>
		);
	}

	// Show error if any.
	if ( 'ERROR' === record.status ) {

		return (
			<Wrap title={ __( 'Error', 'newsletter-optin-box' ) } ref={ ref }>
				<CardBody>
					<Notice status="error" isDismissible={ false }>
						{ record.error?.message || __( 'An unknown error occurred.', 'newsletter-optin-box' ) }
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	// Display the update record screen.
	return (
		<>

			{ tabs.length > 1 && (
				<StyledNavigableMenu orientation="horizontal" onNavigate={ onTabSelect }>
					{ tabs.map( ( recordTab, index ) => (
						<Button
							key={ recordTab.name }
							isPressed={ recordTab.name === tab || ( ! tab && 0 === index ) }
							onClick={ () => onTabSelect( index ) }
						>
							{ recordTab.title }
						</Button>
					) ) }
				</StyledNavigableMenu>
			)}

			<Outlet />
		</>
	);
}
