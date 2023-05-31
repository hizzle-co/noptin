/**
 * External dependencies
 */
import { forwardRef } from "@wordpress/element";
import { Notice, Spinner, CardBody, Flex } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

/**
 * Local dependencies.
 */
import Wrap from "../wrap";
import { useRecordSchema } from "../../../store-data/hooks";
import { useRoute } from "../hooks";
import RecordOverview from "./overview";
import RecordTabs from "./record-tabs";

/**
 * Allows the user to edit a single record.
 *
 * @param {Object} props
 * @param {Object} props.component
 */
const UpdateRecord = ( { component: { title } }, ref ) => {

	// Prepare the state.
	const { namespace, collection, args } = useRoute();
	const schema = useRecordSchema( namespace, collection, args.id );

	// Show the loading indicator if we're loading the schema.
	if ( schema.isResolving() ) {

		return (
			<Wrap title={ __( 'Loading', 'newsletter-optin-box' ) } ref={ ref }>
				<CardBody>
					<Spinner />
				</CardBody>
			</Wrap>
		);
	}

	// Show error if any.
	if ( schema.hasResolutionFailed() ) {
		const error = schema.getResolutionError();

		return (
			<Wrap title={ __( 'Error', 'newsletter-optin-box' ) } ref={ ref }>
				<CardBody>
					<Notice status="error" isDismissible={ false }>
						{ error.message || __( 'An unknown error occurred.', 'newsletter-optin-box' ) }
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	// Display the update record screen.
	return (
		<Flex direction="column" gap={ 4 } ref={ ref }>
			<RecordOverview title={ title } schema={ schema.data.overview } />
			<RecordTabs schema={ schema.data.tabs } />
		</Flex>
	)
}

export default forwardRef( UpdateRecord );
