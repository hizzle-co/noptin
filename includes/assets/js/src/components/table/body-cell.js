/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * Displays a single body cell in a table.
 *
 * @param {Object} props Component props.
 */
export default function BodyCell( { cell, cellClassName, isLeftAligned, isNumeric, isHeader, isSorted } ){

	const Cell = isHeader ? 'th' : 'td';

	const cellClasses = classnames(
		'noptin-table__item',
		cellClassName,
		{
			'is-left-aligned': isLeftAligned || ! isNumeric,
			'is-numeric': isNumeric,
			'is-sorted': isSorted,
		}
	);

	return (
		<Cell scope={ isHeader ? 'row' : undefined } className={ cellClasses }>
			{ cell.display || null }
		</Cell>
	);
}
