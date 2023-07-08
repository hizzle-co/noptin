/**
 * External dependencies
 */
import { useMemo } from "@wordpress/element";
import { Notice } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { compact } from 'lodash';

/**
 * Internal dependencies.
 */
import Setting from "../../setting";
import { useCurrentSchema } from "../hooks";
import { prepareField } from "../records-table/filters";

/**
 * Displays the edit form.
 *
 * @param {Object} props
 * @param {Object} props.component
 */
export const EditForm = ( { record, error, onSaveRecord, setAttributes } ) => {

	const { data } = useCurrentSchema();

	// Prepare form fields.
	const fields = useMemo( () => ( compact(
		data.schema.map( ( field ) => {

			// Abort for readonly and dynamic fields.
			if ( field.readonly || field.is_dynamic ) {
				return null;
			}

			// Abort for hidden fields...
			if ( data.hidden && data.hidden.includes( field.name ) ) {
				return null;
			}

			// ... and fields to ignore.
			if ( data.ignore && data.ignore.includes( field.name ) ) {
				return null;
			}

			return prepareField( field );
		} )
	) ), [ data ] );

	// Display the edit record form.
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
