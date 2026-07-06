// Initialize Stripe with your publishable key
// This key is safe to be exposed in your client-side code.
const stripe = Stripe(STRIPE_PUBLISHABLE_KEY);

// Create an instance of Elements
const elements = stripe.elements();

// Create a 'card' element and attach it to the 'card-element' div.
const card = elements.create('card', {
    style: {
        base: {
            iconColor: '#666EE8',
            color: '#313259',
            fontWeight: '300',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSize: '18px',
            '::placeholder': {
                color: '#CFD7DF',
            },
        },
        invalid: {
            iconColor: '#FFC7EE',
            color: '#FFC7EE',
        },
    },
});
card.mount('#card-element');

// Handle real-time validation errors from the card Element.
card.on('change', function(event) {
    const displayError = document.getElementById('card-errors');
    if (event.error) {
        displayError.textContent = event.error.message;
        displayError.style.display = 'block';
    } else {
        displayError.textContent = '';
        displayError.style.display = 'none';
    }
});

// Handle form submission
const paymentForm = document.getElementById('payment-form');
paymentForm.addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent the form from submitting normally

    const payButton = document.getElementById('pay-button');
    payButton.disabled = true; // Disable button to prevent multiple submissions
    payButton.textContent = 'Processing...';

    const paymentMessage = document.getElementById('payment-message');
    paymentMessage.style.display = 'none'; // Hide previous messages

    // Get other form data
    const amount = document.getElementById('amount').value;
    const description = document.getElementById('description').value;

    stripe.createToken(card).then(function(result) {
        if (result.error) {
            // Inform the user if there was an error.
            const errorElement = document.getElementById('card-errors');
            errorElement.textContent = result.error.message;
            errorElement.style.display = 'block';
            payButton.disabled = false;
            payButton.textContent = 'Pay Now';
        } else {
            // Send the token and other data to your server.
            // Replace 'process_payment.php' with your actual backend endpoint.
            fetch('process_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `stripeToken=${result.token.id}&amount=${amount}&description=${description}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    paymentMessage.textContent = 'Payment successful! Transaction ID: ' + data.transactionId;
                    paymentMessage.className = 'success-message';
                    paymentMessage.style.display = 'block';
                    paymentForm.reset(); // Clear the form
                    card.clear(); // Clear the card element
                } else {
                    paymentMessage.textContent = 'Payment failed: ' + data.message;
                    paymentMessage.className = 'error-message';
                    paymentMessage.style.display = 'block';
                }
            })
            .catch(error => {
                paymentMessage.textContent = 'Network error: ' + error.message;
                paymentMessage.className = 'error-message';
                paymentMessage.style.display = 'block';
                console.error('Error:', error);
            })
            .finally(() => {
                payButton.disabled = false;
                payButton.textContent = 'Pay Now';
            });
        }
    });
});

// Expose publishable key to global scope for payment.php to use
window.STRIPE_PUBLISHABLE_KEY = STRIPE_PUBLISHABLE_KEY;
