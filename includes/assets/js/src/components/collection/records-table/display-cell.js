import { Flex, FlexBlock, FlexItem, Button, Icon } from "@wordpress/components";
import { dateI18n, getSettings, __experimentalGetSettings } from "@wordpress/date";
import getEnumBadge from "./enum-colors";
import { Avatar } from "../../styled-components";
import { useParams } from "react-router-dom";
import { navigateTo, getNewPath } from "../../navigation";

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

	const { namespace, collection } = useParams();
	const value  = record[name];
	const avatar = record.avatar_url ? <Avatar src={ record.avatar_url } alt={ value } /> : null;

	const handleClick = () => {
		const newRoute = getNewPath( {}, `/${namespace}/${collection}/${record.id}` );
		navigateTo( newRoute );
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
 * @param {Object} props.row The record object.
 * @param {Object} props.header The header
 * @param {string} props.headerKey  The name of the column.
 * @returns
 */
export default function DisplayCell( { row, header, headerKey } ) {

	const value = row[headerKey];

	// Nulls and undefined values are displayed as a dash.
	if ( value === null || value === undefined || value === '' ) {
		return <span className="noptin-table__cell--null">&ndash;</span>;
	}

	if ( header.is_primary && typeof value === 'string' ) {
		return <PrimaryColumn record={ row } name={ headerKey } />;
	}

	// Boolean values are displayed as a toggle.
	if ( header.is_boolean ) {

		const icon  = value ? 'yes' : 'no';
		const color = value ? '#3a9001' : '#880000';
		return <Icon size={ 24 } style={{color}} icon={ icon } />;
	}

	// Dates are formatted as a date.
	if ( header.is_date && value ) {
		const settings = getSettings ? getSettings() : __experimentalGetSettings();
		return dateI18n( settings.formats.datetime, value );
	}

	// If we have an enum, display the label.
	if ( header.enum && header.enum[value] ) {
		return <span className={ `noptin-badge ${ getEnumBadge( value ) }` }>{ header.enum[value] }</span>;
	}

	// Strings, numbers, and floats are displayed as is.
	if ( header.is_numeric || header.is_float || typeof value === 'string' ) {
		return value;
	}

	return JSON.stringify( value );
}
