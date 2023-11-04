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
import { useQueryOrSelected } from "../hooks";

/**
 * Displays a delete button.
 *
 */
export default function DeleteButton( { namespace, collection, query, count, selected, setSelected } ) {

	const dispatch = useDispatch( `${namespace}/${collection}` );
	const [isOpen, setOpen] = useState( false );
	const [error, setError] = useState( null );
	const [deleting, setDeleting] = useState( false );
	const deleteAll = selected.length === 0;
	const title = deleteAll ? __( 'Delete', 'newsletter-optin-box' ) : __( 'Delete Selected', 'newsletter-optin-box' );
	const deleteArgs = useQueryOrSelected( selected, query );

	const TheModal = () => (
		<>
			{deleting ? (
				<>
					<Spinner />
					{__( 'Deleting...', 'newsletter-optin-box' )}
				</>
			) : (
				<>
					{error ? (
						<ErrorNotice>{error.message}</ErrorNotice>
					) : (
						<ErrorNotice>
							{deleteAll && sprintf( __( 'Are you sure you want to delete %d matching records?', 'newsletter-optin-box' ), count )}
							{!deleteAll && sprintf( __( 'Are you sure you want to delete %d selected records?', 'newsletter-optin-box' ), selected.length )}
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
						{__( 'Yes, Delete!', 'newsletter-optin-box' )}
					</BlockButton>

					<BlockButton onClick={() => setOpen( false )} variant="secondary" __withNoMargin>
						{__( 'Cancel', 'newsletter-optin-box' )}
					</BlockButton>
				</>
			)}
		</>
	)

	return (
		<>

			<Button
				onClick={() => setOpen( true )}
				variant="tertiary"
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
