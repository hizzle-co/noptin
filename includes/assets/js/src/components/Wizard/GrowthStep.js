import { __ } from '@wordpress/i18n';

function GrowthStep(props) {
  return (
    <div className="noptin-welcome-wizard-growth">
      <h2>{__('Step 3: Grow Your List', 'noptin')}</h2>
      <p>{__('Here are some tips for growing your email list and engaging with your subscribers:', 'noptin')}</p>
      <ul>
        <li>{__('Offer a valuable lead magnet to encourage sign-ups, such as an e-book or a free course.', 'noptin')}</li>
        <li>{__('Include social sharing buttons in your emails to encourage your subscribers to share your content.', 'noptin')}</li>
        <li>{__('Send a welcome email to new subscribers to introduce yourself and your business.', 'noptin')}</li>
        <li>{__('Provide exclusive content to your subscribers, such as special discounts or early access to new products.', 'noptin')}</li>
      </ul>
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

export default GrowthStep;
