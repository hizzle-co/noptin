import { __ } from '@wordpress/i18n';

function NewsletterStep(props) {
  return (
    <div className="noptin-welcome-wizard-newsletter">
      <h2>{__('Step 2: Create Your First Newsletter', 'noptin')}</h2>
      <p>{__('Use the form below to create and send your first email newsletter to your subscribers.', 'noptin')}</p>
      {/* Add code for creating and sending newsletters here */}
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

export default NewsletterStep;
