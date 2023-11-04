import { useParams } from 'react-router-dom';
import { getNewPath, navigateTo, getPath } from "../navigation";

/**
 * Returns a function to navigate back home.
 *
 * @returns {Function}
 */
export function useNavigateCollection() {
	// Get the collection and namespace from the URL.
    const { namespace, collection } = useParams();
	return ( suffix = '' ) => navigateTo( getNewPath( {}, `/${namespace}/${collection}/${suffix}` ) );
}

/**
 * Returns a function to append a path to the curren path.
 *
 * @returns {Function}
 */
export function useAppendNavigation() {
	return ( suffix = '' ) => {
		const path = getPath();

		// Remove trailing slash.
		if ( path.endsWith( '/' ) ) {
			path = path.substring( 0, path.length - 1 );
		}

		// Add leading to suffix.
		if ( ! suffix.startsWith( '/' ) ) {
			suffix = `/${suffix}`;
		}

		navigateTo( getNewPath( {}, `${path}${suffix}` ) )
	}
}

/**
 * Returns a query for bulk actions.
 * @param {Array} selected
 * @returns {Object}
 */
export function useQueryOrSelected( selected, query ) {
	if ( selected.length > 0 ) {
		return { include: selected.join( ',' ), number: -1 };
	}

	const newQuery = { ...query, number: -1 };;

	[ 'order', 'hizzle_path', 'orderby', 'paged', 'page' ].forEach( ( key ) => {
		delete newQuery[ key ];
	} );

	return newQuery;
}
