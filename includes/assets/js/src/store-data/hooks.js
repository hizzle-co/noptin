/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import memoize from 'memize';

/**
 * Exports the meta selectors.
 */
export const META_SELECTORS = [
	'getIsResolving',
	'hasStartedResolution',
	'hasFinishedResolution',
	'isResolving',
	'getCachedResolvers',
	'getResolutionError',
	'hasResolutionFailed',
	'getAllRecordData',
];

/**
 * Removes hizzle_path and page from the query string and returns the new query string.
 *
 * @param {Object} queryString The query.
 * @return {String} The new query string.
 */
const prepareQueryString = ( queryString ) => {
	const query = { ...queryString };

	delete query.hizzle_path;
	delete query.page;

	if ( parseInt( query.paged ) === 1 ) {
		delete query.paged;
	}

	return addQueryArgs( '', query );
}

/**
 * Like useSelect, but the selectors return objects containing
 * both the original data AND the resolution info.
 *
 * @param {Function} mapQuerySelect see useSelect
 * @param {Array}    deps           see useSelect
 *
 * @see useSelect
 *
 */
export function useQuerySelect( mapQuerySelect, deps ) {

	return useSelect( ( select, registry ) => {
		const resolve = ( store ) => enrichSelectors( select( store ) );
		return mapQuerySelect( resolve, registry );
	}, deps );
}

/**
 * Transform simple selectors into ones that return an object with the
 * original return value AND the resolution info.
 *
 * @param {Object} selectors Selectors to enrich
 * @return {Object} Enriched selectors
 */
const enrichSelectors = memoize( ( ( selectors ) => {
	const resolvers = {};

	for ( const selectorName in selectors ) {
		if ( META_SELECTORS.includes( selectorName ) ) {
			resolvers[ selectorName ] = selectors[ selectorName ];
			continue;
		}

		Object.defineProperty( resolvers, selectorName, {
			get:
				() =>
				( ...args ) => {

					const { getIsResolving, hasFinishedResolution, getResolutionError, hasResolutionFailed, hasStartedResolution } = selectors;
					const error = getResolutionError( selectorName, args );
					const isResolving = !! getIsResolving( selectorName, args ) || ! hasStartedResolution( selectorName, args );
					const hasResolved =
						! isResolving &&
						hasFinishedResolution( selectorName, args );
					const data = selectors[ selectorName ]( ...args );

					let status;
					if ( isResolving ) {
						status = 'RESOLVING';
					} else if ( hasResolved ) {
						if ( hasResolutionFailed( selectorName, args ) || error ) {
							status = 'ERROR';
						} else {
							status = 'SUCCESS';
						}
					} else {
						status = 'IDLE';
					}

					return {
						data,
						status,
						isResolving,
						hasResolved,
						error,
					};
				},
		} );
	}

	return resolvers;
} ));

/**
 * Resolves the specified record.
 *
 * @param {String} namespace
 * @param {String} collection
 * @param {Number} recordId ID of the requested record.
 * @return {Object} The record resolution.
 */
export function useRecord( namespace, collection, recordId ) {
	const STORE_NAME = `${namespace}/${collection}`;

	// Ensure we have a valid record ID.
	recordId = parseInt( recordId, 10 );

	const dispatch = useDispatch( STORE_NAME );

	const mutations = useMemo(
		() => ( {
			save: ( saveOptions = {} ) => dispatch.updateRecord( recordId, saveOptions, dispatch ),
			delete: () => dispatch.deleteRecord( recordId, dispatch ).catch( (e) => { console.error( e) } ),
		} ),
		[ recordId ]
	);

	const record = useQuerySelect(
		( query ) => query( STORE_NAME ).getRecord( recordId ),
		[ namespace, collection, recordId ]
	);

	return { ...record, ...mutations };
}

/**
 * Resolves the specified record's schema.
 *
 * @param {String} namespace
 * @param {String} collection
 * @param {Object} recordId ID of the requested record.
 * @param {String} tabID ID of the requested tab.
 * @return {Object} The records resolution.
 */
export function useTabContent( namespace, collection, recordId, tabID ) {

	// Prepare the store name.
	const STORE_NAME = `${namespace}/${collection}`;

	// Ensure we have a valid record ID.
	recordId = parseInt( recordId, 10 );

	return useQuerySelect(
		( query ) => query( STORE_NAME ).getTabContent( recordId, tabID ),
		[ namespace, collection, recordId, tabID ]
	);
}

/**
 * Resolves the specified record's overview.
 *
 * @param {String} namespace
 * @param {String} collection
 * @param {Object} recordId ID of the requested record.
 * @return {Object} The records resolution.
 */
export function useRecordOverview( namespace, collection, recordId ) {

	// Prepare the store name.
	const STORE_NAME = `${namespace}/${collection}`;

	// Ensure we have a valid record ID.
	recordId = parseInt( recordId, 10 );

	return useQuerySelect(
		( query ) => query( STORE_NAME ).getRecordOverview( recordId ),
		[ namespace, collection, recordId ]
	);
}

/**
 * Resolves the specified records.
 *
 * @param {String} namespace
 * @param {String} collection
 * @param {Object} queryArgs Query arguments.
 * @return {Object} The records resolution.
 */
export function useRecords( namespace, collection, queryArgs = {} ) {

	const STORE_NAME = `${namespace}/${collection}`;
	const argsString = prepareQueryString( queryArgs );

	return useQuerySelect(
		( query ) => query( STORE_NAME ).getRecords( argsString ),
		[ namespace, collection, argsString ]
	);
}

/**
 * Resolves the specified partial records.
 *
 * @param {String} namespace
 * @param {String} collection
 * @param {Object} queryArgs Query arguments.
 * @return {Object} The records resolution.
 */
export function usePartialRecords( namespace, collection, queryArgs = {} ) {

	const STORE_NAME = `${namespace}/${collection}`;
	const argsString = prepareQueryString( queryArgs );

	return useQuerySelect(
		( query ) => query( STORE_NAME ).getPartialRecords( argsString ),
		[ namespace, collection, argsString ]
	);
}

/**
 * Resolves the store schema.
 *
 * @param {String} namespace The namespace of the store.
 * @param {String} collection The current collection.
 * @returns {ReturnType<useQuerySelect>}
 */
export function useSchema( namespace, collection ) {

	const STORE_NAME = `${namespace}/${collection}`;

	return useQuerySelect(
		( query ) => query( STORE_NAME ).getSchema(),
		[ namespace, collection ]
	);

}
