/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import initStore from './index';

/**
 * Uses the specified store.
 *
 * @param {String} namespace
 * @param {String} collection
 * @return {Object} The store.
 */
export function useStore( namespace, collection ) {
	return initStore( namespace, collection );
}

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

	return useSelect( ( select ) => {
		const store = select( STORE_NAME );

		return {
			data: store.getRecords( argsString ),
			get: () => store.getRecords( argsString ),
			isResolving: () => store.isResolving( 'getRecords', [ argsString ] ) || ! store.hasStartedResolution( 'getRecords', [ argsString ] ),
			hasResolutionFailed: () => store.hasResolutionFailed( 'getRecords', [ argsString ] ),
			getResolutionError: () => store.getResolutionError( 'getRecords', [ argsString ] ),
		}
	}, [argsString]);
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
