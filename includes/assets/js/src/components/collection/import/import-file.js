/**
 * External dependencies
 */
import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

/**
 * Local dependencies.
 */
import MapHeaders from "./map-headers";
import Progress from "./progress";

/**
 * Displays the import tool.
 *
 * @param {Object} props
 * @param {Object} props.file The file to import.
 * @param {Object} props.schema The schema of the collection.
 * @param {Function} props.back The callback to call when clicking on the back button.
 */
const ImportFile = ( { file, schema: { schema, ignore, hidden, id_prop }, back } ) => {

	const [ mappedHeaders, setMappedHeaders ] = useState( null );
    const [ updateRecords, setUpdateRecords ] = useState( false );

	// If we have no headers, map them.
	if ( ! mappedHeaders ) {
		return (
			<MapHeaders
				file={ file }
				schema={ schema }
				ignore={ ignore }
				hidden={ hidden }
				back={ back }
				onContinue={ ( headers, update ) => {
                    setMappedHeaders( headers );
                    setUpdateRecords( update );
                } }
			/>
		);
	}

	// Display the importer.
	return (
        <Progress
            file={ file }
            headers={ mappedHeaders }
            updateRecords={ updateRecords }
            back={ back }
            id_prop={ id_prop }
        />
    );
}

export default ImportFile;
