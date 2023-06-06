/**
 * External dependencies
 */
import { useMemo, useState } from "@wordpress/element";
import { Notice, CardBody, Icon } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import { without } from 'lodash';

/**
 * Local dependencies.
 */
import { useTabContent } from "../../../store-data/hooks";
import { useRoute } from "../hooks";
import Table from "../../table";
import Wrap from "../wrap";

/**
 * Displays a single cell.
 *
 * @param {Object} props
 * @returns The cell.
 */
const DisplayCell = ( { name, is_list, item, args, is_primary, url, is_boolean, record } ) => {

	if ( is_list ) {

		if ( ! Array.isArray( record[ name ] ) || 0 === record[ name ].length ) {
			return "-";
		}

		return (
			<ul>
				{ record[ name ].map( ( arrayValue, index ) => {
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
		const recordUrl = record[ url ];

		if ( ! recordUrl ) {
			return <strong>{ record[ name ] }</strong>;
		}

		return (
			<a href={ record[ url ] } style={{textDecoration: 'none'}} target="_blank">
				<strong>{ record[ name ] }</strong>
			</a>
		);
	}

	if ( is_boolean ) {
		const theIcon = record[ name ] ? 'yes' : 'no';
		return <Icon icon={ theIcon } />;
	}

	return <div dangerouslySetInnerHTML={ { __html: record[ name ] } } />;
}

/**
 * Renders a table for the current tab.
 *
 * @param {Object} props
 * @param {Object} props.tab
 * @returns The records table.
 */
export default function TableTab( props ) {

	// Prepare the state.
	const [ hiddenCols, setHiddenCols ] = useState( props.tab.hidden ? props.tab.hidden : [] );
	const { namespace, collection, args } = useRoute();
	const tab = useTabContent( namespace, collection, args.id, props.tab.name );

	// Show error if any.
	if ( tab.hasResolutionFailed() ) {

		const error = tab.getResolutionError();
		return (
			<Wrap title={props.tab.title}>
				<CardBody>
					<Notice status="error" isDismissible={ false }>
						{ error.message || __( 'An unknown error occurred.', 'newsletter-optin-box' ) }
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	// Prepare headers.
	let rowHeader = 0;
	const headers = useMemo( () => {

		const headers = [];

		props.tab.headers.forEach( ( header, index ) => {

			if ( header.is_primary ) {
				rowHeader = index;
			}

			headers.push( {
				key: header.name,
				label: header.label,
				visible: ! hiddenCols.includes( header.name ),
				isSortable: false,
				isNumeric: header.is_numeric,
			});
		} );

		return headers;
	}, [ props.tab.headers, hiddenCols ] );

	// Prepare records.
	const rows = useMemo( () => {

		if ( ! Array.isArray( tab.data ) ) {
			return [];
		}

		return tab.data.map( ( row ) => {

			return props.tab.headers.map( ( column ) => {

				return {
					display: <DisplayCell { ...column } record={ row } />,
					value: row[column.key]
				}
			});
		});
	}, [ tab.data ] );

	return (
		<Table
			{ ...props.tab }
			records={ rows }
			isLoading={ tab.isResolving() }
			rows={ rows }
			headers={ headers }
			showFooter={ false }
			rowHeader={ rowHeader }
			toggleHiddenCol={ ( col ) => {

				if ( hiddenCols.includes( col ) ) {
					setHiddenCols( without( hiddenCols, col ) );
				} else {
					setHiddenCols( [ ...hiddenCols, col ] );
				}
			} }
		/>
	);
}
