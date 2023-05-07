import domReady from '@wordpress/dom-ready';
import {render, createRoot, StrictMode} from '@wordpress/element';
import {__} from '@wordpress/i18n';

domReady(() => {
  const wizardSteps = [
    {
      title: __('Step 1: Configure Your Signup Form', 'noptin'),
      description: __('Add and customize a signup form to start collecting email addresses.', 'noptin'),
      component: SignupFormStep,
    },
    {
      title: __('Step 2: Create Your First Newsletter', 'noptin'),
      description: __('Create and send your first email newsletter to your subscribers.', 'noptin'),
      component: NewsletterStep,
    },
    {
      title: __('Step 3: Grow Your List', 'noptin'),
      description: __('Learn how to grow your email list and engage with your subscribers.', 'noptin'),
      component: GrowthStep,
    },
  ];

  // Define a functional component for the welcome wizard
  function WelcomeWizard() {
    // Use the `useState` hook to track the current step index
    const [currentStepIndex, setCurrentStepIndex] = useState(0);
    // Use the current step index to retrieve the current step object
    const currentStep = wizardSteps[currentStepIndex];

    // Define functions to handle advancing to the next step or going back to the previous step
    function handleNextStep() {
      setCurrentStepIndex(currentStepIndex + 1);
    }

    function handlePreviousStep() {
      setCurrentStepIndex(currentStepIndex - 1);
    }

    // Return the welcome wizard component, including the current step
    return (
      <div className="noptin-welcome-wizard">
        <h2>{currentStep.title}</h2>
        {currentStep.content}
        <button onClick={handlePreviousStep}>{__('Previous', 'text-domain')}</button>
        <button onClick={handleNextStep}>{__('Next', 'text-domain')}</button>
      </div>
    );
  }

  // Define functional components for each step of the wizard
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

  function ConfirmationStep(props) {
    return (
        <div className="noptin-welcome-wizard-confirmation">
        <h2>{__('Step 2: Confirm Your Settings', 'noptin')}</h2>
        <p>{__('Please review the settings you have configured before proceeding.', 'noptin')}</p>
        {/* Adding a summary of the settings here */}
        <div className="noptin-welcome-wizard-buttons">
          <button className="noptin-welcome-wizard-button" onClick={props.onPreviousStep}>
            {__('Back', 'noptin')}
          </button>
          <button className="noptin-welcome-wizard-button noptin-welcome-wizard-button-primary" onClick={props.onFinish}>
            {__('Finish', 'noptin')}
          </button>
        </div>
      </div>
    );
  }
  
  // Mount the welcome wizard component to the DOM
  const rootElement = document.getElementById('root');
  if (rootElement) {   
    render(
      <StrictMode>
        <WelcomeWizard />
      </StrictMode>,
      rootElement
    );
  }

});
