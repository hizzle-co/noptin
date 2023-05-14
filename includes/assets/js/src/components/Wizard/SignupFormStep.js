import { __ } from '@wordpress/i18n';
import React, { useState, useEffect } from 'react';

const SignupFormStep = (props) => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');

  useEffect(() => {
    // Load form data from localStorage on component mount
    const storedName = localStorage.getItem('signupName');
    const storedEmail = localStorage.getItem('signupEmail');

    if (storedName) {
      setName(storedName);
    }

    if (storedEmail) {
      setEmail(storedEmail);
    }
  }, []);

  const handleNameChange = (event) => {
    setName(event.target.value);
  };

  const handleEmailChange = (event) => {
    setEmail(event.target.value);
  };

  const handleSubmit = (event) => {
    event.preventDefault();

    // Save form data to localStorage
    localStorage.setItem('signupName', name);
    localStorage.setItem('signupEmail', email);

    // any additional form submission logic here    
  };

  return (
    <div className="noptin-welcome-wizard-signup-form">
      <h2>{__('Step 1: Configure Your Signup Form', 'noptin')}</h2>
      <p>{__('Use the form below to create and customize your signup form.', 'noptin')}</p>
      <form onSubmit={handleSubmit}>
        <label>
          {__('Name:', 'noptin')}
          <input type="text" name="name" value={name} onChange={handleNameChange} />
        </label>
        <label>
          {__('Email:', 'noptin')}
          <input type="email" name="email" value={email} onChange={handleEmailChange} />
        </label>
        <button type="submit">{__('Sign Up', 'noptin')}</button>
      </form>
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
};

export default SignupFormStep;
