/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { TableCell } from '../styled-components';
import { alignmentStyle } from './header-cell';

/**
 * Displays a single body cell in a table.
 *
 * @param {Object} props Component props.
 */
export default function BodyCell( { cell, cellClassName, align, isNumeric, isHeader, isSorted } ){

	const Cell = isHeader ? TableCell.withComponent( 'th' ) : TableCell;

	const cellProps  = {
		className: classnames(
			'noptin-table__item',
			cellClassName,
			{
				'is-numeric': isNumeric,
				'is-sorted': isSorted,
			}
		),
		...alignmentStyle( align, isNumeric ),
	};

	return (
		<Cell scope={ isHeader ? 'row' : undefined } isSorted={ isSorted } { ...cellProps }>
			{ cell.display || null }
		</Cell>
	);
}
