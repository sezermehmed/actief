/**
 * Professional Form Handler for Actief Brandbeveiliging B.V.
 * Replaces the old Google Scripts form handler
 * Created: 2025-09-25
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check for URL parameters to show success/error messages
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('success') === '1') {
        showSuccessMessage();
        // Clear the URL parameters
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (urlParams.get('error') === '1') {
        showErrorMessage(urlParams.get('reason'), urlParams.get('msg'));
        // Clear the URL parameters
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Initialize form
    const form = document.getElementById('offerte-form');
    if (form) {
        initializeForm(form);
    }
});

/**
 * Initialize form with validation and submission handling
 */
function initializeForm(form) {
    // Add form token for CSRF protection
    addFormToken(form);

    // Add form validation
    addFormValidation(form);

    // Handle form submission
    form.addEventListener('submit', handleFormSubmission);

    // Add real-time validation
    addRealTimeValidation(form);

    console.log('Professional form handler initialized');
}

/**
 * Add CSRF token to form
 */
function addFormToken(form) {
    const existingToken = form.querySelector('input[name="form_token"]');
    if (!existingToken) {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'form_token';
        tokenInput.value = generateSessionId();
        form.appendChild(tokenInput);
    }
}

/**
 * Generate a session ID for CSRF protection
 */
function generateSessionId() {
    // Simple session ID generation (in production, this should come from server)
    return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
}

/**
 * Add form validation
 */
function addFormValidation(form) {
    const requiredFields = ['company', 'firstname', 'lastname', 'emailaddress', 'phonenumber', 'message'];

    requiredFields.forEach(fieldName => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (field && !field.hasAttribute('required')) {
            field.setAttribute('required', 'required');
        }
    });
}

/**
 * Add real-time validation
 */
function addRealTimeValidation(form) {
    const emailField = form.querySelector('[name="emailaddress"]');
    const phoneField = form.querySelector('[name="phonenumber"]');

    if (emailField) {
        emailField.addEventListener('blur', validateEmail);
    }

    if (phoneField) {
        phoneField.addEventListener('blur', validatePhone);
    }
}

/**
 * Validate email field
 */
function validateEmail(event) {
    const field = event.target;
    const email = field.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (email && !emailRegex.test(email)) {
        showFieldError(field, 'Voer een geldig email adres in');
        return false;
    } else {
        clearFieldError(field);
        return true;
    }
}

/**
 * Validate phone field
 */
function validatePhone(event) {
    const field = event.target;
    const phone = field.value.trim();
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{6,20}$/;

    if (phone && !phoneRegex.test(phone)) {
        showFieldError(field, 'Voer een geldig telefoonnummer in');
        return false;
    } else {
        clearFieldError(field);
        return true;
    }
}

/**
 * Show field error
 */
function showFieldError(field, message) {
    clearFieldError(field);

    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '14px';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;

    field.parentNode.insertBefore(errorDiv, field.nextSibling);
    field.style.borderColor = '#dc3545';
}

/**
 * Clear field error
 */
function clearFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.style.borderColor = '';
}

/**
 * Handle form submission
 */
function handleFormSubmission(event) {
    event.preventDefault();

    const form = event.target;
    const submitButton = form.querySelector('input[type="submit"], button[type="submit"]');

    // Show loading state
    showLoadingState(submitButton);

    // Validate form before submission
    if (!validateForm(form)) {
        hideLoadingState(submitButton);
        return;
    }

    // Submit form using fetch API
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.redirected) {
            // Handle redirect (success or error)
            window.location.href = response.url;
        } else if (response.ok) {
            showSuccessMessage();
            form.reset();
        } else {
            throw new Error('Network response was not ok');
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        showErrorMessage('network', 'Netwerkfout opgetreden');
    })
    .finally(() => {
        hideLoadingState(submitButton);
    });
}

/**
 * Validate entire form
 */
function validateForm(form) {
    let isValid = true;

    // Check required fields
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'Dit veld is verplicht');
            isValid = false;
        }
    });

    // Validate email
    const emailField = form.querySelector('[name="emailaddress"]');
    if (emailField && !validateEmail({target: emailField})) {
        isValid = false;
    }

    // Validate phone
    const phoneField = form.querySelector('[name="phonenumber"]');
    if (phoneField && !validatePhone({target: phoneField})) {
        isValid = false;
    }

    // Check if at least one checkbox is selected
    const checkboxes = form.querySelectorAll('input[type="checkbox"][name="checkbox[]"]');
    if (checkboxes.length > 0) {
        const hasChecked = Array.from(checkboxes).some(cb => cb.checked);
        if (!hasChecked) {
            const checkboxContainer = checkboxes[0].closest('span') || checkboxes[0].parentNode;
            const existingError = checkboxContainer.querySelector('.checkbox-error');
            if (!existingError) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'checkbox-error';
                errorDiv.style.color = '#dc3545';
                errorDiv.style.fontSize = '14px';
                errorDiv.style.marginTop = '10px';
                errorDiv.textContent = 'Selecteer tenminste één dienst';
                checkboxContainer.appendChild(errorDiv);
            }
            isValid = false;
        }
    }

    return isValid;
}

/**
 * Show loading state
 */
function showLoadingState(button) {
    if (button) {
        button.disabled = true;
        button.originalValue = button.value;
        button.value = 'Verzenden...';
        button.style.opacity = '0.6';
    }
}

/**
 * Hide loading state
 */
function hideLoadingState(button) {
    if (button) {
        button.disabled = false;
        button.value = button.originalValue || 'Send';
        button.style.opacity = '1';
    }
}

/**
 * Show success message
 */
function showSuccessMessage() {
    const successDiv = document.getElementById('success-message');
    const errorDiv = document.getElementById('error-message');

    if (successDiv) {
        successDiv.style.display = 'block';
        successDiv.scrollIntoView({ behavior: 'smooth' });
    }

    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

/**
 * Show error message
 */
function showErrorMessage(reason, message) {
    const errorDiv = document.getElementById('error-message');
    const errorDetails = document.getElementById('error-details');
    const successDiv = document.getElementById('success-message');

    if (errorDiv) {
        errorDiv.style.display = 'block';

        if (errorDetails) {
            let errorText = 'Er is een fout opgetreden bij het verzenden van uw aanvraag.';

            switch(reason) {
                case 'rate_limit':
                    errorText = 'U heeft recentelijk al een aanvraag verstuurd. Wacht even voordat u opnieuw probeert.';
                    break;
                case 'spam':
                    errorText = 'Uw aanvraag werd gemarkeerd als spam. Neem direct contact met ons op.';
                    break;
                case 'validation':
                    errorText = message || 'Sommige velden zijn niet correct ingevuld. Controleer uw gegevens.';
                    break;
                case 'email_send':
                    errorText = 'Er is een probleem opgetreden bij het verzenden. Probeer het later opnieuw of neem direct contact met ons op via telefoon.';
                    break;
                default:
                    errorText = message || errorText;
            }

            errorDetails.textContent = errorText;
        }

        errorDiv.scrollIntoView({ behavior: 'smooth' });
    }

    if (successDiv) {
        successDiv.style.display = 'none';
    }
}

// Utility function to clear all error messages
function clearAllErrors() {
    const fieldErrors = document.querySelectorAll('.field-error');
    fieldErrors.forEach(error => error.remove());

    const checkboxErrors = document.querySelectorAll('.checkbox-error');
    checkboxErrors.forEach(error => error.remove());

    const successDiv = document.getElementById('success-message');
    const errorDiv = document.getElementById('error-message');

    if (successDiv) successDiv.style.display = 'none';
    if (errorDiv) errorDiv.style.display = 'none';
}