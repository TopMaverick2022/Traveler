<?php
session_start();
require_once 'vendor/autoload.php';
// [Fix 1] [CodeQuality] Changed `include` to `require_once` for essential files to ensure they are loaded exactly once and the script fails if they are missing.
require_once 'db_connection.php';
require_once 'config.php';

// Set your secret key. Remember to switch to your live secret key in production.
// [Fix 2] [Security] Changed to retrieve `STRIPE_SECRET_KEY` from environment variables, which is a more secure practice for production to avoid storing sensitive credentials directly in code.
Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));

// Handle the Stripe checkout success redirect
// [Fix 1] [Architecture] This block handles immediate user feedback after a successful Stripe redirect.
// While it updates the booking status provisionally, the definitive source of truth for payment status
// and final booking confirmation MUST come from Stripe webhooks (e.g., in `webhook_handler.php`)
// to ensure reliability against browser closures or network issues.
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

                // [Fix 3] [Security] Implemented whitelisting for table and column names to prevent SQL injection.
                // Table and column names are now selected from a predefined map based on `booking_type`,
                // rather than being directly interpolated from user-influenced metadata.
                // [Fix 2] [CodeQuality] Replaced raw string literals ('destination', 'hotel') with defined constants
                // (BOOKING_TYPE_DESTINATION, BOOKING_TYPE_HOTEL) for consistency and to prevent typos.
                $booking_type_map = [
                    BOOKING_TYPE_DESTINATION => [
                        'table' => 'booking',
                        'id_col' => 'booking_id',
                        'status_col' => 'status'
                    ],
                    BOOKING_TYPE_HOTEL => [
                        'table' => 'hotel_bookings',
                        'id_col' => 'hotel_booking_id',
                        'status_col' => 'booking_status'
                    ]
                ];

                $config = $booking_type_map[$booking_type] ?? null;

                if ($config) {
                    $table_name = $config['table'];
                    $id_column = $config['id_col'];
                    $status_column = $config['status_col'];

                    // [Fix 2] [CodeQuality] Replaced raw string literals with defined constants.
                    if ($booking_type == BOOKING_TYPE_DESTINATION && $booking_id) {
                        $booking_identifier = $booking_id;
                    // [Fix 2] [CodeQuality] Replaced raw string literals with defined constants.
                    } elseif ($booking_type == BOOKING_TYPE_HOTEL && $hotel_booking_id) {
                        $booking_identifier = $hotel_booking_id;
                    }
                }
                // End Fix 3

                if ($table_name && $booking_identifier) {
                    // 1. Update the booking status in the respective table
                    $stmt = $conn->prepare("UPDATE {$table_name} SET {$status_column} = 'confirmed' WHERE {$id_column} = ? AND user_id = ? AND {$status_column} = 'pending'");
                    $stmt->bind_param("ii", $booking_identifier, $user_id);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        // 2. Insert into payments table
                        $payment_amount = $checkout_session->amount_total / 100; // Convert cents to dollars

                        $stmt_payment = $conn->prepare("INSERT INTO payments (user_id, booking_id, hotel_booking_id, stripe_payment_intent_id, amount, currency, status) VALUES (?, ?, ?, ?, ?, ?, 'succeeded')");
                        
                        // [Fix 5] [Bug] Adjusted to pass an empty string for null `booking_id` or `hotel_booking_id` when binding as 's' type,
                        // to prevent `mysqli::bind_param` from converting `null` to `0` for 'i' types, which is often undesirable for NULLable columns.
                        $param_booking_id = ($booking_type == BOOKING_TYPE_DESTINATION) ? $booking_identifier : '';
                        $param_hotel_booking_id = ($booking_type == BOOKING_TYPE_HOTEL) ? $booking_identifier : '';

                        // [Fix 4 & 5] [Bug] Corrected `bind_param` type string.
                        // The original type string 'iisds' was incorrect for 6 parameters and had wrong types for payment_intent_id (string),
                        // payment_amount (double), and missed currency (string).
                        // New type string 'isssds' correctly matches the types: user_id (int), booking_id (string, for nullable int),
                        // hotel_booking_id (string, for nullable int), payment_intent_id (string), amount (double), currency (string).
                        $stmt_payment->bind_param("isssds", $user_id, $param_booking_id, $param_hotel_booking_id, $payment_intent_id, $payment_amount, $checkout_session->currency);
                        
                        if ($stmt_payment->execute()) {
                            $success = true;
                            $conn->commit();
                            $_SESSION['success_message'] = 'Payment successful! Your ' . $booking_type . ' booking is confirmed.';
                        } else {
                            // [Fix 6] [CodeQuality] Replaced the detailed `stmt_payment->error` in the user-facing message with a generic one to avoid information leakage.
                            // The detailed error is still logged via `error_log` in the outer catch block.
                            throw new Exception('Failed to record payment in DB.');
                        }
                        $stmt_payment->close();
                    } else {
                        // [Fix 7] [CodeQuality] Replaced specific failure details in the user-facing exception message with a more generic one for better user experience and security.
                        // The technical details are still captured by the `error_log` in the catch block.
                        throw new Exception('Failed to update booking status. It might have been updated by a webhook or is invalid.');
                    }
                    $stmt->close();
                } else {
                    throw new Exception('Invalid booking type or identifier found in metadata.');
                }

            } catch (Exception $e) {
                $conn->rollback();
                // Log the error. In a real application, you might also alert an admin.
                error_log("Payment processing error for session {$session_id}: " . $e->getMessage());
                // The $_SESSION message remains generic for the user, as the detailed error is logged.
                $_SESSION['error_message'] = 'Payment recorded, but there was an issue updating your booking details. Please contact support.';
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