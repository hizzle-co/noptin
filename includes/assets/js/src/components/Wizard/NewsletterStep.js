import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';

function NewsletterStep(props) {
  const [showModal, setShowModal] = useState(false);

  const handleCreateNewsletter = () => {
    // Code for creating and sending the newsletter
    // Replace this with your actual implementation

    // Show the modal
    setShowModal(true);

    // Close the modal after 2 seconds
    setTimeout(() => {
      setShowModal(false);
    }, 2000);
  };

  return (
    <div className="noptin-welcome-wizard-newsletter">
      <h2>{__('Step 2: Create Your First Newsletter', 'noptin')}</h2>
      <p>{__('Use the form below to create and send your first email newsletter to your subscribers.', 'noptin')}</p>
      <button className="noptin-welcome-wizard-button" onClick={props.onPreviousStep}>
        {__('Back', 'noptin')}
      </button>
      <button className="noptin-welcome-wizard-button noptin-welcome-wizard-button-primary" onClick={handleCreateNewsletter}>
        {__('Create and Send Newsletter', 'noptin')}
      </button>
      <div className="noptin-welcome-wizard-buttons">
        <button className="noptin-welcome-wizard-button" onClick={props.onPreviousStep}>
          {__('Back', 'noptin')}
        </button>
        <button className="noptin-welcome-wizard-button noptin-welcome-wizard-button-primary" onClick={props.onNextStep}>
          {__('Next', 'noptin')}
        </button>
      </div>
      {showModal && (
        <div className="modal">
          <div className="modal-content">
            <p>{__('Newsletter created and sent!', 'noptin')}</p>
          </div>
        </div>
      )}
    </div>
  );
}

export default NewsletterStep;
