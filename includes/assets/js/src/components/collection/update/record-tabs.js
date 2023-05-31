/**
 * External dependencies
 */
import { forwardRef, useMemo } from "@wordpress/element";
import { Notice, Spinner, CardBody, CardFooter, Button, TabPanel } from "@wordpress/components";

/**
 * Internal dependencies
 */
import { useRecord } from "../../../store-data/hooks";
import { useRoute } from "../hooks";
import Wrap from "../wrap";

/**
 * Allows the user to edit a single record.
 *
 * @param {Object} props
 * @param {Object} props.component
 */
const RecordTab = ( tab ) => {
    const { namespace, collection, path, args, navigate } = useRoute();
	const {
		edit,
		editedRecord,
		record,
        isSaving,
		isResolving,
		hasResolutionFailed,
		getResolutionError
	} = useRecord( namespace, collection, args.id );

    return (
        <p>{ tab.title }</p>
    );
}

/**
 * Displays the tabs for the record.
 *
 * @param {Object} props
 * @param {Object} props.component
 */
const RecordTabs = ( { tabs }, ref ) => {

	// Prepare the state.
	const { path, args, navigate } = useRoute();

    /**
     * Fired when a tab is selected.
     *
     * @param {String} tabName The name of the tab. 
     */
    const onSelect = ( tabName ) => {
        if ( tabName !== args.hizzle_tab ) {
            navigate( path, { ...args, hizzle_tab: tabName } );
        }
    };

    /**
     * Prepare the tabs.
     */
    const tabKeys        = useMemo( Object.keys( tabs ), [ tabs ] );
    const tabContent     = useMemo( tabKeys.map( ( tabKey ) => ( {...tabs[ tabKey ], name: tabKey } ) ), [ tabs ] );
    const initialTabName = args.hizzle_tab || tabKeys[ 0 ];

	// Display the add record form.
	return (
		<TabPanel className="hizzle-record-tabs" onSelect={ onSelect } initialTabName={ initialTabName } tabs={ tabContent } ref={ ref }>
		    { RecordTab }
	    </TabPanel>
	);
}

export default forwardRef( RecordTabs );
