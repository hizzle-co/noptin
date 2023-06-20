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
];

/**
 * Like useSelect, but the selectors return objects containing
 * both the original data AND the resolution info.
 *
 * @param {Function} mapQuerySelect see useSelect
 * @param {Array}    deps           see useSelect
 *
 * @see useSelect
 *
 * @return {Object} Queried data.
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
			continue;
		}

		Object.defineProperty( resolvers, selectorName, {
			get:
				() =>
				( ...args ) => {
					const { getIsResolving, hasFinishedResolution, getResolutionError, hasResolutionFailed } = selectors;
					const error = getResolutionError( selectorName, args );
					const isResolving = !! getIsResolving( selectorName, args );
					const hasResolved =
						! isResolving &&
						hasFinishedResolution( selectorName, args );
					const data = selectors[ selectorName ]( ...args );

					let status;
					if ( isResolving ) {
						status = 'RESOLVING';
					} else if ( hasResolved ) {
						if ( hasResolutionFailed() ) {
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

	const recordState = useSelect( ( select ) => {
		const store = select( STORE_NAME );

		return {
			record: store.getRecord( recordId ),
			isResolving: () => store.isResolving( 'getRecord', [ recordId ] ) || ! store.hasStartedResolution( 'getRecord', [ recordId ] ),
			hasResolutionFailed: () => store.hasResolutionFailed( 'getRecord', [ recordId ] ),
			getResolutionError: () => store.getResolutionError( 'getRecord', [ recordId ] ),
		}
	},[ recordId ] );

	return { ...recordState, ...mutations };
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

	return useSelect( ( select ) => {
		const store = select( STORE_NAME );

		return {
			data: store.getTabContent( recordId, tabID ),
			isResolving: () => store.isResolving( 'getTabContent', [ recordId, tabID ] ) || ! store.hasStartedResolution( 'getTabContent', [ recordId, tabID ] ),
			hasResolutionFailed: () => store.hasResolutionFailed( 'getTabContent', [ recordId, tabID ] ),
			getResolutionError: () => store.getResolutionError( 'getTabContent', [ recordId, tabID ] ),
		}
	},[ recordId, tabID ]);

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

	return useSelect( ( select ) => {
		const store = select( STORE_NAME );

		return {
			data: store.getRecordOverview( recordId ),
			isResolving: () => store.isResolving( 'getRecordOverview', [ recordId ] ) || ! store.hasStartedResolution( 'getRecordOverview', [ recordId ] ),
			hasResolutionFailed: () => store.hasResolutionFailed( 'getRecordOverview', [ recordId ] ),
			getResolutionError: () => store.getResolutionError( 'getRecordOverview', [ recordId ] ),
		}
	},[ recordId ]);
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
	const argsString = addQueryArgs( '', queryArgs );

	return useQuerySelect(
		( query ) => query( STORE_NAME ).getRecords( argsString ),
		[ namespace, collection, argsString ]
	);
}

/**
 * Resolves the store schema.
 *
 * @param {String} collection
 * @param {Object} queryArgs Query arguments.
 * @return {Object} The records resolution.
 */
export function useSchema( namespace, collection ) {

	const STORE_NAME = `${namespace}/${collection}`;

	return useSelect( ( select ) => {
		const store = select( STORE_NAME );

		return {
			data: store.getSchema(),
			isResolving: () => store.isResolving( 'getSchema' ) || ! store.hasStartedResolution( 'getSchema' ),
			hasResolutionFailed: () => store.hasResolutionFailed( 'getSchema' ),
			getResolutionError: () => store.getResolutionError( 'getSchema' ),
		}
	}, []);

}
