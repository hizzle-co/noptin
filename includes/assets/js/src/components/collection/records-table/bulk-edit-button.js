/**
 * External dependencies
 */
import { useDispatch } from "@wordpress/data";
import { useState, Fragment } from "@wordpress/element";
import { Button, Modal, Notice, Spinner, Icon } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";

/**
 * Local dependencies.
 */
import { BlockButton } from "../../styled-components";
import { useSelected } from "../../table/selected-context";
import { useParams } from "react-router-dom";
import { useCurrentQueryRecordCount, useQueryOrSelected } from "../hooks";
import Setting from "../../setting";
import { useFilterableFields, prepareField } from "./filters";

/**
 * Displays the bulk edit form.
 */
const EditForm = ( {editableFields, onSave, changes, setAttributes} ) => {

	// Display the edit records form.
	return (
		<form onSubmit={ onSave }>

			{ editableFields.map( ( field ) => {

				const preparedSetting = {
					...prepareField( field ),
					default: '',
					placeholder: __( 'Do not update', 'newsletter-optin-box' ),
				};

				let toRemoveSetting = null;

				if ( field.multiple ) {
					preparedSetting.label = sprintf( __( '%s - To Add', 'newsletter-optin-box' ), field.label );
					preparedSetting.name  = `${ field.name }::add`;

					toRemoveSetting = {
						...preparedSetting,
						name: `${ field.name }::remove`,
						label: sprintf( __( '%s - To Remove', 'newsletter-optin-box' ), field.label )
					}

				}

				return (
					<Fragment key={ field.name }>
						<div style={ { marginBottom: '1.6rem' } }>
							<Setting
								settingKey={ preparedSetting.name }
								saved={ changes }
								setAttributes={ setAttributes }
								setting={ preparedSetting }
							/>
						</div>

						{ toRemoveSetting && (
							<div style={ { marginBottom: '1.6rem' } }>
								<Setting
									settingKey={ toRemoveSetting.name }
									saved={ changes }
									setAttributes={ setAttributes }
									setting={ toRemoveSetting }
								/>
							</div>
						) }
					</Fragment>
				);
			})}
		</form>
	);

}

/**
 * Displays the bulk edit modal.
 */
const TheModal = ( {editableFields} ) => {
	const { namespace, collection } = useParams();
	const dispatch              = useDispatch( `${namespace}/${collection}` );
	const [error, setError]     = useState( null );
	const [saving, setSaving]   = useState( false );
	const [saved, setSaved]     = useState( false );
	const [selected ]           = useSelected();
	const [changes, setChanges] = useState( {} );
	const query                 = useQueryOrSelected( selected );
	const totalRecords          = useCurrentQueryRecordCount();
	const hasChanges            = Object.keys( changes ).length > 0;

	// A function to save records.
	const onSaveRecords = ( e ) => {

		e?.preventDefault();

		// Save once.
		if ( saving ) {
			return;
		}

		setSaving( true );

		// Prepare the batch action.
		const batchAction = {
			bulk_update: {
				merge: changes,
				query,
			},
		};

		dispatch.batchAction( batchAction, dispatch )
			.then( () => {
				setSaved( true );
				setChanges( {} );
			} )
			.catch( ( error ) => {
				setError( error );
			} )
			.finally( () => {
				setSaving( false );
			} );
	}

	// Sets edited attributes.
	const setAttributes = ( atts ) => {
		setChanges( { ...changes, ...atts } );

		if ( error ) {
			setError( null );
		}

		if ( saved ) {
			setSaved( false );
		}
	}

	// Display the edit records modal.
	return (
		<>

			<EditForm
				editableFields={ editableFields }
				changes={ changes }
				onSave={ onSaveRecords }
				setAttributes={ setAttributes }
			/>

			{ hasChanges && (
				<BlockButton variant="primary" onClick={ onSaveRecords } isBusy={ saving }>
					{ ! saving && <Icon icon="cloud-saved" /> }&nbsp;
					{ sprintf(
						saving ? __( 'Saving %d records...', 'newsletter-optin-box' ) : __( 'Edit %d records', 'newsletter-optin-box' ),
						selected.length > 0 ? selected.length : totalRecords
					)}
					{ saving && <Spinner /> }
				</BlockButton>
			)}

			{ error && ! hasChanges && (
				<Notice status="error" isDismissible={true}>
					{error.message}
				</Notice>
			)}

			{ saved && ! hasChanges && (
				<Notice status="success" isDismissible={true}>
					{ __( 'Records updated successfully.', 'newsletter-optin-box' ) }
				</Notice>
			)}
		</>
	);
}

/**
 * Displays a bulk edit button.
 *
 */
export default function BulkEditButton() {

	const [isOpen, setOpen] = useState( false );
	const editableFields    = useFilterableFields( true );

	// Whether we should display the button.
	const displayButton = editableFields.length > 0;

	// Display the button.
	return (
		<>
			{ displayButton && (
				<>
					<Button
						onClick={() => setOpen( true )}
						variant="tertiary"
						text={__( 'Bulk Edit', 'newsletter-optin-box' )}
					/>

					{isOpen && (
						<Modal title={__( 'Bulk Edit', 'newsletter-optin-box' )} onRequestClose={() => setOpen( false )}>
							<TheModal editableFields={editableFields}/>
						</Modal>
					)}
				</>
			)}
		</>
	);

}
