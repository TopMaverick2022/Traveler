<?php
session_start();
require_once 'vendor/autoload.php'; // For Stripe PHP SDK
require_once 'config.php';
require_once 'db_connection.php';

// Set your secret key. Remember to switch to your live secret key in production.
// See your keys here: https://dashboard.stripe.com/apikeys

if (!defined('STRIPE_SECRET_KEY')) {
    error_log('Stripe secret key is not defined in config.php');
    $_SESSION['payment_message'] = 'Payment processing error: Missing Stripe configuration.';
    header('Location: payment.php');
    exit();
}

Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['payment_message'] = 'Invalid request method.';
    header('Location: payment.php');
    exit();
}

// Validate and sanitize inputs
$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
$customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$currency = filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING);
$payment_method_id = filter_input(INPUT_POST, 'payment_method_id', FILTER_SANITIZE_STRING);

if (!$booking_id || !$customer_id || !$amount || !$currency || !$payment_method_id) {
    $_SESSION['payment_message'] = 'Missing required payment details. Please try again.';
    error_log("Missing payment details: booking_id={$booking_id}, customer_id={$customer_id}, amount={$amount}, currency={$currency}, payment_method_id={$payment_method_id}");
    header('Location: payment.php?booking_id=' . $booking_id);
    exit();
}

// Ensure amount is positive and in cents (Stripe requires amount in smallest currency unit)
if ($amount <= 0) {
    $_SESSION['payment_message'] = 'Invalid payment amount.';
    error_log("Invalid payment amount: {$amount} for booking_id={$booking_id}");
    header('Location: payment.php?booking_id=' . $booking_id);
    exit();
}
$amount_cents = round($amount * 100);

try {
    // Create a PaymentIntent with the payment method
    $paymentIntent = Stripe\PaymentIntent::create([
        'amount' => $amount_cents,
        'currency' => $currency,
        'payment_method' => $payment_method_id,
        'confirmation_method' => 'manual',
        'confirm' => true,
        'description' => "Booking #{$booking_id} for Customer #{$customer_id}",
        'metadata' => [
            'booking_id' => $booking_id,
            'customer_id' => $customer_id
        ]
    ]);

    $transaction_status = $paymentIntent->status; // e.g., 'requires_action', 'succeeded', 'requires_payment_method'

    if ($transaction_status == 'succeeded') {
        // Payment succeeded
        $db_status = 'succeeded';
        $_SESSION['payment_message'] = 'Payment successful! Your booking is confirmed.';

        // Update booking status to 'confirmed' or 'paid'
        $stmt_booking = $conn->prepare("UPDATE bookings SET status = 'paid' WHERE booking_id = ? AND user_id = ?");
        $stmt_booking->bind_param('ii', $booking_id, $customer_id);
        $stmt_booking->execute();
        $stmt_booking->close();

    } elseif ($transaction_status == 'requires_action' && $paymentIntent->next_action->type == 'use_stripe_sdk') {
        // Payment requires further action (e.g., 3D Secure authentication)
        $db_status = 'requires_action';
        // You would typically redirect the user to a page to complete the 3D Secure flow
        // For simplicity, we'll treat this as pending/failed for now.
        $_SESSION['payment_message'] = 'Payment requires further authentication. Please complete the process or try again.';
        error_log("Payment requires action: payment_intent_id={$paymentIntent->id}, status={$transaction_status}");

    } else {
        // Payment failed or is in an unexpected state
        $db_status = 'failed';
        $_SESSION['payment_message'] = 'Payment failed. Please try a different card or contact support.';
        error_log("Payment failed or unexpected status: payment_intent_id={$paymentIntent->id}, status={$transaction_status}");
    }

    // Record the payment in your database
    $stmt = $conn->prepare(
        "INSERT INTO payments (customer_id, booking_id, amount, currency, status, payment_intent_id, payment_method_id)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('iidisss', $customer_id, $booking_id, $amount, $currency, $db_status, $paymentIntent->id, $payment_method_id);
    $stmt->execute();
    $payment_db_id = $stmt->insert_id;
    $stmt->close();

    // Redirect based on payment status
    if ($db_status == 'succeeded') {
        header('Location: mainPage.php?payment=success'); // Or a dedicated success page
    } else if ($db_status == 'requires_action') {
        // Implement redirect to handle 3D secure (e.g., a specific page that can confirm the intent)
        // For now, redirect to payment page with error.
        header('Location: payment.php?booking_id=' . $booking_id);
    } else {
        header('Location: payment.php?booking_id=' . $booking_id);
    }
    exit();

} catch (Stripe\Exception\CardException $e) {
    // A decline or other card-related error occurred
    $error_message = $e->getMessage();
    $_SESSION['payment_message'] = 'Card error: ' . $error_message;
    error_log("Stripe Card Error: " . $error_message . " for booking_id={$booking_id}");
    $db_status = 'failed';
} catch (Stripe\Exception\RateLimitException $e) {
    $error_message = $e->getMessage();
    $_SESSION['payment_message'] = 'Too many requests to the API. Please try again later.';
    error_log("Stripe Rate Limit Error: " . $error_message . " for booking_id={$booking_id}");
    $db_status = 'failed';
} catch (Stripe\Exception\InvalidRequestException $e) {
    $error_message = $e->getMessage();
    $_SESSION['payment_message'] = 'Invalid parameters were supplied to Stripe API.';
    error_log("Stripe Invalid Request Error: " . $error_message . " for booking_id={$booking_id}");
    $db_status = 'failed';
} catch (Stripe\Exception\AuthenticationException $e) {
    $error_message = $e->getMessage();
    $_SESSION['payment_message'] = 'Authentication with Stripe API failed.';
    error_log("Stripe Authentication Error: " . $error_message . " for booking_id={$booking_id}");
    $db_status = 'failed';
} catch (Stripe\Exception\ApiConnectionException $e) {
    $error_message = $e->getMessage();
    $_SESSION['payment_message'] = 'Network communication with Stripe failed.';
    error_log("Stripe API Connection Error: " . $error_message . " for booking_id={$booking_id}");
    $db_status = 'failed';
} catch (Stripe\Exception\ApiErrorException $e) {
    // Generic Stripe API error (e.g., internal error, specific API error not covered above)
    $error_message = $e->getMessage();
    $_SESSION['payment_message'] = 'Stripe API error: ' . $error_message;
    error_log("Stripe API Error: " . $error_message . " for booking_id={$booking_id}");
    $db_status = 'failed';
} catch (Exception $e) {
    // Any other error not specific to Stripe
    $error_message = $e->getMessage();
    $_SESSION['payment_message'] = 'An unexpected error occurred. Please try again.';
    error_log("General Error: " . $error_message . " for booking_id={$booking_id}");
    $db_status = 'failed';
}

// If an error occurred before database insert, we might not have a paymentIntent->id
$payment_intent_id = isset($paymentIntent) ? $paymentIntent->id : 'N/A';

// If payment processing failed due to an exception, record the attempt as 'failed' if not already done
// This should only happen if paymentIntent creation failed or an error occurred before status update
if (!isset($payment_db_id)) {
    try {
        $stmt = $conn->prepare(
            "INSERT INTO payments (customer_id, booking_id, amount, currency, status, payment_intent_id, payment_method_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        // Use a default for payment_intent_id if it's not set from an exception
        $stmt->bind_param('iidisss', $customer_id, $booking_id, $amount, $currency, $db_status, $payment_intent_id, $payment_method_id);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Failed to log payment attempt to DB after initial error: " . $e->getMessage());
    }
}

header('Location: payment.php?booking_id=' . $booking_id);
exit();
?>
