<?php
require_once 'vendor/autoload.php';
include 'db_connection.php';
include 'config.php';

Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$payload = @file_get_contents('php://input');
$event = null;

try {
    $event = Stripe\Webhook::constructEvent(
        $payload, $_SERVER['HTTP_STRIPE_SIGNATURE'], STRIPE_WEBHOOK_SECRET
    );
} catch (UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch (Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

function handlePaymentIntentSucceeded($paymentIntent, $conn) {
    $user_id = $paymentIntent->metadata->user_id ?? null;
    $booking_type = $paymentIntent->metadata->booking_type ?? null;
    $booking_id = $paymentIntent->metadata->booking_id ?? null;
    $hotel_booking_id = $paymentIntent->metadata->hotel_booking_id ?? null;

    if (!$user_id || (!$booking_id && !$hotel_booking_id) || !$booking_type) {
        error_log("Webhook Error: Missing metadata for payment_intent.succeeded. Payment Intent: " . $paymentIntent->id);
        return false;
    }

    $conn->begin_transaction();

    try {
        $booking_table = '';
        $booking_id_column = '';
        $status_column = '';
        $identifier = null;

        if ($booking_type === 'destination' && $booking_id) {
            $booking_table = 'booking';
            $booking_id_column = 'booking_id';
            $status_column = 'status';
            $identifier = $booking_id;
        } elseif ($booking_type === 'hotel' && $hotel_booking_id) {
            $booking_table = 'hotel_bookings';
            $booking_id_column = 'hotel_booking_id';
            $status_column = 'booking_status';
            $identifier = $hotel_booking_id;
        } else {
            throw new Exception("Invalid booking type or missing identifier in metadata.");
        }

        // Check if booking already confirmed or payment recorded to prevent duplicates
        $check_stmt = $conn->prepare("SELECT 1 FROM payments WHERE stripe_payment_intent_id = ?");
        $check_stmt->bind_param("s", $paymentIntent->id);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            error_log("Webhook Info: Payment Intent {$paymentIntent->id} already processed. Skipping.");
            $check_stmt->close();
            $conn->commit();
            return true;
        }
        $check_stmt->close();

        // Update booking status
        $update_booking_stmt = $conn->prepare("UPDATE {$booking_table} SET {$status_column} = 'confirmed' WHERE {$booking_id_column} = ? AND user_id = ? AND {$status_column} = 'pending'");
        $update_booking_stmt->bind_param("ii", $identifier, $user_id);
        $update_booking_stmt->execute();
        if ($update_booking_stmt->affected_rows === 0) {
             error_log("Webhook Warning: Booking {$booking_id_column}={$identifier} not found, not pending, or not owned by user for Payment Intent: " . $paymentIntent->id);
            // This might happen if process_payment.php already updated it, or invalid data. Proceed to record payment.
        }
        $update_booking_stmt->close();

        // Record payment
        $amount = $paymentIntent->amount / 100;
        $currency = $paymentIntent->currency;
        $status = 'succeeded';

        $insert_payment_stmt = $conn->prepare("INSERT INTO payments (user_id, booking_id, hotel_booking_id, stripe_payment_intent_id, amount, currency, status) VALUES (?, ?, ?, ?, ?, ?, ?)");

        $param_booking_id = ($booking_type == 'destination') ? $identifier : null;
        $param_hotel_booking_id = ($booking_type == 'hotel') ? $identifier : null;

        $insert_payment_stmt->bind_param("iisdss", $user_id, $param_booking_id, $param_hotel_booking_id, $paymentIntent->id, $amount, $currency, $status);
        $insert_payment_stmt->execute();
        $insert_payment_stmt->close();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Webhook Critical Error (PaymentIntentSucceeded): " . $e->getMessage() . " for Payment Intent: " . $paymentIntent->id);
        return false;
    }
}

function handleChargeRefunded($charge, $conn) {
    $paymentIntentId = $charge->payment_intent;

    // Retrieve the payment record from your DB using paymentIntentId
    $stmt = $conn->prepare("SELECT user_id, booking_id, hotel_booking_id FROM payments WHERE stripe_payment_intent_id = ?");
    $stmt->bind_param("s", $paymentIntentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment_data = $result->fetch_assoc();
    $stmt->close();

    if (!$payment_data) {
        error_log("Webhook Error: Payment record not found for refunded charge (Payment Intent: {$paymentIntentId}).");
        return false;
    }

    $conn->begin_transaction();

    try {
        // Update payment status to refunded
        $update_payment_stmt = $conn->prepare("UPDATE payments SET status = 'refunded' WHERE stripe_payment_intent_id = ?");
        $update_payment_stmt->bind_param("s", $paymentIntentId);
        $update_payment_stmt->execute();
        $update_payment_stmt->close();

        // Update corresponding booking status to cancelled/refunded
        if ($payment_data['booking_id']) {
            $update_booking_stmt = $conn->prepare("UPDATE booking SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?");
            $update_booking_stmt->bind_param("ii", $payment_data['booking_id'], $payment_data['user_id']);
            $update_booking_stmt->execute();
            $update_booking_stmt->close();
        } elseif ($payment_data['hotel_booking_id']) {
            $update_booking_stmt = $conn->prepare("UPDATE hotel_bookings SET booking_status = 'cancelled' WHERE hotel_booking_id = ? AND user_id = ?");
            $update_booking_stmt->bind_param("ii", $payment_data['hotel_booking_id'], $payment_data['user_id']);
            $update_booking_stmt->execute();
            $update_booking_stmt->close();
            // Also, increment num_rooms_available if it was a hotel booking
            // This requires fetching room_id and num_rooms from hotel_bookings
            $booking_details_stmt = $conn->prepare("SELECT room_id, num_rooms FROM hotel_bookings WHERE hotel_booking_id = ?");
            $booking_details_stmt->bind_param("i", $payment_data['hotel_booking_id']);
            $booking_details_stmt->execute();
            $booking_room_data = $booking_details_stmt->get_result()->fetch_assoc();
            $booking_details_stmt->close();

            if ($booking_room_data) {
                $update_rooms_stmt = $conn->prepare("UPDATE hotel_rooms SET num_rooms_available = num_rooms_available + ? WHERE room_id = ?");
                $update_rooms_stmt->bind_param("ii", $booking_room_data['num_rooms'], $booking_room_data['room_id']);
                $update_rooms_stmt->execute();
                $update_rooms_stmt->close();
            }
        }

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Webhook Critical Error (ChargeRefunded): " . $e->getMessage() . " for Payment Intent: " . $paymentIntentId);
        return false;
    }
}

// Handle the event
switch ($event->type) {
    case 'payment_intent.succeeded':
        $paymentIntent = $event->data->object; // contains a StripePaymentIntent object
        handlePaymentIntentSucceeded($paymentIntent, $conn);
        break;
    case 'charge.refunded':
        $charge = $event->data->object; // contains a StripeCharge object
        handleChargeRefunded($charge, $conn);
        break;
    // ... handle other event types
    default:
        error_log('Received unknown event type ' . $event->type);
}

http_response_code(200);
$conn->close();

?>
