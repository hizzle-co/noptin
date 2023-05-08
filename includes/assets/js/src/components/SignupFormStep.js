import { __ } from '@wordpress/i18n';

function SignupFormStep(props) {
  return (
    <div className="noptin-welcome-wizard-signup-form">
      <h2>{__('Step 1: Configure Your Signup Form', 'noptin')}</h2>
      <p>{__('Use the form below to create and customize your signup form.', 'noptin')}</p>
      {/* Adding signup form code here */}
      <div className="noptin-welcome-wizard-buttons">
        <button className="noptin-welcome-wizard-button" onClick={props.onPreviousStep}>
          {__('Back', 'noptin')}
        </button>
        <button className="noptin-welcome-wizard-button noptin-welcome-wizard-button-primary" onClick={props.onNextStep}>
          {__('Next', 'noptin')}
        </button>
      </div>
    </div>
  );
}

export default SignupFormStep;
