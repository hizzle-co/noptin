/**
 * External dependencies
 */
import { Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import RecordsTable from './records-table';
import Export from './export';
import Import from './import';
import CreateRecord from './create-record';
import UpdateRecord from './update-record';

/**
 * Displays a single screen.
 *
 * @param {Object} props Component props.
 * @param {string} props.path The path of the screen.
 * @param {string} props.component The component to render.
 */
export default function Screen( { path, component, ...props } ) {

	// Display records.
	if ( 'list-records' === component ) {
		return (
			<Suspense fallback="Loading">
				<RecordsTable { ...props } />
			</Suspense>
		)
	}

	// Export records.
	if ( 'export' === component ) {
		return <Export { ...props } />;
	}

	// Import records.
	if ( 'import' === component ) {
		return <Import { ...props } />;
	}

	// Create a new record.
	if ( 'create-record' === component ) {
		return <CreateRecord { ...props } />;
	}

	// Update a record.
	if ( 'update-record' === component ) {
		return <UpdateRecord { ...props } />;
	}

	return path;
}
