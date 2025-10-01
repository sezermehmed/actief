/**
 * Modern Form Submission Handler
 * Enhanced with ES6+ features and better error handling
 */

class FormHandler {
  constructor() {
    this.init();
  }

  init() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.bindEvents());
    } else {
      this.bindEvents();
    }
  }

  bindEvents() {
    // Bind to all forms with gform class
    const forms = document.querySelectorAll('form.gform');
    forms.forEach(form => {
      form.addEventListener('submit', (event) => this.handleFormSubmit(event));

      // Add real-time validation
      this.addRealTimeValidation(form);

      // Add accessibility improvements
      this.enhanceFormAccessibility(form);
    });
  }

  /**
   * Enhanced form data extraction with better error handling
   */
  getFormData(form) {
    const elements = form.elements;
    let honeypot = null;

    // Convert HTMLCollection to array and process
    const fields = Array.from(elements)
      .filter(element => {
        if (element.name === 'honeypot') {
          honeypot = element.value;
          return false;
        }
        return element.name && element.name.trim() !== '';
      })
      .map(element => element.name)
      .filter((item, pos, self) => self.indexOf(item) === pos);

    const formData = {};
    fields.forEach(name => {
      const element = elements[name];

      // Handle different input types
      if (element.type === 'checkbox' || element.type === 'radio') {
        if (element.checked) {
          formData[name] = element.value;
        }
      } else if (element.length && element.length > 1) {
        // Handle multiple elements with same name
        const values = Array.from(element)
          .filter(item => item.checked || item.selected || (item.type !== 'checkbox' && item.type !== 'radio'))
          .map(item => item.value);
        formData[name] = values.join(', ');
      } else {
        formData[name] = element.value || '';
      }
    });

    // Add form metadata
    formData.formDataNameOrder = JSON.stringify(fields);
    formData.formGoogleSheetName = form.dataset.sheet || 'responses';
    formData.formGoogleSendEmail = form.dataset.email || '';
    formData.timestamp = new Date().toISOString();

    return { data: formData, honeypot };
  }

  /**
   * Enhanced form submission with better UX and error handling
   */
  async handleFormSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const submitButton = form.querySelector('[type="submit"]');

    try {
      // Show loading state
      this.setFormState(form, 'loading');

      const formData = this.getFormData(form);
      const { data, honeypot } = formData;

      // Anti-spam check
      if (honeypot) {
        console.warn('Spam detected: honeypot field filled');
        this.setFormState(form, 'error', 'Er is een probleem opgetreden. Probeer het later opnieuw.');
        return;
      }

      // Validate form data
      const validationResult = this.validateFormData(data);
      if (!validationResult.isValid) {
        this.setFormState(form, 'error', validationResult.message);
        return;
      }

      // Submit form with modern fetch API
      const response = await this.submitForm(form, data);

      if (response.ok) {
        this.setFormState(form, 'success');
        form.reset();

        // Hide form and show thank you message
        const formElements = form.querySelector('.form-elements');
        const thankYouMessage = form.querySelector('.thankyou_message');

        if (formElements) formElements.style.display = 'none';
        if (thankYouMessage) thankYouMessage.style.display = 'block';

        // Send analytics event if available
        if (typeof gtag === 'function') {
          gtag('event', 'form_submit', {
            event_category: 'engagement',
            event_label: form.id || 'contact_form'
          });
        }
      } else {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
    } catch (error) {
      console.error('Form submission error:', error);
      this.setFormState(form, 'error', 'Er is een probleem opgetreden. Controleer uw internetverbinding en probeer het opnieuw.');
    }
  }

  /**
   * Submit form using modern fetch API
   */
  async submitForm(form, data) {
    const url = form.action;
    const encoded = Object.keys(data)
      .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(data[key])}`)
      .join('&');

    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: encoded
    });
  }

  /**
   * Form validation
   */
  validateFormData(data) {
    const errors = [];

    // Required field validation
    const requiredFields = ['name', 'email'];
    requiredFields.forEach(field => {
      if (!data[field] || data[field].trim() === '') {
        errors.push(`${field} is verplicht`);
      }
    });

    // Email validation
    if (data.email && !this.isValidEmail(data.email)) {
      errors.push('Voer een geldig e-mailadres in');
    }

    return {
      isValid: errors.length === 0,
      message: errors.join(', ')
    };
  }

  isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  /**
   * Set form state (loading, success, error)
   */
  setFormState(form, state, message = '') {
    const submitButton = form.querySelector('[type="submit"]');
    const messageContainer = form.querySelector('.form-message') || this.createMessageContainer(form);

    // Reset previous states
    form.classList.remove('form-loading', 'form-success', 'form-error');
    messageContainer.textContent = '';
    messageContainer.className = 'form-message';

    switch (state) {
      case 'loading':
        form.classList.add('form-loading');
        if (submitButton) {
          submitButton.disabled = true;
          submitButton.textContent = 'Verzenden...';
        }
        break;

      case 'success':
        form.classList.add('form-success');
        messageContainer.classList.add('success');
        messageContainer.textContent = 'Bedankt! Uw bericht is succesvol verzonden.';
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.textContent = 'Verzonden!';
          setTimeout(() => {
            submitButton.textContent = 'Verzenden';
          }, 3000);
        }
        break;

      case 'error':
        form.classList.add('form-error');
        messageContainer.classList.add('error');
        messageContainer.textContent = message;
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.textContent = 'Verzenden';
        }
        break;
    }
  }

  createMessageContainer(form) {
    const container = document.createElement('div');
    container.className = 'form-message';
    container.setAttribute('role', 'alert');
    container.setAttribute('aria-live', 'polite');
    form.appendChild(container);
    return container;
  }

  /**
   * Add real-time form validation
   */
  addRealTimeValidation(form) {
    const inputs = form.querySelectorAll('input, textarea, select');

    inputs.forEach(input => {
      input.addEventListener('blur', () => {
        this.validateField(input);
      });

      input.addEventListener('input', () => {
        // Clear previous error state on input
        input.classList.remove('error');
        const errorMsg = input.parentNode.querySelector('.field-error');
        if (errorMsg) errorMsg.remove();
      });
    });
  }

  validateField(field) {
    const value = field.value.trim();
    let errorMessage = '';

    // Check if required field is empty
    if (field.required && !value) {
      errorMessage = `${this.getFieldLabel(field)} is verplicht`;
    }

    // Email validation
    if (field.type === 'email' && value && !this.isValidEmail(value)) {
      errorMessage = 'Voer een geldig e-mailadres in';
    }

    // Show/hide error
    if (errorMessage) {
      this.showFieldError(field, errorMessage);
    } else {
      this.hideFieldError(field);
    }
  }

  getFieldLabel(field) {
    const label = field.parentNode.querySelector('label');
    return label ? label.textContent.replace('*', '').trim() : field.name;
  }

  showFieldError(field, message) {
    field.classList.add('error');

    let errorElement = field.parentNode.querySelector('.field-error');
    if (!errorElement) {
      errorElement = document.createElement('div');
      errorElement.className = 'field-error';
      errorElement.setAttribute('role', 'alert');
      field.parentNode.appendChild(errorElement);
    }

    errorElement.textContent = message;
  }

  hideFieldError(field) {
    field.classList.remove('error');
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
      errorElement.remove();
    }
  }

  /**
   * Enhance form accessibility
   */
  enhanceFormAccessibility(form) {
    const inputs = form.querySelectorAll('input, textarea, select');

    inputs.forEach(input => {
      // Add aria-required for required fields
      if (input.required) {
        input.setAttribute('aria-required', 'true');
      }

      // Associate labels with inputs
      const label = input.parentNode.querySelector('label');
      if (label && !input.id) {
        const id = `field_${Math.random().toString(36).substr(2, 9)}`;
        input.id = id;
        label.setAttribute('for', id);
      }
    });
  }
}

// Initialize the form handler
new FormHandler();