import { FormFileUpload, Tip, BaseControl, Button } from "@wordpress/components";
import { upload } from "@wordpress/icons";
import { __ } from "@wordpress/i18n";

/**
 * Displays the file input.
 *
 * @param {Object} props
 */
const SelectFile = ( { onUpload } ) => {

	return (
		<>
			<BaseControl
				label={ __( 'This tool allows you to import existing records from a CSV file.', 'newsletter-optin-box' ) }
				help={ __( 'The first row of the CSV file should contain the field names/headers.', 'newsletter-optin-box' ) }
				className="noptin-collection__upload-wrapper"
			>
				<FormFileUpload
					accept="text/csv"
					onChange={ ( event ) => onUpload( event.currentTarget.files[0] ) }
					icon={upload}
					variant="primary"
				>
					{ __( 'Select a CSV file', 'newsletter-optin-box' ) }
				</FormFileUpload>

			</BaseControl>

			<Tip>
				{ __( ' Have a different file type?', 'newsletter-optin-box' ) }&nbsp;
				<Button
					variant="link"
					href="https://convertio.co/csv-converter/"
					target="_blank"
					text={ __( 'Convert it to CSV', 'newsletter-optin-box' ) }
				/>
			</Tip>
		</>
	);
}

export default SelectFile;
