<?php
session_start(); // Session might be useful for logging or admin notifications

// IMPORTANT: In a real-world scenario, you MUST validate the webhook signature
// provided by the payment gateway (e.g., Stripe-Signature header, PayPal-Auth-Algo header).
// Without signature validation, your webhook endpoint is vulnerable to spoofing.

require_once 'db_connection.php';

// Log webhook request for debugging (optional)
file_put_contents('webhook_log.txt', "Webhook received at: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents('webhook_log.txt', "Headers: " . json_encode(getallheaders()) . "\n", FILE_APPEND);
$payload = file_get_contents('php://input');
file_put_contents('webhook_log.txt', "Payload: " . $payload . "\n\n", FILE_APPEND);

// Attempt to decode the JSON payload
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit();
}

// --- Payment Gateway Specific Logic --- //
// This is a placeholder for how different payment gateways might send data.
// You'd typically look for a specific event type, e.g., 'payment_intent.succeeded', 'checkout.session.completed'

$event_type = $data['event_type'] ?? 'unknown'; // Example for a generic gateway
$transaction_id = $data['transaction_id'] ?? null; // Example transaction ID
$booking_id = $data['metadata']['booking_id'] ?? null; // Example: booking ID stored in metadata
$payment_status = $data['status'] ?? 'failed'; // Example status

// Simulate successful payment event
if ($event_type === 'payment_succeeded' && $transaction_id && $booking_id) {
    if ($payment_status === 'succeeded') {
        $new_booking_status = 'Confirmed'; // Or 'Paid', etc.
        // Update booking status in the database
        $stmt = $conn->prepare("UPDATE booking SET status = ?, transaction_id = ? WHERE booking_id = ? AND status = 'Pending'");
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error preparing statement: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param("ssi", $new_booking_status, $transaction_id, $booking_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                echo json_encode(['message' => 'Booking status updated successfully.']);
            } else {
                // Booking not found or already updated (e.g., duplicate webhook)
                http_response_code(200);
                echo json_encode(['message' => 'Booking status already updated or not found for pending status.']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Database error updating booking status: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        // Handle other payment statuses like 'failed', 'refunded', etc.
        // You might log these or update a different status field.
        http_response_code(200);
        echo json_encode(['message' => 'Unhandled payment status: ' . $payment_status]);
    }
} else if ($event_type === 'refund_succeeded' && $transaction_id && $booking_id) {
    $new_booking_status = 'Refunded';
    $stmt = $conn->prepare("UPDATE booking SET status = ? WHERE booking_id = ?");
    $stmt->bind_param("si", $new_booking_status, $booking_id);
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['message' => 'Booking status updated to Refunded.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error updating booking status to Refunded: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(200); // Acknowledge receipt even if not processed
    echo json_encode(['message' => 'Unhandled event type or missing data.']);
}

$conn->close();
