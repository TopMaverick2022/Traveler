# Payment System Integration Plan: Stripe

This document outlines the implementation details for integrating Stripe into the Traveler application to handle payments and refunds.

## 1. Tech Stack & Dependencies

*   **Language:** PHP
*   **Framework:** None (Vanilla PHP)
*   **Database:** MySQLi
*   **Payment Gateway:** Stripe
*   **Server-side Library:** Stripe PHP SDK (`stripe/stripe-php`)
*   **Client-side Library:** Stripe.js (`https://js.stripe.com/v3/`)

## 2. Configuration (`config.php`)

Stripe API keys (publishable and secret) and webhook secret must be securely stored. These will be defined as constants in `config.php`.

php
// Stripe API Keys
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_STRIPE_PUBLISHABLE_KEY'); 
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_STRIPE_SECRET_KEY');

// Stripe Webhook Secret (for verifying webhook signatures)
define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_STRIPE_WEBHOOK_SECRET');

define('DEFAULT_CURRENCY', 'usd');


**Action:** Update `config.php` with these definitions. Replace placeholders with actual keys (use test keys for development).

## 3. Database Schema Updates (`database/travel.sql`)

Two new tables are required to store payment and refund information:

### `payments` Table

Records details of successful and attempted payments.

sql
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL, -- FK to users table
    booking_id INT NOT NULL,  -- FK to bookings table
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'usd',
    status VARCHAR(50) NOT NULL DEFAULT 'pending', -- 'pending', 'succeeded', 'failed', 'refunded', 'partially_refunded'
    transaction_id VARCHAR(255) UNIQUE, -- Stripe Charge ID (legacy, use payment_intent_id)
    payment_intent_id VARCHAR(255) UNIQUE NOT NULL, -- Stripe Payment Intent ID
    payment_method_id VARCHAR(255), -- Stripe Payment Method ID used
    payment_gateway VARCHAR(50) NOT NULL DEFAULT 'stripe',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);


### `refund_requests` Table

Stores records of refund requests and their processing status.

sql
CREATE TABLE IF NOT EXISTS refund_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL, -- FK to users table
    payment_id INT NOT NULL,  -- FK to payments table
    reason TEXT,              -- Reason for the refund request
    status VARCHAR(50) NOT NULL DEFAULT 'pending', -- 'pending', 'approved', 'rejected', 'refunded'
    refund_id VARCHAR(255) UNIQUE, -- Stripe Refund ID
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME,
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE CASCADE
);


**Action:** Add these table creation statements to `database/travel.sql` and ensure they are run to update the database.

## 4. Client-Side Payment Form (`payment.php` & `js/payment.js`)

### `payment.php` Modifications

*   Remove all direct card input fields (card number, expiry, CVV, card holder name).
*   Add a `div` element with `id="card-element"` where Stripe.js will render the secure UI.
*   Include the Stripe.js library (`<script src="https://js.stripe.com/v3/"></script>`).
*   Pass `STRIPE_PUBLISHABLE_KEY` from PHP to JavaScript.
*   Add hidden input fields for `booking_id`, `customer_id`, `amount`, `currency`, and `payment_method_id` (to be populated by JavaScript).

### `js/payment.js` Modifications

*   Initialize Stripe.js with the `STRIPE_PUBLISHABLE_KEY` received from `payment.php`.
*   Create and mount a Stripe Card Element into the `#card-element` div.
*   Listen for changes on the Card Element to display validation errors.
*   On form submission:
    *   Prevent default form submission.
    *   Use `stripe.createPaymentMethod()` to securely tokenize card data.
    *   If successful, set the `payment_method_id` hidden input field and submit the form programmatically to `process_payment.php`.
    *   Handle and display any client-side errors (e.g., invalid card details).

**Action:** Update `payment.php` and `js/payment.js` as described.

## 5. Server-Side Payment Processing (`process_payment.php`)

*   Include `vendor/autoload.php` for the Stripe PHP SDK.
*   Initialize Stripe with `STRIPE_SECRET_KEY`.
*   Receive `payment_method_id`, `booking_id`, `customer_id`, `amount`, and `currency` from the client-side form.
*   Validate and sanitize all inputs.
*   Create a `Stripe\PaymentIntent` using the `payment_method_id` and the booking details.
*   Handle various `PaymentIntent` statuses:
    *   `succeeded`: Payment successful. Update `payments` table status to 'succeeded' and `bookings` table status to 'paid'.
    *   `requires_action`: Payment requires 3D Secure or other authentication. User needs to be redirected or prompted.
    *   `requires_payment_method` / `failed`: Payment failed. Update `payments` table status to 'failed'.
*   Record payment details (Stripe Payment Intent ID, amount, status) in the `payments` table.
*   Implement robust error handling for Stripe API calls and database operations.
*   Redirect user with success/failure messages.

**Action:** Update `process_payment.php` to handle server-side Stripe charges.

## 6. Refund Logic (`refund.php`)

*   Ensure this script is protected and only accessible by authorized administrators.
*   Include `vendor/autoload.php` and initialize Stripe with `STRIPE_SECRET_KEY`.
*   Receive `payment_id` and optionally `refund_amount` and `reason` from an admin interface request.
*   Retrieve the `payment_intent_id` (or `charge_id`) from the `payments` table using `payment_id`.
*   Create a `Stripe\Refund` using the Stripe PHP SDK with the `payment_intent_id` and desired amount.
*   Update the `refund_requests` table with the Stripe `refund_id` and status (`succeeded`, `failed`, `pending`).
*   Update the original `payments` table status to 'refunded' or 'partially_refunded' based on the refund amount.
*   Implement error handling and logging.

**Action:** Implement `refund.php` as described.

## 7. Webhook Handler (`webhook_handler.php`)

*   Include `vendor/autoload.php` and initialize Stripe with `STRIPE_SECRET_KEY`.
*   Implement Stripe webhook signature verification using `Stripe\Webhook::constructEvent()` and `STRIPE_WEBHOOK_SECRET`.
*   Handle key events:
    *   `payment_intent.succeeded`: Confirm payment, update `payments` table to 'succeeded' and `bookings` table to 'paid'. This acts as a fallback or final confirmation for payments.
    *   `charge.refunded`: Confirm refund, update `refund_requests` table status to 'refunded' and `payments` table status to 'refunded' or 'partially_refunded'.
*   Log all webhook events and any errors encountered during processing.
*   Return a `200 OK` response to Stripe for successful processing.

**Action:** Implement `webhook_handler.php` as described.

## 8. Error Handling and User Feedback

*   Display user-friendly messages for successful payments, failed payments, and refund statuses.
*   Implement server-side logging (`error_log()`) for all critical payment operations, Stripe API calls, and webhook processing errors.
*   Ensure all user inputs are sanitized (`filter_input`) and validated to prevent vulnerabilities.

**Action:** Integrate error handling and user feedback across all relevant files.

## 9. Testing

*   **Development Environment:** Use Stripe's test API keys and test card numbers.
*   **Payment Flow:** Test successful payments, failed payments (declined cards, invalid details), and 3D Secure scenarios.
*   **Refund Flow:** Test full and partial refunds through the admin interface.
*   **Webhooks:** Use Stripe CLI to simulate webhook events (`stripe listen --forward-to localhost/webhook_handler.php`) or manually trigger them from the Stripe dashboard to verify asynchronous updates.
*   Verify database updates for all scenarios.

**Action:** Conduct thorough end-to-end testing.
