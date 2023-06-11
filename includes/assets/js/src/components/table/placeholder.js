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

	const numberOfRows = query.per_page ? parseInt( query.per_page, 10 ) : 25;
	const rows         = range( numberOfRows ).map( () => ( {} ) );

	const tableProps = { query, headers, ...props };
	return (
		<Table
			{ ...tableProps }
			rows={ rows }
			DisplayCell={ LoadingPlaceholder }
			isLoading
			aria-hidden="true"
		/>
	);
};

export default TablePlaceholder;
