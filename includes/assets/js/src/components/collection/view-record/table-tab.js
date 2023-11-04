/**
 * External dependencies
 */
import { useMemo } from "@wordpress/element";
import { Notice, CardBody, Icon } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";

/**
 * Local dependencies.
 */
import { useTabContent } from "../../../store-data/hooks";
import Table from "../../table";
import Wrap from "../wrap";

/**
 * Displays a single cell.
 *
 * @param {Object} props
 * @returns The cell.
 */
const DisplayCell = ( { row, header, headerKey } ) => {

	const { is_list, item, args, is_primary, url, is_boolean } = header;

	if ( is_list ) {

		if ( ! Array.isArray( row[ headerKey ] ) || 0 === row[ headerKey ].length ) {
			return '—';
		}
	
		return (
			<ul>
				{ row[ headerKey ].map( ( arrayValue, index ) => {
					let value = arrayValue;

					if ( item ) {
						const replacements = args.map( arg => arrayValue[ arg ] );
						value = sprintf( item, ...replacements );
					}

					return <li key={ index } dangerouslySetInnerHTML={ { __html: value } } />
				} ) }
			</ul>
		);
	}

	if ( is_primary && url ) {
		const recordUrl = row[ url ];

		if ( ! recordUrl ) {
			return <strong>{ row[ headerKey ] }</strong>;
		}

		return (
			<a href={ row[ url ] } style={{textDecoration: 'none'}} target="_blank">
				<strong>{ row[ headerKey ] }</strong>
			</a>
		);
	}

	if ( is_boolean ) {
		const theIcon = row[ headerKey ] ? 'yes' : 'no';
		return <Icon icon={ theIcon } />;
	}

	return <div dangerouslySetInnerHTML={ { __html: row[ headerKey ] } } />;
}

/**
 * Renders a table for the current tab.
 *
 * @param {Object} props
 * @param {Object} props.tab
 * @param {String} props.tabName
 * @returns The records table.
 */
export default function TableTab( {tab, tabName, namespace, collection, recordId} ) {

	// Prepare the state.
	const tabContent = useTabContent( namespace, collection, recordId, tabName );

	// Prepare headers.
	const headers = useMemo( () => tab.headers.map( ( header ) => ({
		key: header.name,
		label: header.label,
		isSortable: false,
		isNumeric: header.is_numeric,
		...header
	})) , [ tab.headers ] );

	// Show error if any.
	if ( 'ERROR' === tabContent.status ) {

		return (
			<Wrap title={tab.title}>
				<CardBody>
					<Notice status="error" isDismissible={ false }>
						{ tabContent.error?.message || __( 'An unknown error occurred.', 'newsletter-optin-box' ) }
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	return (
		<Table
			{ ...tab }
			isLoading={ tabContent.isResolving }
			rows={ tabContent.data }
			headers={ headers }
			showFooter={ false }
			DisplayCell={ DisplayCell }
		/>
	);
}
