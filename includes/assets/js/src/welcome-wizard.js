import domReady from '@wordpress/dom-ready';
import { render, createRoot, StrictMode } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import WelcomeWizard from './components/WelcomeWizard';

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

  // Define the welcome wizard component
  function NoptinWelcomeWizard() {
    const [currentStepIndex, setCurrentStepIndex] = useState(0);
    const currentStep = wizardSteps[currentStepIndex];

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
        <p>{currentStep.description}</p>
        <currentStep.component
          onNextStep={handleNextStep}
          onPreviousStep={handlePreviousStep}
        />
      </div>
    );
  }

  // Render the welcome wizard component
  const root = createRoot(document.querySelector('#noptin-welcome-wizard'));
  root.render(
    <StrictMode>
      <NoptinWelcomeWizard />
    </StrictMode>
  );
});
