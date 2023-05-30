/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

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

	const { editRecord, updateRecord } = useDispatch( STORE_NAME );

	const mutations = useMemo(
		() => ( {
			edit: ( record ) => editRecord( recordId, record ),
			save: ( saveOptions = {} ) => updateRecord( recordId, saveOptions ),
		} ),
		[ recordId ]
	);

	const recordState = useSelect( ( select ) => {
		const store = select( STORE_NAME );

		return {
			editedRecord: store.getEditedRecord( recordId ),
			hasEdits: store.hasEditsForRecord( recordId ),
			record: store.getRecord( recordId ),
			isResolving: () => store.isResolving( 'getRecord', [ recordId ] ),
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
 * @return {Object} The records resolution.
 */
export function useRecordSchema( namespace, collection, recordId ) {

	const STORE_NAME = `${namespace}/${collection}`;

	return useSelect( ( select ) => {
		const store = select( STORE_NAME );

		return {
			get: store.getRecordSchema( recordId ),
			isResolving: () => store.isResolving( 'getRecordSchema', [ recordId ] ),
			hasResolutionFailed: () => store.hasResolutionFailed( 'getRecordSchema', [ recordId ] ),
			getResolutionError: () => store.getResolutionError( 'getRecordSchema', [ recordId ] ),
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

	return useSelect( ( select ) => {
		const store = select( STORE_NAME );

		return {
			get: store.getRecords( queryArgs ),
			isResolving: () => store.isResolving( 'getRecords', [ queryArgs ] ),
			hasResolutionFailed: () => store.hasResolutionFailed( 'getRecords', [ queryArgs ] ),
			getResolutionError: () => store.getResolutionError( 'getRecords', [ queryArgs ] ),
		}
	},[ queryArgs ]);

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
			get: store.getSchema(),
			isResolving: () => store.isResolving( 'getSchema' ),
			hasResolutionFailed: () => store.hasResolutionFailed( 'getSchema' ),
			getResolutionError: () => store.getResolutionError( 'getSchema' ),
		}
	}, []);

}
