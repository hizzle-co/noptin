/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import { Button } from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';

export const alignmentStyle = ( align, isNumeric ) => {

	// Remove alignments.
	if ( ! align && ! isNumeric ) {
		return {};
	}

	if ( 'center' === align || isNumeric ) {
		return { textAlign: 'center' };
	}

	return { textAlign: align };
}

/**
 * Displays a single header cell in a table.
 *
 * @param {Object} props Component props.
 */
export default function HeaderCell( { columnKey, columnLabel, screenReaderLabel, cellClassName, align, isSortable, isNumeric, isSorted, sortDir, onClick, instanceId } ) {

	// Label the header cell for screen readers.
	const labelId = `header-${ columnKey }-${ instanceId }`;

	// Prepare props.
	const btnStyle = {};
	const thProps  = {
		className: classnames(
			'noptin-table__header',
			cellClassName,
			{
				'is-sortable': isSortable,
				'is-sorted': isSorted,
				'is-numeric': isNumeric,
			}
		),
		style: alignmentStyle( align, isNumeric ),
	};

	// Adding aria-sort attribute to the header cell.
	if ( isSortable ) {
		thProps[ 'aria-sort' ] = 'none';
		if ( isSorted ) {
			thProps[ 'aria-sort' ] = sortDir === 'asc' ? 'ascending' : 'descending';
		}

		if ( 'right' === align ) {
			btnStyle.justifyContent = 'flex-end';
			btnStyle.paddingRight   = '24px';
			btnStyle.paddingLeft    = '24px';
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
				{ columnLabel }
			</span>
			{ screenReaderLabel && (
				<span className="screen-reader-text">
					{ screenReaderLabel }
				</span>
			) }
		</>
	);

	return (
		<th role="columnheader" scope="col" key={ columnKey } { ...thProps }>
			{ ( isSortable ) ? (
				<>
					<Button
						aria-describedby={ labelId } onClick={ onClick }
						icon={ sortDir !== 'asc' ? chevronDown : chevronUp}
						text={ textLabel }
						iconPosition="right"
					/>
					<span className="screen-reader-text" id={ labelId }>
						{ iconLabel }
					</span>
				</>
			) : (
				textLabel
			) }
		</th>
	);
};
