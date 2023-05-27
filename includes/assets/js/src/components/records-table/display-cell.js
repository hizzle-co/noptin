import { FormToggle } from "@wordpress/components";
import { dateI18n } from "@wordpress/date";
import getEnumBadge from "./enum-colors";

/**
 * Displays a single cell in the records table.
 * @param {Object} props
 * @param {Object} props.record The record object.
 * @param {string} props.name  The name of the column.
 * @param {string} props.label The label of the column.
 * @param {string} props.description The description of the column.
 * @returns
 */
export default function DisplayCell( { record, name, label, description, length, nullable, readonly, multiple, is_dynamic, is_boolean, is_numeric, is_float, is_date, ...extra } ) {

	const value = record[name];

	// Nulls and undefined values are displayed as a dash.
	if ( value === null || value === undefined || value === '' ) {
		return <span className="noptin-table__cell--null">&ndash;</span>;
	}

	// Boolean values are displayed as a toggle.
	if ( is_boolean ) {
		return <FormToggle checked={ value } disabled={ readonly } onChange={ () => {} } />;
	}

	// Dates are formatted as a date.
	if ( is_date && value && value.date && value.timezone ) {
		return dateI18n( 'F j, Y g:i a', value.date );
	}

	// If we have an enum, display the label.
	if ( extra.enum && extra.enum[value] ) {
		return <span className={ `noptin-badge ${ getEnumBadge( value ) }` }>{ extra.enum[value] }</span>;
	}

	// Strings, numbers, and floats are displayed as is.
	if ( is_numeric || is_float || typeof value === 'string' ) {
		return value;
	}

	return label;
}
