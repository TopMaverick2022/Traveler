<?php
session_start();
require_once 'vendor/autoload.php';
include 'db_connection.php';
include 'config.php';

// Set your secret key. Remember to switch to your live secret key in production.
Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Handle the Stripe checkout success redirect
if (isset($_GET['session_id'])) {
    $session_id = $_GET['session_id'];

    try {
        $checkout_session = Stripe\Checkout\Session::retrieve($session_id);

        // Check if the payment was successful
        if ($checkout_session->payment_status == 'paid') {
            $user_id = $checkout_session->metadata->user_id ?? null;
            $booking_type = $checkout_session->metadata->booking_type ?? null;
            $booking_id = $checkout_session->metadata->booking_id ?? null;
            $hotel_booking_id = $checkout_session->metadata->hotel_booking_id ?? null;
            $payment_intent_id = $checkout_session->payment_intent;

            // Start a transaction for database updates
            $conn->begin_transaction();

            try {
                $success = false;
                $table_name = '';
                $id_column = '';
                $status_column = '';
                $booking_identifier = null;

                if ($booking_type == 'destination' && $booking_id) {
                    $table_name = 'booking';
                    $id_column = 'booking_id';
                    $status_column = 'status';
                    $booking_identifier = $booking_id;
                } elseif ($booking_type == 'hotel' && $hotel_booking_id) {
                    $table_name = 'hotel_bookings';
                    $id_column = 'hotel_booking_id';
                    $status_column = 'booking_status';
                    $booking_identifier = $hotel_booking_id;
                }

                if ($table_name && $booking_identifier) {
                    // 1. Update the booking status in the respective table
                    $stmt = $conn->prepare("UPDATE {$table_name} SET {$status_column} = 'confirmed' WHERE {$id_column} = ? AND user_id = ? AND {$status_column} = 'pending'");
                    $stmt->bind_param("ii", $booking_identifier, $user_id);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        // 2. Insert into payments table
                        $payment_amount = $checkout_session->amount_total / 100; // Convert cents to dollars

                        $stmt_payment = $conn->prepare("INSERT INTO payments (user_id, booking_id, hotel_booking_id, stripe_payment_intent_id, amount, currency, status) VALUES (?, ?, ?, ?, ?, ?, 'succeeded')");
                        
                        $param_booking_id = ($booking_type == 'destination') ? $booking_identifier : null;
                        $param_hotel_booking_id = ($booking_type == 'hotel') ? $booking_identifier : null;

                        $stmt_payment->bind_param("iisds", $user_id, $param_booking_id, $param_hotel_booking_id, $payment_intent_id, $payment_amount, $checkout_session->currency);
                        
                        if ($stmt_payment->execute()) {
                            $success = true;
                            $conn->commit();
                            $_SESSION['success_message'] = 'Payment successful! Your ' . $booking_type . ' booking is confirmed.';
                        } else {
                            throw new Exception('Failed to record payment in DB: ' . $stmt_payment->error);
                        }
                        $stmt_payment->close();
                    } else {
                        throw new Exception('Booking not found, not pending, or not owned by user. Payment recorded by webhook likely.');
                    }
                    $stmt->close();
                } else {
                    throw new Exception('Invalid booking type or identifier found in metadata.');
                }

            } catch (Exception $e) {
                $conn->rollback();
                // Log the error. In a real application, you might also alert an admin.
                error_log("Payment processing error for session {$session_id}: " . $e->getMessage());
                $_SESSION['error_message'] = 'Payment recorded, but there was an issue updating your booking details. Please contact support. ' . $e->getMessage();
            }
        } else {
            // Payment was not 'paid'
            $_SESSION['error_message'] = 'Payment not successful. Status: ' . $checkout_session->payment_status;
        }
    } catch (Stripe\Exception\ApiErrorException $e) {
        // Handle error from Stripe API
        error_log("Stripe API Error in process_payment.php for session {$session_id}: " . $e->getMessage());
        $_SESSION['error_message'] = 'Payment processing failed due to a Stripe error. Please try again or contact support. ' . $e->getMessage();
    } catch (Exception $e) {
        // General error handling
        error_log("General Error in process_payment.php for session {$session_id}: " . $e->getMessage());
        $_SESSION['error_message'] = 'An unexpected error occurred during payment processing. Please try again or contact support. ' . $e->getMessage();
    }

    $conn->close();
    header('Location: index.php'); // Redirect to homepage or a specific confirmation page
    exit();

} else {
    $_SESSION['error_message'] = 'Invalid payment request.';
    header('Location: index.php');
    exit();
}

// Note: For production, rely more heavily on webhooks (webhook_handler.php)
// for ultimate source of truth regarding payment status to avoid race conditions
// or issues if a user closes the browser before redirection completes.
?>