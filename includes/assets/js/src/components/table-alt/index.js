/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { useState, useEffect, useMemo } from "@wordpress/element";
import 'react-data-grid/lib/styles.css';
import DataGrid from 'react-data-grid';

/**
 * Renders a records overview table.
 * @param {Object} props
 * @param {string} props.namespace
 * @param {string} props.collection
 * @returns 
 */
export default function Table( { namespace, collection } ) {

    // Prepare state.
    const [ schema, setSchema ] = useState( [] );
    const [ rows, setRows ] = useState( [] );
    const [ loading, setLoading ] = useState( true );
    const [ error, setError ] = useState( null );

    // Fetch schema on mount.
    useEffect( () => {
        apiFetch( { path: `${ namespace }/v1/${ collection }/collection_schema` } )
            .then( setSchema )
            .catch( ( error ) => {
                setError( error );
            } )
    }, [] );

    // Fetch rows on mount.
    useEffect( () => {
        apiFetch( { path: `${ namespace }/v1/${ collection }` } )
            .then( setRows )
            .catch( ( error ) => {
                setError( error );
            } )
            .finally( () => {
                setLoading( false );
            } );
    }, [] );

    // Make some columns from the schema.
    const columns = useMemo( () => {

        const columns = [];

        schema.forEach( ( column ) => { // name, label, description, length, nullable, default, enum, readonly, multiple, is_dynamic, is_boolean, is_numeric, is_float, is_date

            // Abort if dynamic column.
            if ( column.is_dynamic ) {
                return;
            }

            columns.push( {
                key: column.name,
                name: column.label,
            });
        } );

        return columns;
    }, [ schema ] );

    return (
        <DataGrid
            columns={columns}
            rows={rows}
            rowKeyGetter={row => row.id}
            onRowsChange={( rows, indexes ) => { console.log({rows, indexes}); }}
            rowHeight={60}
            headerRowHeight={60}
            renderers={{
                cell: info => info.getValue(),
            }}
        />
    );
}
