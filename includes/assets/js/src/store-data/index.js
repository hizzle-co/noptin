/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import * as actions from './actions';
import createDynamicActions from './dynamic-actions';
import reducer from './reducer';
import createResolvers from './resolvers';
import * as selectors from './selectors';
export * as hooks from './hooks';

// Cache the stores.
const stores = {};

/**
 * Initializes the store.
 *
 * @param {string} namespace The namespace.
 * @param {string} collection The collection.
 * @return {Object} The store.
 */
export default function initStore( namespace, collection ) {

    const STORE_NAME = `${namespace}/${collection}`;

    // If the store already exists, return it.
    if ( stores[ STORE_NAME ] ) {
        return stores[ STORE_NAME ];
    }

    // Create the store.
    stores[ STORE_NAME ] = createReduxStore( STORE_NAME, {
        reducer,
        actions: { ...actions, ...createDynamicActions( namespace, collection ) },
        selectors: { ...selectors },
        controls,
        resolvers: createResolvers( namespace, collection ),
    } );

    register( stores[ STORE_NAME ] );

    return stores[ STORE_NAME ];
}
