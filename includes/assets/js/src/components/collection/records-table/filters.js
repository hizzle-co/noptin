/**
 * External dependencies
 */
import { useState, useMemo, Fragment } from "@wordpress/element";
import { Button, Modal } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";

/**
 * Local dependencies.
 */
import { BlockButton } from "../../styled-components";
import Setting from "../../setting";
import { useSchema } from "../../../store-data/hooks";

/**
 * Displays the bulk edit form.
 */
const EditForm = ( {fields, onApplyFilters, filters, setAttributes} ) => {

	// Display the edit records form.
	return (
		<form onSubmit={ onApplyFilters }>

			{ fields.map( ( field ) => {

                // If field.is_numeric || field.is_float, show _min and _max fields.
                // If field.is_date, show _before and _after fields.
				const mainSetting = {
					...prepareField( field ),
					default: '',
					placeholder: __( 'Any', 'newsletter-optin-box' ),
                    canSelectPlaceholder: true,
				};

				let secondarySetting = null;

                if ( field.is_boolean ) {
                    mainSetting.el = 'select';
                    mainSetting.options = {
                        '1': __( 'Yes', 'newsletter-optin-box' ),
                        '0': __( 'No', 'newsletter-optin-box' ),
                    };
                } else if ( field.is_numeric || field.is_float ) {
                    mainSetting.name = `${ field.name }_min`;
                    mainSetting.label = sprintf( __( '%s - Min', 'newsletter-optin-box' ), field.label );

                    secondarySetting = {
                        ...mainSetting,
                        name: `${ field.name }_max`,
                        label: sprintf( __( '%s - Max', 'newsletter-optin-box' ), field.label )
                    }
                } else if ( field.is_date ) {
                    mainSetting.name = `${ field.name }_after`;
                    mainSetting.label = sprintf( __( '%s - After', 'newsletter-optin-box' ), field.label );

                    secondarySetting = {
                        ...mainSetting,
                        name: `${ field.name }_before`,
                        label: sprintf( __( '%s - Before', 'newsletter-optin-box' ), field.label )
                    }
                } else {

					secondarySetting = {
						...mainSetting,
						name: `${ field.name }_not`,
						label: sprintf( __( '%s - Exclude', 'newsletter-optin-box' ), field.label )
					}

				}

				return (
					<Fragment key={ field.name }>
						<div style={ { marginBottom: '1.6rem' } }>
							<Setting
								settingKey={ mainSetting.name }
								saved={ filters }
								setAttributes={ setAttributes }
								setting={ mainSetting }
							/>
						</div>

						{ secondarySetting && (
							<div style={ { marginBottom: '1.6rem' } }>
								<Setting
									settingKey={ secondarySetting.name }
									saved={ filters }
									setAttributes={ setAttributes }
									setting={ secondarySetting }
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
const TheModal = ( {currentFilters, fields, setOpen, setQuery} ) => {
	const [filters, setFilters] = useState( { ...currentFilters } );

	// A function to apply the filters.
	const onApplyFilters = ( e ) => {
		e?.preventDefault();
		setQuery( filters );
        setOpen( false );
	}

	// Sets edited attributes.
	const setAttributes = ( atts ) => {
		setFilters( { ...filters, ...atts } );
	}

	// Display the edit records modal.
	return (
		<>

			<EditForm
				fields={ fields }
				filters={ filters }
				onApplyFilters={ onApplyFilters }
				setAttributes={ setAttributes }
			/>

            <BlockButton variant="primary" onClick={ onApplyFilters } text={__( 'Apply Filters', 'newsletter-optin-box' )} />
		</>
	);
}

/**
 * Returns a list of filterable fields.
 */
export const useFilterableFields = ( { namespace, collection, isBulkEditing = false } ) => {
    const { data } = useSchema( namespace, collection );

    return useMemo( () => {

		if ( ! data.schema ) {
			return [];
		}
	
        return data.schema.filter( ( field ) => {

            // Remove readonly fields.
            if ( ( field.readonly && isBulkEditing ) || field.is_dynamic ) {
                return false;
            }

            // Remove ignorable fields.
            if ( Array.isArray( data.ignore ) && data.ignore.includes( field.name ) ) {
				return false;
			}

            // Remove hidden fields.
            if ( Array.isArray( data.hidden ) && data.hidden.includes( field.name ) ) {
                return false;
            }

			// Allow tokens.
			if ( field.is_tokens ) {
				return true;
			}

            // If we're not bulk editing, include numbers and dates.
            if ( ! isBulkEditing && ( field.is_numeric || field.is_float || field.is_date ) ) {
                return true;
            }

            // Remove non-selectable fields.
            if ( ! field.enum || Array.isArray( field.enum ) ) {
                return false;
            }

            return true;
        } );
    }, [ data, isBulkEditing ] );
}

/**
 * Prepares a field for use in a setting.
 */
export const prepareField = ( field ) => {

	const prepared = {
		default: field.default,
		label: field.label,
		el: 'input',
		type: 'text',
		name: field.name,
		isInputToChange: true,
	};

	// Custom attributes.
	if ( field.js_props?.setting ) {
		prepared.customAttributes = field.js_props.setting;
	}

	// Conditions.
	if ( field.js_props?.conditions ) {
		prepared.conditions = field.js_props?.conditions;
	}

	// Tokens.
	if ( field.is_tokens ) {
		prepared.el = 'form_token';
		prepared.suggestions = field.suggestions;
	} else if ( field.enum && ! Array.isArray( field.enum ) ) {
		prepared.el = 'select';
		prepared.options = field.enum;

		if ( field.multiple ) {
			prepared.el = 'multi_checkbox';
		}
	} else if ( field.is_textarea ) {
		prepared.el = 'textarea';
	}

	if ( field.is_numeric || field.is_float ) {
		prepared.type = 'number';
	}

	if ( field.is_date ) {
		prepared.type = 'date';
	}

	if ( field.is_boolean ) {
		prepared.type = 'toggle';
	}

	if ( field.description && field.description !== field.label ) {
		prepared.description = field.description;
	}

	return prepared;
}

/**
 * Displays a filters edit button.
 *
 */
export default function FiltersButton( { query, setQuery, ...props} ) {

	const [isOpen, setOpen] = useState( false );
	const fields     = useFilterableFields( props );
    const filters    = useMemo( () => {

        // Prepare the filters.
        const filters = {};

        fields.forEach( ( field ) => {

            // Add variations, _not, _min, _max, _before, _after.
            [ '', '_not', '_min', '_max', '_before', '_after' ].forEach( ( variation ) => {
                const name = field.name + variation;

                if ( query[ name ] ) {
                    filters[ name ] = query[ name ];
                }
            } );
        });

        return filters;
    }, [ query, fields ] );
    const numFilters = Object.keys( filters ).length;
    const buttonText = numFilters > 0 ? sprintf( __( 'Filters (%d)', 'newsletter-optin-box' ), numFilters ) : __( 'Filter Records', 'newsletter-optin-box' );

	// Display the button.
	return (
		<>
			{ fields.length > 0 && (
				<>
					<Button
						onClick={() => setOpen( true )}
						variant="tertiary"
                        icon="filter"
						text={buttonText}
					/>

					{isOpen && (
						<Modal title={__( 'Filter records', 'newsletter-optin-box' )} onRequestClose={() => setOpen( false )}>
							<TheModal fields={fields} currentFilters={filters} setOpen={setOpen} setQuery={setQuery} />
						</Modal>
					)}
				</>
			)}
		</>
	);

}
