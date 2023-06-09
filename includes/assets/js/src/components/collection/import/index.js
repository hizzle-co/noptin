/**
 * External dependencies
 */
import { useState } from "@wordpress/element";
import { CardBody } from "@wordpress/components";

/**
 * Local dependencies.
 */
import { useSchema } from "../../../store-data/hooks";
import { useRoute } from "../hooks";
import Wrap from "../wrap";
import SelectFile from "./select-file";
import ImportFile from "./import-file";

/**
 * Allows the user to import new records.
 *
 * @param {Object} props
 * @param {Object} props.component
 * @param {string} props.component.title
 */
export default function Import( { component: { title } } ) {

	// Fetch the schema.
	const { namespace, collection } = useRoute();
	const schema = useSchema(namespace, collection);
	const [ file, setFile ] = useState( null );

    // Sets the current file the scrolls to top.
    const onUpload = ( file ) => {
        setFile( file );
		window.scrollTo( { top: 0, behavior: 'smooth' } );
    }

    const Step = () => {

        // If we have a file, import it.
        if ( file ) {
            return (
                <ImportFile
                    file={ file }
                    schema={ schema.data }
                    back={ () => setFile( null )}
                />
            );
        }

        // Otherwise, display the file selector.
        return <SelectFile onUpload={ onUpload } />;
    }

	return (
		<Wrap title={title}>
			<CardBody>
				<Step />
			</CardBody>
		</Wrap>
	);

}
