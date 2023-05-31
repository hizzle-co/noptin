/**
 * External dependencies
 */
import { forwardRef } from "@wordpress/element";
import { Notice, Spinner, CardBody, CardFooter, Button } from "@wordpress/components";

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
const RecordOverview = ( { overview,  title }, ref ) => {

	// Prepare the state.
	const { namespace, collection, args } = useRoute();
	const {
		save,
		editedRecord,
		record,
		hasEdits,
        isSaving,
		isResolving,
		hasResolutionFailed,
		getResolutionError
	} = useRecord( namespace, collection, args.id );

	// Show the loading indicator if we're loading the schema.
	if ( isResolving ) {

		return (
			<Wrap title={ title } ref={ ref }>
				<CardBody>
					<Spinner />
				</CardBody>
			</Wrap>
		);
	}

	// Show error if any.
	if ( hasResolutionFailed() ) {
		const error = getResolutionError();

		return (
			<Wrap title={ __( 'Error', 'newsletter-optin-box' ) }>
				<CardBody>
					<Notice status="error" isDismissible={ false }>
						{ error.message || __( 'An unknown error occurred.', 'newsletter-optin-box' ) }
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	// Display the add record form.
	return (
		<Wrap title={title}>

			<CardBody style={{ opacity: isSaving ? 0.5 : 1 }}>
				The record overview goes here.
			</CardBody>

			{ hasEdits && (
				<CardFooter>
					<Button variant="primary" onClick={ save } isBusy={ isSaving } disabled={ isSaving }>
						{ __( 'Save Changes', 'newsletter-optin-box' ) }
						{ isSaving && <Spinner /> }
					</Button>
				</CardFooter>
			) }
		</Wrap>
	);

}

export default forwardRef( RecordOverview );
