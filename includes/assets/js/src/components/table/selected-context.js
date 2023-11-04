import { createContext, useState, useContext, useCallback } from '@wordpress/element';

/**
 * Wrap tables in this context to keep track of the selected records.
 */
const SelectedContext = createContext( {} );

/**
 * Wraps the given table in a context provider.
 *
 * @param {Object} props
 * @param {Array} props.selected Initial selected records.
 * @param {JSX.Element} props.children
 * @returns {JSX.Element}
 */
export const SelectedContextProvider = ( {children} ) => {
    const [ selected, setSelected ] = useState( {} );

    return (
        <SelectedContext.Provider value={ { selected, setSelected } }>
            { children }
        </SelectedContext.Provider>
    );
};

/**
 * Hook to get and set selected records from the context.
 */
export const useSelected = ( store_name = 'default' ) => {
    const store = useContext( SelectedContext );

    // Get the selected records.
    const selected = store.selected[ store_name ] || [];

    // Update the selected records.
    const setSelected = useCallback( ( new_selected ) => {
        store.setSelected( {
            ...store.selected,
            [ store_name ]: new_selected,
        } );
    }, [ store_name, store.setSelected ] );

    return [ selected, setSelected ];
}
