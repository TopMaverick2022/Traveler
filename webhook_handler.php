<?php
require_once 'vendor/autoload.php'; // For Stripe PHP SDK
require_once 'config.php';
require_once 'db_connection.php';

// Set your secret key.
Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// This is your Stripe webhook secret. Replace with your actual webhook secret.
// You can find it in the Stripe Dashboard -> Developers -> Webhooks section
$webhookSecret = STRIPE_WEBHOOK_SECRET;

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

$event = null;

try {
    $event = Stripe\Webhook::constructEvent(
        $payload, $sig_header, $webhookSecret
    );
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    error_log("Webhook Error: Invalid payload - " . $e->getMessage());
    exit();
} catch (Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    error_log("Webhook Error: Invalid signature - " . $e->getMessage());
    exit();
}

// Handle the event
switch ($event->type) {
    case 'payment_intent.succeeded':
        $paymentIntent = $event->data->object; // contains a Stripe\PaymentIntent
        handlePaymentIntentSucceeded($paymentIntent, $conn);
        break;

    case 'charge.refunded':
        $charge = $event->data->object; // contains a Stripe\Charge
        handleChargeRefunded($charge, $conn);
        break;

    // Add more event types as needed, e.g., invoice.payment_succeeded, customer.subscription.created

    default:
        // Unexpected event type
        error_log("Received unknown event type " . $event->type);
        break;
}

http_response_code(200);

/**
 * Handles the payment_intent.succeeded webhook event.
 * Updates the payment status in the database.
 */
function handlePaymentIntentSucceeded($paymentIntent, $conn) {
    $payment_intent_id = $paymentIntent->id;
    $amount = $paymentIntent->amount / 100; // Convert cents to dollars
    $currency = strtoupper($paymentIntent->currency);
    $customer_id = $paymentIntent->metadata->customer_id ?? null;
    $booking_id = $paymentIntent->metadata->booking_id ?? null;

    error_log("PaymentIntent succeeded: {$payment_intent_id}");

    // Check if the payment already exists and is not yet 'succeeded'
    $stmt_check = $conn->prepare("SELECT payment_id FROM payments WHERE payment_intent_id = ? AND status != 'succeeded'");
    $stmt_check->bind_param('s', $payment_intent_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Payment exists and needs update
        $stmt = $conn->prepare("UPDATE payments SET status = 'succeeded', updated_at = NOW() WHERE payment_intent_id = ?");
        $stmt->bind_param('s', $payment_intent_id);
        if ($stmt->execute()) {
            error_log("Updated payment {$payment_intent_id} to succeeded.");
        } else {
            error_log("Failed to update payment {$payment_intent_id}: " . $stmt->error);
        }
        $stmt->close();

        // Optionally, update booking status as well
        if ($booking_id) {
            $stmt_booking = $conn->prepare("UPDATE bookings SET status = 'paid' WHERE booking_id = ?");
            $stmt_booking->bind_param('i', $booking_id);
            if ($stmt_booking->execute()) {
                error_log("Updated booking {$booking_id} status to paid.");
            } else {
                error_log("Failed to update booking {$booking_id}: " . $stmt_booking->error);
            }
            $stmt_booking->close();
        }

    } else if ($result_check->num_rows == 0 && $customer_id && $booking_id) {
        // This might happen if your initial `process_payment.php` didn't capture payment yet, or missed. 
        // Or if the payment was confirmed asynchronously without initial DB entry.
        // Insert a new payment record if not found (less common, but good for robustness)
        error_log("Payment {$payment_intent_id} not found in DB, inserting as succeeded from webhook.");
        $stmt_insert = $conn->prepare(
            "INSERT INTO payments (customer_id, booking_id, amount, currency, status, payment_intent_id, payment_gateway)
             VALUES (?, ?, ?, ?, 'succeeded', ?, 'stripe')"
        );
        $stmt_insert->bind_param('iidis', $customer_id, $booking_id, $amount, $currency, $payment_intent_id);
        if ($stmt_insert->execute()) {
            error_log("Inserted new payment {$payment_intent_id} from webhook.");
        } else {
            error_log("Failed to insert new payment {$payment_intent_id} from webhook: " . $stmt_insert->error);
        }
        $stmt_insert->close();

        // Update booking status
        if ($booking_id) {
            $stmt_booking = $conn->prepare("UPDATE bookings SET status = 'paid' WHERE booking_id = ?");
            $stmt_booking->bind_param('i', $booking_id);
            $stmt_booking->execute();
            $stmt_booking->close();
        }
    } else {
        error_log("Payment {$payment_intent_id} already succeeded or inconsistent data, no action taken.");
    }
    $stmt_check->close();
}

/**
 * Handles the charge.refunded webhook event.
 * Updates the refund request and payment status in the database.
 */
function handleChargeRefunded($charge, $conn) {
    $charge_id = $charge->id;
    $refund_id = $charge->refunds->data[0]->id ?? null; // Get the latest refund ID
    $refund_status = $charge->refunds->data[0]->status ?? 'unknown';
    $amount_refunded_cents = $charge->refunds->data[0]->amount ?? 0;
    $amount_refunded = $amount_refunded_cents / 100;

    error_log("Charge refunded: {$charge_id}, Refund ID: {$refund_id}, Status: {$refund_status}");

    // Find the corresponding payment in your database
    // Assuming you store charge_id in payments.transaction_id or payment_intent_id in payments.payment_intent_id
    $stmt = $conn->prepare("SELECT payment_id, amount FROM payments WHERE payment_intent_id = ? OR transaction_id = ?");
    $stmt->bind_param('ss', $charge_id, $charge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();

    if ($payment) {
        $payment_id = $payment['payment_id'];
        $original_payment_amount = $payment['amount'];

        // Update refund_requests table
        $stmt_refund_request = $conn->prepare(
            "UPDATE refund_requests SET status = ?, refund_id = ?, processed_at = NOW() WHERE payment_id = ? AND status = 'pending'"
        );
        $stmt_refund_request->bind_param('ssi', $refund_status, $refund_id, $payment_id);
        if ($stmt_refund_request->execute()) {
            error_log("Updated refund request for payment {$payment_id} to {$refund_status}.");
        } else {
            error_log("Failed to update refund request for payment {$payment_id}: " . $stmt_refund_request->error);
        }
        $stmt_refund_request->close();

        // Update payments table status
        $new_payment_status = $payment['status'];
        if ($refund_status === 'succeeded') {
            if ($amount_refunded >= $original_payment_amount) {
                $new_payment_status = 'refunded';
            } else {
                $new_payment_status = 'partially_refunded';
            }
        } else if ($refund_status === 'failed') {
            // If a refund failed, the payment status might revert or stay as original
            error_log("Stripe refund for charge {$charge_id} failed.");
            // Optionally, you might log this as a specific refund failure without changing main payment status
        }

        $stmt_update_payment = $conn->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE payment_id = ?");
        $stmt_update_payment->bind_param('si', $new_payment_status, $payment_id);
        if ($stmt_update_payment->execute()) {
            error_log("Updated payment {$payment_id} status to {$new_payment_status}.");
        } else {
            error_log("Failed to update payment {$payment_id} status: " . $stmt_update_payment->error);
        }
        $stmt_update_payment->close();

    } else {
        error_log("No payment found for charge ID {$charge_id} to update refund status.");
    }
}


?>
