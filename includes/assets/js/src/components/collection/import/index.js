/**
 * External dependencies
 */
import { useState } from "@wordpress/element";
import { CardBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

/**
 * Local dependencies.
 */
import Wrap from "../wrap";
import SelectFile from "./select-file";
import ImportFile from "./import-file";
import { useParams } from "react-router-dom";
import { useSchema } from "../../../store-data/hooks";

/**
 * Allows the user to import new records.
 *
 */
export default function Import() {

	// Fetch the schema.
    const { namespace, collection } = useParams();
	const { data } = useSchema( namespace, collection );
	const [ file, setFile ] = useState( null );

    // Sets the current file the scrolls to top.
    const onUpload = ( file ) => {
        setFile( file );
		window.scrollTo( { top: 0, behavior: 'smooth' } );
    }

	return (
		<Wrap title={data.labels?.import || __( 'Import', 'newsletter-optin-box' )}>
			<CardBody>
				{ file ? (
                    <ImportFile
                        file={ file }
                        schema={ data }
                        back={ () => setFile( null )}
                        namespace={ namespace }
                        collection={ collection }
                    />
                ) : (
                    <SelectFile onUpload={ onUpload } />
                ) }
			</CardBody>
		</Wrap>
	);

}
