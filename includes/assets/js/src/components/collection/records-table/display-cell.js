import { Flex, FlexBlock, FlexItem, Button, Icon } from "@wordpress/components";
import { dateI18n, getSettings, __experimentalGetSettings } from "@wordpress/date";
import { getQueryArg, addQueryArgs } from "@wordpress/url";
import getEnumBadge from "./enum-colors";
import { Avatar, Badge } from "../../styled-components";
import { useNavigateCollection } from "../hooks";
import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

const isObject = ( obj ) => obj && typeof obj === 'object' && obj.constructor === Object;

/**
 * Takes an avatar URL then generates consistent colors for the avatar.
 *
 * @param {string} avatarUrl The avatar URL.
 * @return {Object} The generated background and text colors.
 */
export const normalizeAvatarColors = ( avatarUrl, fallbackText ) => {

	if ( ! avatarUrl ) {
		return avatarUrl;
	}

	const fallback = getQueryArg( avatarUrl, 'd' );

	// Abort if we're not falling back to ui-avatar.
	if ( ! fallback || ! fallback.includes( 'ui-avatars.com' ) ) {
		return avatarUrl;
	}

	const match = fallback.match( /\/api\/(.*?)\/64\// );
	const text  = ( match && match.length > 1 ) ? match[1] : fallback;

	// Generate unique color for the string.
	const color = getEnumBadge( fallbackText || text );

	// Replace the colors in the URL.
	const index = fallback.indexOf( '/64/' );

	if ( index !== -1 ) {
		return addQueryArgs( avatarUrl, {
			d: `${ fallback.substring( 0, index + 4 ) }/${ color.backgroundColor.replace( '#', '' ) }/${ color.color.replace( '#', '' ) }`,
		} );
	}

	return avatarUrl;
}

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

	const navigateTo = useNavigateCollection();
	const value      = record[name];
	const avatar_url = normalizeAvatarColors( record.avatar_url, value );
	const avatar     = avatar_url ? <Avatar src={ avatar_url } alt={ value } /> : null;

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
		<Button variant="link" style={ btnStyle } onClick={ () => navigateTo( record.id ) }>
			{ ColValue }
		</Button>
	)
}

/**
 * Displays badge list.
 *
 * @param {Object} props
 * @param {Array} props.value The value.
 * @param {Object} props.enums The enums.
 * @returns {JSX.Element}
 */
const BadgeList = ( { value, enums = {} } ) => {

	const [ isOpen, setIsOpen ] = useState( false );
	const toShow = isOpen ? value : value.slice( 0, 2 );
	const showToggle = value.length > 2;

	// Display the list.
	return (
		<Flex gap={2} justify="flex-start" wrap>
			{ toShow.map( ( val ) => (
				<FlexItem key={ val }>
					<Badge {...getEnumBadge( val )}>{ enums[val] || val }</Badge>
				</FlexItem>
			) ) }

			{ showToggle && (
				<FlexItem>
					<Button variant="link" onClick={ () => setIsOpen( ! isOpen ) }>
						{ isOpen ? __( 'Hide', 'newsletter-optin-box' ) : __( 'Show all', 'newsletter-optin-box' ) }
					</Button>
				</FlexItem>
			) }
		</Flex>
	);
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

	// Empty arrays are displayed as a dash.
	if ( Array.isArray( value ) && value.length === 0 ) {
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
		// If value contains 10 chars, format as date, otherwise format as datetime.
		if ( value.length === 10 ) {
			return dateI18n( settings.formats.date, value );
		}

		return dateI18n( settings.formats.datetime, value );
	}

	// Tokens.
	if ( header.is_tokens && Array.isArray( value ) ) {
		return <BadgeList value={ value } />;
	}

	// Array with enum values are displayed as a badge.
	if ( header.enum && Array.isArray( value ) ) {
		return <BadgeList value={ value } enums={ header.enum } />;
	}

	// Strings, numbers, and floats are displayed as is.
	if ( header.is_numeric || header.is_float || typeof value === 'string' ) {

		// If we have an enum, display the label.
		if ( isObject( header.enum ) ) {
			return <Badge {...getEnumBadge( value )}>{ header.enum[value] || value }</Badge>;
		}

		return value;
	}

	return JSON.stringify( value );
}
