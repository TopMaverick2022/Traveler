<?php
session_start(); // Start session if it uses session data, though webhooks typically don't.
require_once 'db_connection.php'; // Include the new centralized database connection

// Any existing 'include "junk.php";' or 'include "infop.php";' should be removed.
// All database interactions in this file should now use the $conn variable.

// It's crucial for webhook handlers to be robust and not rely on user sessions.
// Authentication for webhooks is typically done via shared secrets or API keys in headers.
// For simplicity and given the project context, we proceed without specific webhook authentication logic here,
// but in a real-world scenario, this would be the first security step.

header('Content-Type: application/json');
$input = file_get_contents('php://input');
$event = json_decode($input, true);

// Log the raw webhook payload for debugging
file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Webhook received: " . $input . "\n", FILE_APPEND);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid JSON payload.']);
    exit();
}

if (!isset($event['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Webhook event type missing.']);
    exit();
}

switch ($event['type']) {
    case 'payment.succeeded':
        // Example: Handle a successful payment event from a payment gateway
        $transaction_id = $event['data']['transaction_id'] ?? null;
        $booking_id = $event['data']['booking_id'] ?? null; // Assuming booking_id is sent in webhook payload
        $amount = $event['data']['amount'] ?? null;

        if ($transaction_id && $booking_id && $amount) {
            // Update booking status and payment records in the database
            $sql_update_payment = "UPDATE payments SET status = 'completed', amount = ? WHERE transaction_id = ? AND booking_id = ?";
            if ($stmt = $conn->prepare($sql_update_payment)) {
                $stmt->bind_param("dsi", $amount, $transaction_id, $booking_id);
                if ($stmt->execute()) {
                    // Update booking status in the 'bookings' table
                    $sql_update_booking = "UPDATE bookings SET status = 'Confirmed' WHERE id = ?";
                    if ($stmt_booking = $conn->prepare($sql_update_booking)) {
                        $stmt_booking->bind_param("i", $booking_id);
                        $stmt_booking->execute();
                        $stmt_booking->close();
                    }
                    http_response_code(200);
                    echo json_encode(['status' => 'success', 'message' => 'Payment success handled.']);
                } else {
                    http_response_code(500); // Internal Server Error
                    echo json_encode(['status' => 'error', 'message' => 'Database update failed: ' . $stmt->error]);
                }
                $stmt->close();
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Database error: Could not prepare payment update statement.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing data for payment.succeeded event.']);
        }
        break;

    case 'payment.failed':
        // Example: Handle a failed payment event
        $transaction_id = $event['data']['transaction_id'] ?? null;
        $booking_id = $event['data']['booking_id'] ?? null;

        if ($transaction_id && $booking_id) {
            $sql_update_payment = "UPDATE payments SET status = 'failed' WHERE transaction_id = ? AND booking_id = ?";
            if ($stmt = $conn->prepare($sql_update_payment)) {
                $stmt->bind_param("si", $transaction_id, $booking_id);
                if ($stmt->execute()) {
                    $sql_update_booking = "UPDATE bookings SET status = 'Failed' WHERE id = ?";
                    if ($stmt_booking = $conn->prepare($sql_update_booking)) {
                        $stmt_booking->bind_param("i", $booking_id);
                        $stmt_booking->execute();
                        $stmt_booking->close();
                    }
                    http_response_code(200);
                    echo json_encode(['status' => 'success', 'message' => 'Payment failure handled.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'Database update failed: ' . $stmt->error]);
                }
                $stmt->close();
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing data for payment.failed event.']);
        }
        break;

    // Add more cases for other webhook event types (e.g., refund.succeeded, subscription.created)

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown webhook event type.']);
        break;
}

$conn->close();
exit();
?>