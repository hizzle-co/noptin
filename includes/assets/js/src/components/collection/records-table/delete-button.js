/**
 * External dependencies
 */
import { useDispatch } from "@wordpress/data";
import { useState } from "@wordpress/element";
import { Button, Modal, Spinner } from "@wordpress/components";
import { trash } from "@wordpress/icons";
import { __, sprintf } from "@wordpress/i18n";
import { addQueryArgs } from "@wordpress/url";

/**
 * Local dependencies.
 */
import { useRoute } from "../hooks";
import { BlockButton, ErrorNotice } from "../../styled-components";
import { useSelected } from "../../table/selected-context";

/**
 * Displays a delete button.
 *
 */
export default function DeleteButton() {

	const { namespace, collection, args } = useRoute();
	const STORE_NAME        = `${namespace}/${collection}`;
	const dispatch          = useDispatch( STORE_NAME );
	const [isOpen, setOpen] = useState( false );
	const [error, setError] = useState( null );
	const [deleting, setDeleting] = useState( false );
	const [selected, setSelected] = useSelected();
	const deleteAll  = selected.length === 0;
	const buttonText = deleteAll ? __( 'Delete All', 'newsletter-optin-box' ) : __( 'Delete Selected', 'newsletter-optin-box' );
	const modalTitle = deleteAll ? __( 'Delete', 'newsletter-optin-box' ) : __( 'Delete Selected', 'newsletter-optin-box' );
	const deleteArgs = deleteAll ? { ...args, number: -1 } : { include: selected.join( ',' ) };

	const TheModal = () => (
		<>
			{ deleting ? (
				<>
					<Spinner />
					{ __( 'Deleting...', 'newsletter-optin-box' ) }
				</>
			) : (
				<>
					{ error ? (
						<ErrorNotice>{error.message}</ErrorNotice>
					) : (
						<ErrorNotice>
							{ deleteAll && __( 'Are you sure you want to delete all matching records?', 'newsletter-optin-box' ) }
							{ ! deleteAll && sprintf( __( 'Are you sure you want to delete %d selected records?', 'newsletter-optin-box' ), selected.length ) }
						</ErrorNotice>
					)}

					<BlockButton
						isDestructive
						onClick={() => {
							setDeleting( true );

							dispatch.deleteRecords( addQueryArgs( '', deleteArgs ), dispatch )
								.then( ( res ) => {
									setOpen( false );
									setSelected( [] );
								} )
								.catch( ( error ) => {
									setError( error );
								} )
								.finally( () => {
									setDeleting( false );
								} );
						}}
					>
						{ __( 'Yes, Delete!', 'newsletter-optin-box' ) }
					</BlockButton>

					<BlockButton onClick={() => setOpen( false )} variant="secondary" __withNoMargin>
						{ __( 'Cancel', 'newsletter-optin-box' ) }
					</BlockButton>
				</>
			)}
		</>
	)

	return (
		<>

			<Button
				onClick={() => setOpen( true )}
				icon={trash}
				text={buttonText}
				isDestructive
			/>

			{isOpen && (
				<Modal title={modalTitle} onRequestClose={() => setOpen( false )}>
					<TheModal />
				</Modal>
			)}

		</>
	);

}
