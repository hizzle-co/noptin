/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { useState, useEffect, useMemo } from "@wordpress/element";
import { useReactTable, createColumnHelper, getCoreRowModel, flexRender } from "@tanstack/react-table";

const columnHelper = createColumnHelper();

const RowActions = ({ row }) => {
    return JSON.stringify(row);
};

// Make some columns!
const columns = [
    // Actions Column
    columnHelper.display({
        id: 'actions',
        cell: props => <RowActions row={props.row} />,
    }),
    // ID Column
    columnHelper.accessor('id', {
        header: () => 'ID',
        footer: props => props.column.id,
    }),
    // Title Column
    columnHelper.accessor('title', {
        header: () => 'Title',
        footer: props => props.column.id,
        cell: info => info.getValue(),
    }),
];

/**
 * Renders a records overview table.
 * @param {Object} props
 * @param {string} props.namespace
 * @param {string} props.collection
 * @returns 
 */
export default function Table( { namespace, collection } ) {

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

            if ( column.readonly ) {
                columns.push(
                    columnHelper.display({
                        id: column.name,
                        cell: info => info.getValue(),
                        header: () => column.label,
                        footer: props => props.column.id,
                    })
                );
            } else {
                columns.push(
                    columnHelper.accessor( column.name, {
                        header: () => column.label,
                        footer: props => props.column.id,
                        cell: info => info.getValue(),
                    } )
                );
            }
        } );

        return columns;
    }, [ schema ] );

    // Prepare data.
    const { getHeaderGroups, getRowModel, getFooterGroups } = useReactTable({
        data: rows,
        columns,
        getCoreRowModel: getCoreRowModel(),
    })

    return (
        <table className="wp-list-table widefat fixed striped table-view-list subscribers">
            <thead>
                {getHeaderGroups().map(headerGroup => (
                    <tr key={headerGroup.id}>
                        {headerGroup.headers.map(header => (
                            <th key={header.id}>
                                {header.isPlaceholder
                                    ? null
                                    : flexRender(
                                        header.column.columnDef.header,
                                        header.getContext()
                                    )}
                            </th>
                        ))}
                    </tr>
                ))}
            </thead>
            <tbody>
                {getRowModel().rows.map(row => (
                    <tr key={row.id}>
                        {row.getVisibleCells().map(cell => (
                            <td key={cell.id}>
                                {flexRender(cell.column.columnDef.cell, cell.getContext())}
                            </td>
                        ))}
                    </tr>
                ))}
            </tbody>
            <tfoot>
                {getFooterGroups().map(footerGroup => (
                    <tr key={footerGroup.id}>
                        {footerGroup.headers.map(header => (
                            <th key={header.id}>
                                {header.isPlaceholder
                                    ? null
                                    : flexRender(
                                        header.column.columnDef.footer,
                                        header.getContext()
                                    )}
                            </th>
                        ))}
                    </tr>
                ))}
            </tfoot>
        </table>
    );
}
