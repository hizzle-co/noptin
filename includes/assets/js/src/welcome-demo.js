import domReady from '@wordpress/dom-ready';
import {render, createRoot, StrictMode} from '@wordpress/element';
import {__} from '@wordpress/i18n';

domReady(() => {
  const wizardSteps = [
  //  wizard steps here
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
        {/* noptin welcome wizard here */}
      </div>
    );
  }

  // Define functional components for each step of the wizard
  function SignupFormStep(props) {
    return (
        <div className="noptin-welcome-wizard-signup-form">
        {/* signupform step code here */}
      </div>
    );
  }

  function ConfirmationStep(props) {
    return (
        <div className="noptin-welcome-wizard-confirmation">
          {/* confirmation step code here */}
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
