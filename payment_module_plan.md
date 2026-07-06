# Traveler Project: Payment System Integration Plan

**Feature Goal:** Implement a secure and reliable payment processing system for the Traveler project to handle booking payments.

**Selected Payment Gateway:** Stripe

**Reasoning:** Stripe offers robust APIs, excellent documentation, strong security features (client-side tokenization minimizes PCI scope), and competitive fees. Its PHP library is well-maintained and widely used.

## 1. Project Setup & Dependency Management

*   **Initialize Composer:**
    *   Run `composer init` in the project root (`traveler/`).
    *   This will create `composer.json`.
*   **Install Stripe PHP Library:**
    *   Add `stripe/stripe-php` as a dependency.
    *   Run `composer require stripe/stripe-php`.
    *   This will download the library and generate `vendor/autoload.php` and `composer.lock`.
*   **PHP Environment Check:**
    *   Ensure the following PHP extensions are enabled (usually via `php.ini`):
        *   `php_openssl` (for secure communication)
        *   `php_curl` (for making HTTP requests to Stripe API)
        *   `php_json` (for handling JSON responses from Stripe)

## 2. Configuration (`config.php`)

*   Create `config.php` at the project root.
*   Define constants for:
    *   `STRIPE_PUBLISHABLE_KEY` (client-side public key)
    *   `STRIPE_SECRET_KEY` (server-side secret key - **CRITICAL: NEVER expose in client-side code, use environment variables in production**)
    *   `STRIPE_WEBHOOK_SECRET` (for verifying webhook events)
    *   `PAYMENT_LOG_FILE`, `WEBHOOK_LOG_FILE` (paths for flat-file logging)
*   Include a basic `log_message` function for flat-file logging due to no existing database.
*   Create a `logs/` directory and ensure it's writable.

## 3. Frontend Payment Form Implementation (`payment.php`, `css/payment.css`, `js/payment.js`)

*   **`payment.php` (HTML Structure):**
    *   Create a new PHP file `payment.php` (or integrate into `booking.php` if appropriate).
    *   Include `config.php`.
    *   Render an HTML form for collecting payment details: amount, description, and a `div` for the Stripe Card Element.
    *   Include Stripe.js (`https://js.stripe.com/v3/`) and `js/payment.js`.
    *   Pass `STRIPE_PUBLISHABLE_KEY` from `config.php` to the client-side JavaScript.
*   **`css/payment.css` (Styling):**
    *   Create `css/payment.css` for styling the payment form and Stripe elements.
    *   Ensure it integrates with the existing `css/style.css`.
*   **`js/payment.js` (Client-Side Logic):**
    *   Initialize Stripe with `STRIPE_PUBLISHABLE_KEY`.
    *   Create and mount a Stripe Card Element into the designated `div` in `payment.php`.
    *   Implement client-side validation using Stripe.js built-in features.
    *   On form submission, use `stripe.createToken(card)` to securely tokenize card details.
    *   Send the generated `stripeToken.id`, amount, and description via `POST` to `process_payment.php`.
    *   Handle success/error messages from the backend and display them to the user.

## 4. Backend Endpoint for Payment Processing (`process_payment.php`)

*   Create `process_payment.php` at the project root.
*   Include `vendor/autoload.php` and `config.php`.
*   Set Stripe API key using `\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY)`.
*   Receive `stripeToken`, `amount`, and `description` from the frontend POST request.
*   **Server-Side Validation:** Validate the `amount` and `stripeToken`.
*   **Charge Creation:** Use `\Stripe\Charge::create()` with the token, amount (converted to cents), currency (`usd`), and description.
*   **Error Handling:** Implement comprehensive `try-catch` blocks for various Stripe exceptions (`CardException`, `ApiErrorException`, etc.) and general PHP exceptions.
*   **Transaction Logging:** Log successful and failed transactions to `logs/payment_transactions.log` including Charge ID, amount, status, and any error messages.
*   Return a JSON response to the frontend indicating success or failure, along with a message and transaction ID.

## 5. Webhook Handler Implementation (`webhook_handler.php`)

*   Create `webhook_handler.php` at the project root.
*   Include `vendor/autoload.php` and `config.php`.
*   Set Stripe API key.
*   **Signature Verification:** Use `\Stripe\Webhook::constructEvent()` to verify the incoming webhook payload using `STRIPE_WEBHOOK_SECRET`. This is crucial for security.
*   **Event Handling (Switch Statement):**
    *   Process key events:
        *   `payment_intent.succeeded`: Mark bookings as paid, trigger confirmation emails.
        *   `charge.refunded`: Update booking/transaction status to refunded.
        *   `charge.failed`, `payment_intent.payment_failed`: Log failures, potentially flag for review.
    *   Log all webhook events to `logs/webhook_events.log`.
*   Respond with a `200 OK` status to acknowledge receipt of the webhook.

## 6. Refund Functionality Integration (`refund.php`)

*   Create `refund.php` at the project root (or as a function within an existing admin script like `admin_op.php`).
*   Include `vendor/autoload.php` and `config.php`.
*   Set Stripe API key.
*   Receive `chargeId` (mandatory) and `amount` (optional, for partial refunds) via POST request.
*   **API Call:** Use `\Stripe\Refund::create()` to initiate a refund for the specified `chargeId`.
*   **Error Handling:** Implement `try-catch` for Stripe API errors.
*   **Logging:** Log all refund requests and their outcomes to `logs/payment_transactions.log`.
*   Return a JSON response (success/failure) to the calling script/admin interface.

## 7. Security Review and PCI DSS Considerations

*   **No Sensitive Data on Server:** Ensure no raw credit card data ever touches Traveler's servers. Stripe.js tokenization handles this.
*   **HTTPS Everywhere:** All communication related to payments (frontend forms, backend API calls to Stripe, webhooks) **MUST** use HTTPS in production.
*   **API Key Management:** `STRIPE_SECRET_KEY` and `STRIPE_WEBHOOK_SECRET` must be kept strictly confidential. Use environment variables in production.
*   **Webhook Security:** Strong signature verification is implemented in `webhook_handler.php` to prevent spoofed events.
*   **Error Messages:** Avoid verbose error messages on the frontend that could reveal system internals. Log detailed errors on the backend only.

## 8. Testing (Sandbox Environment)

*   **Setup:** Use Stripe's test API keys and test card numbers.
*   **Scenarios to Test:**
    *   Successful payments (various amounts, currencies if applicable).
    *   Declined payments (insufficient funds, expired card, fraud, etc.).
    *   Network issues (simulate API connection errors).
    *   Full refunds.
    *   Partial refunds.
    *   Webhook event processing for `payment_intent.succeeded`, `charge.refunded`, `charge.failed`, etc.
    *   Concurrency issues (multiple payments simultaneously).
*   **Logging Verification:** Check `logs/payment_transactions.log` and `logs/webhook_events.log` for correct entries.

## 9. Documentation and Deployment Plan

*   **Documentation:**
    *   Detail configuration steps (API keys, webhook setup).
    *   Explain how to run Composer to install dependencies.
    *   Outline the payment flow (frontend to backend to Stripe).
    *   List common error codes and troubleshooting steps.
    *   Instructions for setting up webhook endpoints in Stripe Dashboard.
*   **Deployment Plan:**
    *   **Environment Variables:** Transition `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET` (and potentially `STRIPE_PUBLISHABLE_KEY`) to environment variables on the production server.
    *   **HTTPS:** Ensure the production server is configured for HTTPS.
    *   **Webhook Endpoint:** Configure the live Stripe Dashboard to point webhooks to `https://your-domain.com/webhook_handler.php`.
    *   **Log Management:** Implement a robust logging solution for production (e.g., dedicated logging service, log rotation).
    *   **Testing:** Conduct final smoke tests in the live environment with small real transactions before full launch.

---