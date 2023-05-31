import { FormToggle, Flex, FlexBlock, FlexItem, __experimentalUseNavigator as useNavigator, Button } from "@wordpress/components";
import { dateI18n } from "@wordpress/date";
import getEnumBadge from "./enum-colors";
import { useRoute } from "../hooks";

/**
 * Displays the primary column.
 * @param {Object} props
 * @param {Object} props.record The record object.
 * @param {string} props.name  The name of the column.
 * @param {string} props.label The label of the column.
 * @param {string} props.description The description of the column.
 * @returns
 */
const PrimaryColumn = ( { record, name } ) => {

	const { namespace, collection, navigate } = useRoute();
	const value       = record[name];
	const avatarStyle = {
		height: '32px',
		width: '32px',
		borderRadius: '50%',
	};
	const avatar         = record.avatar_url ? <img src={ record.avatar_url } style={ avatarStyle } alt={ value } /> : null;

	const handleClick = () => {
		navigate( `/${namespace}/${collection}/update`, { id: record.id } );
	};

	const ColValue = avatar ? (
		<Flex>
			<FlexItem>
				{ avatar }
			</FlexItem>
			<FlexBlock>
				{ value }
			</FlexBlock>
		</Flex>
	) : value;

	const btnStyle = {
		width: '100%',
    	alignItems: 'start',
    	textDecoration: 'none',
	}

	return (
		<Button variant="link" style={ btnStyle } onClick={ handleClick }>
			{ ColValue }
		</Button>
	)
}

/**
 * Displays a single cell in the records table.
 * @param {Object} props
 * @param {Object} props.record The record object.
 * @param {string} props.name  The name of the column.
 * @param {string} props.label The label of the column.
 * @param {string} props.description The description of the column.
 * @returns
 */
export default function DisplayCell( { record, name, label, description, length, nullable, readonly, multiple, is_dynamic, is_boolean, is_numeric, is_float, is_date, is_primary_col, ...extra } ) {

	const value = record[name];

	// Nulls and undefined values are displayed as a dash.
	if ( value === null || value === undefined || value === '' ) {
		return <span className="noptin-table__cell--null">&ndash;</span>;
	}

	if ( is_primary_col && typeof value === 'string' ) {
		return <PrimaryColumn record={ record } name={ name } />;
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
