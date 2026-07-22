document.addEventListener('DOMContentLoaded', function() {
    // Fix 1: Explicitly retrieve `stripe_publishable_key` from a data attribute on the payment form.
    // This makes its source visible and ensures robustness against implicit global reliance.
    // Fix 1: Renamed variable `stripe_publishable_key` to `stripePublishableKey` to conform to JavaScript's camelCase naming convention.
    const stripePublishableKey = document.getElementById('payment-form').dataset.stripePublishableKey;
    // Initialize Stripe.js with your publishable key
    const stripe = Stripe(stripePublishableKey); // stripe_publishable_key is passed from payment.php

    // Create an instance of Elements
    const elements = stripe.elements();

    // Create a Card Element and mount it to the #card-element div
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a',
            },
        },
    });
    cardElement.mount('#card-element');

    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const cardErrors = document.getElementById('card-errors');

    // [Fix 3] Placeholder function for integrating with a centralized error monitoring service (e.g., Sentry, Bugsnag).
    // This allows proactively identifying and addressing frontend issues in production.
    function logClientError(error, context = {}) {
        // In a production environment, this would send 'error' and 'context' to a service.
        // Example: Sentry.captureException(error, { extra: context });
        console.warn('CLIENT ERROR LOG (production simulation):', error, context);
    }

    // Handle real-time validation errors from the card Element.
    cardElement.on('change', function(event) {
        if (event.error) {
            cardErrors.textContent = event.error.message;
        } else {
            cardErrors.textContent = '';
        }
        submitButton.disabled = !event.complete; // Disable button if card element is not complete
    });

    // Handle form submission
    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        // Disable the button to prevent multiple submissions
        submitButton.disabled = true;
        // [Fix 2] Add a unicode hourglass icon for a more prominent visual loading indicator,
        // and disable all other form inputs to prevent user interaction and ensure clarity during processing.
        submitButton.textContent = 'Processing... \u231B'; // Hourglass icon
        for (let i = 0; i < form.elements.length; i++) {
            const element = form.elements[i];
            if (element !== submitButton) { // Exclude the submit button itself, which is already disabled
                element.disabled = true;
            }
        }

        const customerId = document.getElementById('customer-id').value;

        // Fix 3: Uncommented and populated `billing_details.name` and `billing_details.email` from form inputs.
        // This is important for fraud prevention, compliance, and dispute resolution.
        const customerNameInput = document.getElementById('customer-name');
        const customerEmailInput = document.getElementById('customer-email');
        const customerName = customerNameInput ? customerNameInput.value : '';
        const customerEmail = customerEmailInput ? customerEmailInput.value : '';

        // Create PaymentMethod with card details and customer info
        const { paymentMethod, error } = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                // If you have customer name/email, pass them here
                name: customerName,
                email: customerEmail,
            },
        });

        if (error) {
            // Display error.message in your UI
            cardErrors.textContent = error.message;
            // [Fix 2] Re-enable form inputs and reset button on error, allowing the user to retry.
            for (let i = 0; i < form.elements.length; i++) {
                const element = form.elements[i];
                if (element !== submitButton) {
                    element.disabled = false;
                }
            }
            submitButton.disabled = false;
            submitButton.textContent = 'Pay Now';
        } else {
            // Fix 2: Replace form.submit() with an asynchronous fetch request to wait for server
            // response. This ensures server-side validation/processing is acknowledged,
            // improving robustness and user feedback.
            document.getElementById('payment-method-id').value = paymentMethod.id;

            // Prepare form data including all original form fields for submission
            const formData = new FormData(form);

            try {
                // Send the payment method ID and other form data to the server
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                });

                let result;
                // Fix 2: Wrapped `response.json()` in a try...catch block to handle cases where
                // the server might return a non-JSON response (e.g., HTML error page), preventing a crash.
                try {
                    result = await response.json();
                } catch (jsonError) {
                    logClientError(jsonError, { component: 'payment-form-submission', context: 'json-parse-error', status: response.status });
                    console.error('Error parsing server response as JSON:', jsonError, 'Status:', response.status, 'Response text:', await response.text());
                    cardErrors.textContent = 'Received an unexpected response from the server. Please try again.';
                    // Re-enable form inputs and reset button on JSON parsing error
                    for (let i = 0; i < form.elements.length; i++) {
                        const element = form.elements[i];
                        if (element !== submitButton) {
                            element.disabled = false;
                        }
                    }
                    submitButton.disabled = false;
                    submitButton.textContent = 'Pay Now';
                    return; // Stop further processing if JSON parsing failed
                }

                if (result.success) {
                    // Payment successful, redirect to a success page or display confirmation
                    // [Fix 1] Removed hardcoded fallback URL. The server should explicitly dictate
                    // the redirection path (e.g., `result.redirectUrl`) for dynamic flow control
                    // and different success scenarios.
                    window.location.href = result.redirectUrl;
                } else {
                    // Payment failed, display the error message from the server
                    cardErrors.textContent = result.message || 'Payment failed, please try again.';
                    // [Fix 2] Re-enable form inputs and reset button on payment failure, allowing the user to retry.
                    for (let i = 0; i < form.elements.length; i++) {
                        const element = form.elements[i];
                        if (element !== submitButton) {
                            element.disabled = false;
                        }
                    }
                    submitButton.disabled = false;
                    submitButton.textContent = 'Pay Now';
                }
            } catch (fetchError) {
                // Handle network errors or issues parsing server response
                // [Fix 3] Log client-side network errors to a centralized error monitoring service
                // for proactive issue identification in production environments.
                logClientError(fetchError, { component: 'payment-form-submission', context: 'network-error' });
                console.error('Error processing payment:', fetchError);
                cardErrors.textContent = 'An unexpected error occurred. Please try again.';
                // [Fix 2] Re-enable form inputs and reset button on network error, allowing the user to retry.
                for (let i = 0; i < form.elements.length; i++) {
                    const element = form.elements[i];
                    if (element !== submitButton) {
                        element.disabled = false;
                    }
                }
                submitButton.disabled = false;
                submitButton.textContent = 'Pay Now';
            }
        }
    });
});