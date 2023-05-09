import { StrictMode } from 'react';
import { createRoot } from 'react-dom';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import SignupFormStep from './components/SignupFormStep';
import NewsletterStep from './components/NewsletterStep';
import GrowthStep from './components/GrowthStep';

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
    const totalSteps = wizardSteps.length;
    const progress = ((currentStepIndex + 1) / totalSteps) * 100;
  
    function handleNextStep() {
      setCurrentStepIndex(currentStepIndex + 1);
    }
  
    function handlePreviousStep() {
      setCurrentStepIndex(currentStepIndex - 1);
    }
  
    // Return the welcome wizard component, including the current step and progress bar
    return (
      <div className="noptin-welcome-wizard">
        <h2>{currentStep.title}</h2>
        <p>{currentStep.description}</p>
        <div className="noptin-welcome-wizard-progress">
          <div className="noptin-welcome-wizard-progress-bar" style={{ width: `${progress}%` }}></div>
        </div>
        <currentStep.component
          onNextStep={handleNextStep}
          onPreviousStep={handlePreviousStep}
        />
        <div className="noptin-welcome-wizard-buttons">
          <button className="noptin-welcome-wizard-button" onClick={handlePreviousStep} disabled={currentStepIndex === 0}>
            {__('Back', 'noptin')}
          </button>
          <button className="noptin-welcome-wizard-button noptin-welcome-wizard-button-primary" onClick={handleNextStep} disabled={currentStepIndex === totalSteps - 1}>
            {currentStepIndex === totalSteps - 1 ? __('Finish', 'noptin') : __('Next', 'noptin')}
          </button>
        </div>
      </div>
    );
  }
  
  // Render the welcome wizard component
  const rootElement = document.querySelector('#noptin-welcome-wizard');
  if (rootElement) {
    const root = createRoot(rootElement);
    root.render(
      <StrictMode>
        <NoptinWelcomeWizard />
      </StrictMode>
    );
  }
});
