import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import SignupFormStep from './SignupFormStep';
import NewsletterStep from './NewsletterStep';
import GrowthStep from './GrowthStep';

const WelcomeWizard = () => {
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

  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const currentStep = wizardSteps[currentStepIndex];

  const handleNextStep = () => {
    setCurrentStepIndex(currentStepIndex + 1);
  };

  const handlePreviousStep = () => {
    setCurrentStepIndex(currentStepIndex - 1);
  };

  return (
    <div className="noptin-welcome-wizard">
      <h2>{currentStep.title}</h2>
      <p>{currentStep.description}</p>
      <currentStep.component onNextStep={handleNextStep} onPreviousStep={handlePreviousStep} />
    </div>
  );
};

export default WelcomeWizard;
