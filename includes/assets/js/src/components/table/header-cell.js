/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import { Button, Icon } from '@wordpress/components';
import { memo } from "@wordpress/element";

/**
 * Internal dependencies
 */
import { TableHeader } from '../styled-components';

export const alignmentStyle = ( align, isNumeric ) => {

	// Remove alignments.
	if ( ! align && ! isNumeric ) {
		return {};
	}

	if ( 'center' === align || isNumeric ) {
		return { align: 'center' };
	}

	return { align };
}

/**
 * Displays a single header cell in a table.
 *
 * @param {Object} props Component props.
 */
const HeaderCell = ( { columnKey, columnLabel, screenReaderLabel, cellClassName, minWidth, display, align, isSortable, isNumeric, isSorted, sortDir, setSortBy } ) => {

	// Label the header cell for screen readers.
	const alignment = alignmentStyle( align, isNumeric );

	// Prepare props.
	const thProps  = {
		className: classnames( 'noptin-table__header', cellClassName ),
		...alignment,
		minWidth: minWidth || undefined,
	};

	// Adding aria-sort attribute to the header cell.
	if ( isSortable ) {
		thProps[ 'aria-sort' ] = 'none';
		if ( isSorted ) {
			thProps[ 'aria-sort' ] = sortDir === 'asc' ? 'ascending' : 'descending';
		}
	}

	// We only sort by ascending if the col is already sorted descending
	const iconLabel = sortDir !== 'asc'
		? sprintf( __( 'Sort by %s in ascending order', 'newsletter-optin-box' ), screenReaderLabel || columnLabel )
		: sprintf( __( 'Sort by %s in descending order', 'newsletter-optin-box' ), screenReaderLabel || columnLabel );

	// Prepare the text label.
	const textLabel = (
		<>
			<span aria-hidden={ Boolean(screenReaderLabel) }>
				{ display || columnLabel }
			</span>
			{ screenReaderLabel && (
				<span className="screen-reader-text">
					{ screenReaderLabel }
				</span>
			) }
		</>
	);

	const TheIcon = <Icon icon={ sortDir !== 'asc' ? 'arrow-down-alt2' : 'arrow-up-alt2' } />;

	return (
		<TableHeader role="columnheader" scope="col" key={ columnKey } isSorted={ isSorted } { ...thProps }>
			{ ( isSortable ) ? (
				<Button onClick={ () => setSortBy( columnKey ) } label={ iconLabel } showTooltip>
					{ 'right' === alignment.align && TheIcon }
					<span> {textLabel} </span>
					{ 'right' !== alignment.align && TheIcon }
				</Button>
			) : (
				textLabel
			) }
		</TableHeader>
	);
};

export default memo( HeaderCell );
