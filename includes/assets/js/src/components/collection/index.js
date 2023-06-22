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
import { Page } from "./page";
import { ViewRecord, RenderTab } from './view-record';
import RecordsTable from './records-table';
import CreateRecord from './create-record';
import Import from './import';

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
			<ErrorBoundary>
				<HistoryRouter history={ getHistory( defaultRoute ) }>
					<Routes basename={ basename }>

						<Route
							path='/:namespace/:collection'
							exact
							element={ <Page /> }
						>

							<Route
								path=':id'
								exact
								element={ <ViewRecord /> }
							>
								<Route path=':tab' exact element={ <RenderTab /> } />
								<Route index element={ <RenderTab /> } />
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
		</SlotFillProvider>
	);
};
