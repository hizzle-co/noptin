/**
 * External dependencies
 */
import { useDispatch } from "@wordpress/data";
import { useState } from "@wordpress/element";
import { Button, Modal, Spinner } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import { addQueryArgs } from "@wordpress/url";

/**
 * Local dependencies.
 */
import { BlockButton, ErrorNotice } from "../../styled-components";
import { useSelected } from "../../table/selected-context";
import { useQuery } from "../../navigation";
import { useParams } from "react-router-dom";
import { useCurrentQueryRecordCount } from "../hooks";

/**
 * Displays a delete button.
 *
 */
export default function DeleteButton() {

	const args = useQuery();
	const { namespace, collection } = useParams();
	const dispatch          = useDispatch( `${namespace}/${collection}` );
	const [isOpen, setOpen] = useState( false );
	const [error, setError] = useState( null );
	const [deleting, setDeleting] = useState( false );
	const [selected, setSelected] = useSelected();
	const deleteAll  = selected.length === 0;
	const count      = useCurrentQueryRecordCount();
	const title      = deleteAll ? __( 'Delete', 'newsletter-optin-box' ) : __( 'Delete Selected', 'newsletter-optin-box' );
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
							{ deleteAll && sprintf( __( 'Are you sure you want to delete %d matching records?', 'newsletter-optin-box' ), count ) }
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
				icon="trash"
				text={title}
				isDestructive
			/>

			{isOpen && (
				<Modal title={title} onRequestClose={() => setOpen( false )}>
					<TheModal />
				</Modal>
			)}

		</>
	);

}
