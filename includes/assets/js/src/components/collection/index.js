/**
 * External dependencies
 */
import { SlotFillProvider } from "@wordpress/components";

import {
	unstable_HistoryRouter as HistoryRouter,
	Route,
	Routes,
} from 'react-router-dom';

/**
 * Internal dependencies.
 */
import ErrorBoundary from "./error-boundary";
import { getHistory } from "../navigation";
import { Page, InnerPage } from "./page";
import { ViewRecord, ViewInnerRecord, RenderTab, RenderInnerTab } from './view-record';
import { RecordOverview, InnerRecordOverview } from "./view-record/overview";
import RecordsTable from './records-table';
import { CreateRecord, CreateInnerRecord } from './create-record';
import Import from './import';
import { SelectedContextProvider } from "../table/selected-context";

/**
 * Displays the entire app.
 *
 * @param {Object} props
 * @param {string} props.defaultRoute The default route.
 */
export const App = ( { defaultRoute } ) => {

	// get the basename, usually 'wp-admin/' but can be something else if the site installation changed it
	const path     = document.location.pathname;
	const basename = path.substring( 0, path.lastIndexOf( '/' ) );

	return (
		<SlotFillProvider>
			<SelectedContextProvider>
				<ErrorBoundary>
					<HistoryRouter history={ getHistory( defaultRoute ) }>
						<Routes basename={ basename }>

							<Route path='/:namespace/:collection' exact element={ <Page /> }>

								<Route path=':id' exact element={ <ViewRecord /> }>
									<Route path=':tab' exact element={ <RenderTab /> }>
										<Route
											path=':innerNamespace/:innerCollection'
											exact
											element={ <InnerPage /> }
										>
											<Route path=':innerId' exact element={ <ViewInnerRecord /> }>
												<Route path=':innerTab' exact element={ <RenderInnerTab /> } />
												<Route index element={ <InnerRecordOverview /> } />
											</Route>
											<Route path='add' exact element={ <CreateInnerRecord /> } />
										</Route>
									</Route>
									<Route index element={ <RecordOverview /> } />
								</Route>

								<Route
									path='add'
									exact
									element={ <CreateRecord /> }
									handle={{
										title: ( { labels, collection } ) => labels?.add_new_item ?? collection,
									}}
								></Route>

								<Route
									path='import'
									exact
									element={ <Import /> }
								></Route>

								<Route index element={ <RecordsTable /> } />

							</Route>

						</Routes>
					</HistoryRouter>
				</ErrorBoundary>
			</SelectedContextProvider>
		</SlotFillProvider>
	);
};
