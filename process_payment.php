<?php
session_start();
require_once 'db_connection.php'; // Include the new centralized database connection

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['payment_message'] = 'Please log in to complete payment.';
    header("Location: index.php");
    exit();
}

// Any existing 'include "junk.php";' or 'include "infop.php";' should be removed.
// All database interactions in this file should now use the $conn variable.

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_id = intval($_POST['booking_id']);
    $amount = floatval($_POST['amount']);
    $card_number = trim($_POST['card_number']);
    $expiry_date = trim($_POST['expiry_date']);
    $cvv = trim($_POST['cvv']);
    $user_id = $_SESSION['user_id'];

    // --- Start of original process_payment.php logic (adapted) ---

    // Basic validation
    if (empty($booking_id) || empty($amount) || empty($card_number) || empty($expiry_date) || empty($cvv)) {
        $_SESSION['payment_message'] = 'All payment fields are required.';
        header("Location: payment.php?booking_id=" . $booking_id);
        exit();
    }

    // In a real application, you would integrate with a payment gateway here.
    // For this example, we'll simulate a successful payment.

    $transaction_status = 'failed';
    $transaction_id = 'TRX' . uniqid(); // Simulated transaction ID

    // Simulate payment processing logic
    // If payment gateway returns success:
    $payment_successful = (rand(0, 100) > 10); // 90% chance of success

    if ($payment_successful) {
        $transaction_status = 'completed';
        $_SESSION['payment_message'] = 'Payment successful! Your booking is confirmed.';

        // Update booking status in database (assuming a 'status' column in 'bookings' table)
        $sql_update_booking = "UPDATE bookings SET status = ?, transaction_id = ? WHERE id = ? AND user_id = ?";
        if ($stmt_update = $conn->prepare($sql_update_booking)) {
            $new_status = 'Confirmed';
            $stmt_update->bind_param("ssii", $new_status, $transaction_id, $booking_id, $user_id);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            // Log error but don't stop user flow
            error_log("Failed to update booking status for ID " . $booking_id . ": " . $conn->error);
        }
    } else {
        $transaction_status = 'failed';
        $_SESSION['payment_message'] = 'Payment failed. Please check your details or try again.';
        // Optionally, store failed transaction attempt
    }

    // Log payment attempt (assuming a 'payments' table)
    $sql_log_payment = "INSERT INTO payments (booking_id, user_id, amount, transaction_id, status, payment_date) VALUES (?, ?, ?, ?, ?, NOW())";
    if ($stmt_log = $conn->prepare($sql_log_payment)) {
        $stmt_log->bind_param("iidsi", $booking_id, $user_id, $amount, $transaction_id, $transaction_status);
        $stmt_log->execute();
        $stmt_log->close();
    } else {
        error_log("Failed to log payment attempt for booking ID " . $booking_id . ": " . $conn->error);
    }

    // --- End of original process_payment.php logic ---

    $conn->close();
    header("Location: payment.php?booking_id=" . $booking_id); // Redirect back to payment page to show status
    exit();
}

$conn->close();
// If not a POST request, redirect to booking or home page
header("Location: booking.php");
exit();
?>