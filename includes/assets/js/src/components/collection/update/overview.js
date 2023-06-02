/**
 * External dependencies
 */
import { forwardRef, useState, useMemo } from "@wordpress/element";
import { Notice, Spinner, CardBody, Button, Flex, FlexBlock, FlexItem, CardFooter } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { compact } from 'lodash';

/**
 * Internal dependencies
 */
import { useRecord, useSchema } from "../../../store-data/hooks";
import { useRoute } from "../hooks";
import Setting from "../../setting";
import Wrap from "../wrap";

/**
 * Displays the edit form.
 *
 * @param {Object} props
 * @param {Object} props.component
 */
const EditForm = ( { schema, record, error, onSaveRecord, setAttributes } ) => {

	// Prepare form fields.
	const fields = useMemo( () => ( compact(
		schema.schema.map( ( field ) => {

			// Abort for readonly and dynamic fields.
			if ( field.readonly || field.is_dynamic ) {
				return null;
			}

			// Abort for hidden fields...
			if ( schema.hidden && schema.hidden.includes( field.name ) ) {
				return null;
			}

			// ... and fields to ignore.
			if ( schema.ignore && schema.ignore.includes( field.name ) ) {
				return null;
			}

			const preparedSetting = {
				default: field.default,
				label: field.label,
				el: 'input',
				type: 'text',
				name: field.name,
				isInputToChange: true,
			};

			if ( field.enum && ! Array.isArray( field.enum ) ) {
				preparedSetting.el = 'select';
				preparedSetting.options = field.enum;
			}

			if ( field.isLongText ) {
				preparedSetting.el = 'textarea';
			}

			if ( field.is_numeric || field.is_float ) {
				preparedSetting.type = 'number';
			}

			if ( field.is_boolean ) {
				preparedSetting.type = 'toggle';
			}

			if ( field.description && field.description !== field.label ) {
				preparedSetting.description = field.description;
			}

			return preparedSetting;
		} )
	) ), [ schema ] );

	// Display the add record form.
	return (
		<form onSubmit={ onSaveRecord }>

			{ fields.map( ( field ) => (
				<div style={ { marginBottom: '1.6rem' } } key={ field.name }>
					<Setting
						settingKey={ field.name }
						saved={ record }
						setAttributes={ setAttributes }
						setting={ field }
					/>
				</div>
			) ) }

			{ error && (
				<Notice status="error">
					{ error.message }
				</Notice>
			) }

		</form>
	);

}

/**
 * Allows the user to edit a single record.
 *
 * @param {Object} props
 * @param {Object} props.tab
 * @param {String} props.tab.title
 */
const RecordOverview = ( { tab: {title} }, ref ) => {

	// Prepare the state.
	const { namespace, collection, navigate, args } = useRoute();

	const STORE_NAME                = `${namespace}/${collection}`;
	const [ error, setError ]       = useState( null );
	const [ saving, setSaving ]     = useState( false );
	const [ edits, setEdits ]       = useState( {} );
	const schema                    = useSchema( namespace, collection );
	const record                    = useRecord( namespace, collection, args.id );

	// A function to save a record.
	const onSaveRecord = ( e ) => {

		e?.preventDefault();

		// Save once.
		if ( saving ) {
			return;
		}

		setSaving( true );

		record.save( edits )
			.then( () => {
				setEdits( {} );
			} )
			.catch( ( error ) => {
				setError( error );
			} )
			.finally( () => {
				setSaving( false );
			} );
	}

	// A function to delete a record.
	const onDeleteRecord = () => {

		// Confirm.
		if ( ! confirm( __( 'Are you sure you want to delete this record?', 'newsletter-optin-box' ) ) ) {
			return;
		}

		// Delete the record.
		record.delete();

		// Navigate back to the list.
		navigate( STORE_NAME );
	}

	// Record actions.
	const actions = (
		<Flex gap={2} justify="start" wrap>
			{ Object.keys( edits ).length > 0 && (
				<FlexItem>
					<Button variant="primary" onClick={ onSaveRecord } isBusy={ saving }>
						{ saving ? __( 'Saving...', 'newsletter-optin-box' ) : __( 'Save', 'newsletter-optin-box' ) }
						{ saving && <Spinner /> }
					</Button>
				</FlexItem>
			) }
			<FlexItem>
				<Button variant="secondary" onClick={ onDeleteRecord } isDestructive>
					{ __( 'Delete', 'newsletter-optin-box' ) }
				</Button>
			</FlexItem>
		</Flex>
	);

	// Sets edited attributes.
	const setAttributes = ( atts ) => {
		setEdits( { ...edits, ...atts } );

		if ( error ) {
			setError( null );
		}
	}

	// Display the add record form.
	return (
		<Wrap title={title} actions={ actions } ref={ ref }>

			<CardBody>
				<Flex align="start" wrap>
					<FlexItem>
						Overview goes here.
					</FlexItem>
					<FlexBlock>
						<EditForm
							schema={ schema.data }
							record={{ ...record.record, ...edits }}
							error={ error }
							onSaveRecord={ onSaveRecord }
							setAttributes={ setAttributes }
						/>
					</FlexBlock>
				</Flex>
			</CardBody>

			<CardFooter>
				{ actions }
			</CardFooter>
		</Wrap>
	);

}

export default forwardRef( RecordOverview );
