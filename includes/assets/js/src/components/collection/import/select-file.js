import { FormFileUpload, Tip, Button, __experimentalText as Text } from "@wordpress/components";
import { upload, Icon } from "@wordpress/icons";
import { __ } from "@wordpress/i18n";
import styled from '@emotion/styled';
import { BlockButton } from "../../styled-components";

// Wrapper div.
const WrapperDiv = styled.div`
	margin: 1.6rem 0;
	max-width: 600px;
`;

// Button.
const UploadButton = BlockButton.withComponent( FormFileUpload );

/**
 * Displays the file input.
 *
 * @param {Object} props
 */
const SelectFile = ( { onUpload } ) => {

	return (
		<WrapperDiv>

				<Text weight={ 600 } as="h3">
					{ __( 'This tool allows you to import existing records from a CSV file.', 'newsletter-optin-box' ) }
				</Text>

				<UploadButton
					accept="text/csv"
					onChange={ ( event ) => onUpload( event.currentTarget.files[0] ) }
					variant="primary"
				>
					<Icon icon={upload} />
					{ __( 'Select a CSV file', 'newsletter-optin-box' ) }
				</UploadButton>

				<Tip>
					{ __( 'The first row of the CSV file should contain the field names/headers.', 'newsletter-optin-box' ) }
					<br />
					{ __( ' Have a different file type?', 'newsletter-optin-box' ) }&nbsp;
					<Button
						variant="link"
						href="https://convertio.co/csv-converter/"
						target="_blank"
						text={ __( 'Convert it to CSV', 'newsletter-optin-box' ) }
					/>
				</Tip>
		</WrapperDiv>
	);
}

export default SelectFile;
