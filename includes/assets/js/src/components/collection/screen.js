/**
 * External dependencies
 */
import { __ } from "@wordpress/i18n";
import { Notice, CardBody } from "@wordpress/components";

/**
 * Internal dependencies
 */
import RecordsTable from './records-table';
import Export from './export';
import Import from './import';
import CreateRecord from './create-record';
import UpdateRecord from './update-record';
import Wrap from "./wrap";
import { useSchema } from "../../store-data/hooks";
import { useRoute } from "./hooks";

/**
 * Displays a single screen.
 *
 * @param {Object} props Component props.
 * @param {string} props.path The path of the screen.
 */
export default function Screen( { path } ) {

	// Prepare the store.
	const { namespace, collection } = useRoute();
	const { data } = useSchema( namespace, collection );

	// Abort if the component is not found.
	if ( ! data.routes[ path ] ) {
		return (
            <Wrap title={ __( 'Error:', 'newsletter-optin-box' ) }>
                <CardBody>
                    <Notice status="error" isDismissible={ false }>
					{ __( 'Route not found', 'newsletter-optin-box' ) }
                    </Notice>
                </CardBody>
            </Wrap>
        );
	}

	const component = data.routes[ path ].component;
	const args      = {
		component: data.routes[ path ],
		path
	};

	// Display records.
	if ( 'list-records' === component ) {
		return <RecordsTable {...args} />
	}

	// Export records.
	if ( 'export' === component ) {
		return <Export {...args} />;
	}

	// Import records.
	if ( 'import' === component ) {
		return <Import {...args} />;
	}

	// Create a new record.
	if ( 'create-record' === component ) {
		return <CreateRecord {...args} />;
	}

	// Update a record.
	if ( 'update-record' === component ) {
		return <UpdateRecord {...args} />;
	}

	return path;
}
