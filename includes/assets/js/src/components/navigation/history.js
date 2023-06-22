/**
 * External dependencies
 */
import { createBrowserHistory } from 'history';
import { getQueryArgs } from '@wordpress/url';

let _history;

/**
 * Recreate `history` to coerce React Router into accepting path arguments found in query
 * parameter `hizzle_path`, allowing a url hash to be avoided. Since hash portions of the url are
 * not sent server side, full route information can be detected by the server.
 *
 * `<Router />` and `<Switch />` components use `history.location()` to match a url with a route.
 * Since they don't parse query arguments, recreate `get location` to return a `pathname` with the
 * query path argument's value.
 *
 * In react-router v6, { basename } is no longer a parameter in createBrowserHistory(), and the
 * replacement is to use basename in the <Route> component.
 *
 * @return {Object} React-router history object with `get location` modified.
 */
function getHistory( defaultRoute = '/' ) {
	if ( ! _history ) {
		const browserHistory = createBrowserHistory();
		_history = {
			get action() {
				return browserHistory.action;
			},
			get location() {
				const { location } = browserHistory;

				const query = getQueryArgs( location.search );

				const pathname = query.hizzle_path || defaultRoute;

				return {
					...location,
					pathname,
				};
			},
			createHref: browserHistory.createHref,
			push: browserHistory.push,
			replace: browserHistory.replace,
			go: browserHistory.go,
			back: browserHistory.back,
			forward: browserHistory.forward,
			block: browserHistory.block,
			listen( listener ) {
				return browserHistory.listen( () => {
					listener( {
						action: this.action,
						location: this.location,
					} );
				} );
			},
		};
	}
window._history = _history;
	return _history;
}

export { getHistory };
