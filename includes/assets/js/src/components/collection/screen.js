/**
 * External dependencies
 */
import { useAtomValue } from "jotai";
import { __ } from "@wordpress/i18n";
import { Notice, CardBody } from "@wordpress/components";

/**
 * Internal dependencies
 */
import { components } from './store';
import RecordsTable from './records-table';
import Export from './export';
import Import from './import';
import CreateRecord from './create-record';
import UpdateRecord from './update-record';
import Wrap from "./wrap";

/**
 * Displays a single screen.
 *
 * @param {Object} props Component props.
 * @param {string} props.path The path of the screen.
 */
export default function Screen( { path } ) {

	// Prepare the store.
	const allComponents = useAtomValue( components );

	// Abort if the component is not found.
	if ( ! allComponents[ path ] ) {
		return (
            <Wrap title={ __( 'Error:', 'newsletter-optin-box' ) }>
                <CardBody>
                    <Notice status="error" isDismissible={ false }>
					{ __( 'Component not found', 'newsletter-optin-box' ) }
                    </Notice>
                </CardBody>
            </Wrap>
        );
	}

	const component = allComponents[ path ].component;

	// Display records.
	if ( 'list-records' === component ) {
		return <RecordsTable path={path} component={component} />
	}

	// Export records.
	if ( 'export' === component ) {
		return <Export path={path} component={component} />;
	}

	// Import records.
	if ( 'import' === component ) {
		return <Import path={path} component={component} />;
	}

	// Create a new record.
	if ( 'create-record' === component ) {
		return <CreateRecord path={path} component={component} />;
	}

	// Update a record.
	if ( 'update-record' === component ) {
		return <UpdateRecord path={path} component={component} />;
	}

	return path;
}
