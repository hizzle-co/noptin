/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

import { alignmentStyle } from './header-cell';

/**
 * Displays a single body cell in a table.
 *
 * @param {Object} props Component props.
 */
export default function BodyCell( { cell, cellClassName, align, isNumeric, isHeader, isSorted } ){

	const Cell = isHeader ? 'th' : 'td';

	const cellClasses = classnames(
		'noptin-table__item',
		cellClassName,
		{
			'is-numeric': isNumeric,
			'is-sorted': isSorted,
		}
	);

	const cellStyle = alignmentStyle( align, isNumeric );

	return (
		<Cell scope={ isHeader ? 'row' : undefined } style={ cellStyle } className={ cellClasses }>
			{ cell.display || null }
		</Cell>
	);
}
