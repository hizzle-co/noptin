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
import { RecordOverview, InnerRecordOverview } from "./overview";
import TableTab from "./table-tab";
import { StyledNavigableMenu } from "../../styled-components";
import { useNavigateCollection } from "../hooks";
import { MiniRecordsTable } from "../records-table";
import { useRecord } from "../../../store-data/hooks";
import { withSchema } from "../page";

/**
 * Displays a given tab.
 *
 */
const RenderTabContent = withSchema( function RenderTabContent( { tab, namespace, collection, recordId, schema, isInner } ) {

	// Prepare the state.
	const record = useRecord( namespace, collection, recordId );
	const args = {
		namespace,
		collection,
		recordId,
		tabName: tab,
	};

	if ( !Array.isArray( schema.tabs ) && schema.tabs[tab] ) {
		const currentTab = schema.tabs[tab];

		// Tables.
		if ( 'table' === currentTab.type ) {
			return <TableTab tab={currentTab} record={record} {...args} />;
		}

		// Sub collections.
		if ( 'collection' === currentTab.type ) {
			return <MiniRecordsTable {...currentTab} defaultProps={{ [currentTab.filter_by]: recordId }} />;
		}

		// Allow rendering of custom tabs.
		return (
			<Slot
				name={`${namespace}-${collection}-tab-${tab}${isInner ? '--inner' : ''}`}
				fillProps={{ tab: currentTab, record: record.data }}
			/>
		);
	}

	if ( isInner ) {
		return <InnerRecordOverview />;
	}

	return <RecordOverview />;
} )

/**
 * Displays a given tab.
 *
 */
export const RenderTab = () => {

	// Prepare the state.
	const { tab, namespace, collection, id } = useParams();

	return (
		<div className={`${namespace}-${collection}__${tab || 'overview'}-content`}>
			<RenderTabContent
				namespace={namespace}
				collection={collection}
				tab={tab}
				recordId={id}
			/>
			<Outlet />
		</div>
	);
}

/**
 * Displays a given inner tab.
 *
 */
export const RenderInnerTab = () => {

	// Prepare the state.
	const { innerNamespace, innerCollection, innerId, innerTab } = useParams();

	return (
		<div className={`${namespace}-${collection}__${tab || 'overview'}-content`}>
			<RenderTabContent
				namespace={innerNamespace}
				collection={innerCollection}
				tab={innerTab}
				recordId={innerId}
				isInner
			/>
		</div>
	);
}

/**
 * Allows the user to view a single inner record.
 *
 */
const InnerRecordContent = withSchema( function InnerRecordContent( { children, recordId, namespace, collection, schema, tab, basePath } ) {

	// Prepare the state.
	const record = useRecord( namespace, collection, recordId );
	const navigateTo = useNavigateCollection();

	// Prepare the tabs.
	const tabs = [
		{
			title: schema?.labels?.edit_item || __( 'Edit Item', 'newsletter-optin-box' ),
			name: 'overview',
		}
	]

	// Displays a normal header if there are no tabs.
	if ( !Array.isArray( schema?.tabs ) ) {
		Object.keys( schema.tabs ).map( ( tab ) => (
			tabs.push( {
				title: schema.tabs[tab].title,
				name: tab,
			} )
		) );
	}

	// Fired when a tab is selected.
	const onTabSelect = useCallback( ( tabIndex ) => {
		const newTab = tabs[tabIndex]?.name || 'overview';
		navigateTo( `${basePath}/${newTab}` );
	}, [basePath] );

	// Show the loading indicator if we're loading the record.
	if ( record.isResolving ) {

		return (
			<Wrap title={schema.labels?.edit_item || __( 'Edit Item', 'newsletter-optin-box' )}>
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
			<Wrap title={__( 'Error', 'newsletter-optin-box' )}>
				<CardBody>
					<Notice status="error" isDismissible={false}>
						{record.error?.message || __( 'An unknown error occurred.', 'newsletter-optin-box' )}
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	// Display the update record screen.
	return (
		<>

			{tabs.length > 1 && (
				<StyledNavigableMenu orientation="horizontal" onNavigate={onTabSelect}>
					{tabs.map( ( recordTab, index ) => (
						<Button
							key={recordTab.name}
							isPressed={recordTab.name === tab || ( !tab && 0 === index )}
							onClick={() => onTabSelect( index )}
						>
							{recordTab.title}
						</Button>
					) )}
				</StyledNavigableMenu>
			)}

			{children}
		</>
	);
} );

/**
 * Allows the user to view a single record.
 *
 */
export const ViewRecord = () => {

	// Prepare the state.
	const { namespace, collection, id, tab } = useParams();

	// Display the update record screen.
	return (
		<InnerRecordContent
			namespace={namespace}
			collection={collection}
			recordId={id}
			tab={tab}
			basePath={ id }
		>
			<Outlet />
		</InnerRecordContent>
	);
}

/**
 * Allows the user to view a single inner record.
 *
 */
export const ViewInnerRecord = () => {

	// Prepare the state.
	const { innerNamespace, innerCollection, innerTab, innerId, id, tab } = useParams();

	return (
		<InnerRecordContent
			namespace={innerNamespace}
			collection={innerCollection}
			recordId={innerId}
			tab={innerTab}
			basePath={ `${id}/${tab}/${innerNamespace}/${innerCollection}/${innerId}` }
		>
			<Outlet />
		</InnerRecordContent>
	);
}
