/**
 * Wordpress dependancies.
 */
import { Card, CardBody, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Local dependancies.
 */
import Step from './step';
import {wizardSteps} from './constants';

/**
 * Displays the settings wizard.
 *
 * @param {Object} props
 * @param {Function} props.saved
 *
 * @return {JSX.Element}
 */
export default function SettingsWizard( {saved} ) {

	// Index of the current step.
	const [ currentStep, setCurrentStep ] = useState( 0 );

	// Checks if the current step is the last step.
	const isLastStep = currentStep === wizardSteps.length - 1;

	// Checks if the current step is the first step.
	const isFirstStep = currentStep === 0;

	// Retrieves the current step.
	const step = wizardSteps[ currentStep ] ? wizardSteps[ currentStep ] : null;

	return (
		<Card className="noptin-settings-wizard noptin-component__section" style={{maxWidth: "520px"}}>

			{ step && (
				<Step
					onClickNext={ () => setCurrentStep( currentStep + 1 ) }
					onClickBack={ () => setCurrentStep( currentStep - 1 ) }
					isLastStep={ isLastStep }
					isFirstStep={ isFirstStep }
					saved={ saved }
					{ ...step }
				/>
			)}

			{ ! step && (
				<CardBody>
					<h2>{ __( "That's all!", 'newsletter-optin-box' ) }</h2>
					<p>{ __( "You have successfully completed the settings wizard. You can now go to the settings page to customize your opt-in box.", 'newsletter-optin-box' ) }</p>
					<Button
						variant="primary"
						href="https://noptin.com/guide/"
						target="_blank"
					>
						{ __( 'Read Documentation', 'newsletter-optin-box' ) }
					</Button>
				</CardBody>
			)}

		</Card>
	);
}
