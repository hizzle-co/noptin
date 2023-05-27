/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { useState, useEffect, useMemo } from "@wordpress/element";
import TableCard from "../table";
import DisplayCell from "./display-cell";
import { Notice } from "@wordpress/components";

/**
 * Local dependencies.
 */
import { getSchema } from "../collection/get-schema";

/**
 * Renders a records overview table.
 * @param {Object} props
 * @param {string} props.namespace
 * @param {string} props.collection
 * @param {string} props.title
 * @param {string} props.singular
 * @param {string} props.icon
 * @returns 
 */
export default function RecordsTable( { namespace, collection, ...extra } ) {

    const [ total, setTotal ] = useState( 0 );
    const [ schema, setSchema ] = useState( [] );
    const [ rows, setRows ] = useState( [] );
    const [ loading, setLoading ] = useState( true );
    const [ error, setError ] = useState( null );

    // Fetch schema on mount.
    useEffect( () => {
        getSchema( namespace, collection )
            .then( ( {count, schema } ) => {
                setTotal( count );
                setSchema( schema );
            } )
            .catch( ( error ) => {
                setError( error );
            } )
    }, [namespace, collection] );

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
    }, [namespace, collection] );

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
            rows={ data }
            headers={ columns }
            rowsPerPage={ 7 }
            totalRows={ total }
            summary={ [] }
            isLoading={ loading }
            onQueryChange={ ( ...query ) => { console.log( query )} }
            onSearch={ ( query ) => { console.log( query )} }
            onSort={ ( query ) => { console.log( query )} }
            onClickDownload={ () => { console.log( 'download' ) } }
            downloadable={ true }
            query={ { page: 1 } }
            className={ `${namespace}-${collection}-records-table` }
            { ...extra }
        />
    );
}
