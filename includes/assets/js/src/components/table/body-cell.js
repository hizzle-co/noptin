/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { memo, isValidElement } from "@wordpress/element";

/**
 * Internal dependencies
 */
import { TableCell } from '../styled-components';
import { alignmentStyle } from './header-cell';

/**
 * Displays a single body cell in a table.
 *
 * @param {Object} props Component props.
 * @param {Object} props.row The row.
 * @param {Object} props.header The header.
 * @param {Function|Object} props.DisplayCell The display cell component or element.
 * @param {string} props.cellClassName The cell class name.
 * @param {string} props.headerKey The header key.
 * @param {boolean} props.isSorted Whether this is a sorted cell.
 */
const BodyCell = ( { row, header, DisplayCell, cellClassName, headerKey, isSorted } ) => {

	const Cell = header.is_primary ? TableCell.withComponent( 'th' ) : TableCell;

	const cellProps  = {
		className: classnames( 'noptin-table__col', cellClassName ),
		...alignmentStyle( header.align, header.isNumeric ),
		minWidth: header.minWidth || undefined,
	};

	// Check if we have an element or a component.
	const isJSX = isValidElement( DisplayCell );

	return (
		<Cell scope={ header.is_primary ? 'row' : undefined } isSorted={ isSorted } { ...cellProps }>
			{ isJSX ? DisplayCell : (
				<DisplayCell row={ row } header={ header } headerKey={ headerKey } />
			) }
		</Cell>
	);
}
export default memo( BodyCell );
