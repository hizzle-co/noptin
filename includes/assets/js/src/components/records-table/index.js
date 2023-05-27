/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { useState, useEffect, useMemo } from "@wordpress/element";
import TableCard from "../table";
import DisplayCell from "./display-cell";

/**
 * Renders a records overview table.
 * @param {Object} props
 * @param {string} props.namespace
 * @param {string} props.collection
 * @returns 
 */
export default function RecordsTable( { namespace, collection } ) {

    const [ total, setTotal ] = useState( 0 );
    const [ schema, setSchema ] = useState( [] );
    const [ rows, setRows ] = useState( [] );
    const [ loading, setLoading ] = useState( true );
    const [ error, setError ] = useState( null );

    // Fetch schema on mount.
    useEffect( () => {
        apiFetch( { path: `${ namespace }/v1/${ collection }/collection_schema` } )
            .then( ( {count, schema } ) => {
                setTotal( count );
                setSchema( schema );
            } )
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

        schema.forEach( ( column ) => {

            // Abort if dynamic column.
            if ( column.is_dynamic ) {
                return;
            }

            columns.push( {
                key: column.name,
                visible: ! column.is_dynamic && 'id' !== column.name,
                isSortable: ! column.is_dynamic,
                ...column
            });
        } );

        return columns;
    }, [ schema ] );

    // Convert rows into data array.
    const data = useMemo( () => {

        return rows.map( ( row ) => {

            return columns.map( ( column ) => {
                return {
                    display: <DisplayCell { ...column } record={ row } />,
                    value: row[column.key]
                }
            });
        });
    }, [ rows, columns ] );

    return (
        <TableCard
            title="Revenue last week"
            rows={ data }
            headers={ columns }
            rowsPerPage={ 7 }
            totalRows={ total }
            summary={ [] }
            isLoading={ loading }
            onQueryChange={ ( query ) => { console.log( query )} }
            onSearch={ ( query ) => { console.log( query )} }
            onSort={ ( query ) => { console.log( query )} }
            onClickDownload={ () => { console.log( 'download' ) } }
            downloadable={ true }
            query={ { page: 1 } }
        />
    );
}
