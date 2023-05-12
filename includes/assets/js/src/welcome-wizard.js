import { StrictMode } from 'react';
import { createRoot } from 'react-dom';
import { useState } from 'react'; 
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import SignupFormStep from './components/Wizard/SignupFormStep';
import NewsletterStep from './components/Wizard/NewsletterStep';
import GrowthStep from './components/Wizard/GrowthStep';
import './components/Wizard/wizard.css';

domReady(() => {
  // Check if we're on the WordPress admin dashboard
  if (document.body.classList.contains('wp-admin')) {
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

    // Define the values for noptinApiSettings
    const noptinApiSettings = {
      root: 'https://your-api-url.com/', // Replace with API URL
      nonce: 'your-nonce-value', // Replace with appropriate nonce value
    };


    // Define the welcome wizard component
    function NoptinWelcomeWizard() {
      const [currentStepIndex, setCurrentStepIndex] = useState(0);
      const currentStep = wizardSteps[currentStepIndex];
      const totalSteps = wizardSteps.length;
      const progress = ((currentStepIndex + 1) / totalSteps) * 100;

      function handleNextStep() {
        setCurrentStepIndex(currentStepIndex + 1);

        // Prepare progress data
        const progressData = {
          step: currentStepIndex + 1,
        };

        // Make AJAX call to save progress data
        fetch(noptinApiSettings.root + 'noptin/v1/welcome-wizard/progress', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': noptinApiSettings.nonce,
          },
          body: JSON.stringify(progressData),
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error('Network response was not ok');
            }
            return response.json();
          })
          .then((data) => {
            console.log('Progress data saved:', data);
          })
          .catch((error) => {
            console.error('Error saving progress:', error);
          });
      }

      function handlePreviousStep() {
        setCurrentStepIndex(currentStepIndex - 1);
      }

      // Return the welcome wizard component, including the current step and progress bar
      return (
        <div className="noptin-welcome-wizard">
          <h2 className="wizard-title">{currentStep.title}</h2>
          <p className="wizard-description">{currentStep.description}</p>
          <div className="wizard-progress">
            <div className="wizard-progress-bar" style={{ width: `${progress}%` }}></div>
          </div>
          <currentStep.component
            onNextStep={handleNextStep}
            onPreviousStep={handlePreviousStep}
          />
          <div className="wizard-buttons">
            <button
              className="wizard-button"
              onClick={handlePreviousStep}
              disabled={currentStepIndex === 0}
            >
              {__('Back', 'noptin')}
            </button>
            <button
              className={`wizard-button ${currentStepIndex === totalSteps - 1 ? 'wizard-button-primary' : ''}`}
              onClick={currentStepIndex === totalSteps - 1 ? handleFinishButtonClick : handleNextStep}
              disabled={currentStepIndex === totalSteps - 1}
            >
              {currentStepIndex === totalSteps - 1 ? __('Finish', 'noptin') : __('Next', 'noptin')}
            </button>
          </div>
        </div>
      );
    }

    function noptin_welcome_wizard_init() {
      // Get the "noptin_welcome_wizard_completed" option value from the WP database
      const completed = localStorage.getItem('noptin_welcome_wizard_completed') === 'true';

      // If the user hasn't completed the welcome wizard, render the wizard component
      if (!completed) {
        const rootElement = document.querySelector('#noptin-welcome-wizard');
        if (rootElement) {
          const root = createRoot(rootElement);
          root.render(
            <StrictMode>
              <NoptinWelcomeWizard />
            </StrictMode>
          );
        }
      }
    }

    // When the DOM is ready, initialize the welcome wizard if it hasn't been completed
    domReady(() => {
      noptin_welcome_wizard_init();
    });

    function mark_welcome_wizard_as_completed() {
      // Make AJAX call to save progress data
      fetch(noptinApiSettings.root + 'noptin/v1/welcome-wizard/progress', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': noptinApiSettings.nonce,
        },
        body: JSON.stringify(progressData),
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then((data) => {
          console.log('Progress data saved:', data);
          // Make AJAX call to mark welcome wizard as completed
          fetch(noptinApiSettings.root + 'noptin/v1/welcome-wizard/completed', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': noptinApiSettings.nonce,
            },
          })
            .then((response) => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then((data) => {
              console.log('Welcome wizard marked as completed:', data);
            })
            .catch((error) => {
              console.error('Error marking welcome wizard as completed:', error);
            });
        })
        .catch((error) => {
          console.error('Error saving progress:', error);
        });
    }

    function handleFinishButtonClick() {
      const progressData = {
        step: wizardSteps.length,
      };

      mark_welcome_wizard_as_completed();
    }
  }
});
