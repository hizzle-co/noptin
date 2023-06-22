/**
 * External dependencies
 */
import { useState } from "@wordpress/element";
import { CardBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

/**
 * Local dependencies.
 */
import { useCurrentSchema } from "../hooks";
import Wrap from "../wrap";
import SelectFile from "./select-file";
import ImportFile from "./import-file";

/**
 * Allows the user to import new records.
 *
 */
export default function Import() {

	// Fetch the schema.
	const { data } = useCurrentSchema();
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
                    />
                ) : (
                    <SelectFile onUpload={ onUpload } />
                ) }
			</CardBody>
		</Wrap>
	);

}
