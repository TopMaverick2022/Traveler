document.addEventListener('DOMContentLoaded', function() {
    // Initialize Stripe.js with your publishable key
    const stripe = Stripe(stripe_publishable_key); // stripe_publishable_key is passed from payment.php

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
        submitButton.textContent = 'Processing...';

        const customerId = document.getElementById('customer-id').value;

        // Create PaymentMethod with card details and customer info
        const { paymentMethod, error } = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                // If you have customer name/email, pass them here
                // name: 'Jane Doe',
                // email: 'jane.doe@example.com',
            },
        });

        if (error) {
            // Display error.message in your UI
            cardErrors.textContent = error.message;
            submitButton.disabled = false;
            submitButton.textContent = 'Pay Now';
        } else {
            // Send the payment_method_id to your server
            document.getElementById('payment-method-id').value = paymentMethod.id;
            form.submit();
        }
    });
});
