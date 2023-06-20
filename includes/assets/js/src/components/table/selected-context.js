import { createContext, useState, useContext } from '@wordpress/element';

/**
 * Wrap tables in this context to keep track of the selected records.
 */
export const SelectedContext = createContext( {
    selected: [],
    setSelected: () => {},
} );

/**
 * Wraps the given table in a context provider.
 *
 * @param {Object} props
 * @param {Array} props.selected Initial selected records.
 * @param {JSX.Element} props.children
 * @returns {JSX.Element}
 */
export const SelectedContextProvider = ( { initialSelected = [], children } ) => {
    const [ selected, setSelected ] = useState( initialSelected );

    return (
        <SelectedContext.Provider value={ { selected, setSelected } }>
            { children }
        </SelectedContext.Provider>
    );
};

/**
 * Hook to get and set selected records from the context.
 */
export const useSelected = () => {
    const { selected, setSelected } = useContext( SelectedContext );

    return [ selected, setSelected ];
}
