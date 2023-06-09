/**
 * External dependencies
 */
import { range } from 'lodash';

/**
 * Internal dependencies
 */
import Table from './table';
import { LoadingPlaceholder } from "../styled-components";

/**
 * A table placeholder component.
 *
 * @param {Object} props Component props.
 * @param {Object} props.query The current query.
 * @param {Array} props.headers The table headers.
 */
const TablePlaceholder = ( { query, headers, ...props } ) => {

	const numberOfRows = query.per_page ? parseInt( query.per_page, 10 ) : 10;
	const rows         = range( numberOfRows ).map( () =>
		headers.map( () => ( {
			display: <LoadingPlaceholder />,
		} ) )
	);

	const tableProps = { query, headers, ...props };
	return (
		<Table
			ariaHidden={ true }
			className="is-loading"
			rows={ rows }
			{ ...tableProps }
		/>
	);
};

export default TablePlaceholder;