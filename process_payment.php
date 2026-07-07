<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to process payment.";
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_id = $_POST['booking_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    $card_number = $_POST['card_number'] ?? '';
    $card_name = $_POST['card_name'] ?? '';
    $expiry_month = $_POST['expiry_month'] ?? '';
    $expiry_year = $_POST['expiry_year'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    $customer_id = $_SESSION['user_id'];

    // Basic validation
    if (empty($booking_id) || empty($amount) || empty($card_number) || empty($card_name) || empty($expiry_month) || empty($expiry_year) || empty($cvv)) {
        $_SESSION['error'] = "All payment fields are required.";
        header("Location: payment.php?booking_id=" . htmlspecialchars($booking_id));
        exit();
    }

    // Further validation (e.g., card number format, expiry date, CVV length)
    if (!is_numeric($booking_id) || $amount <= 0 || !preg_match('/^[0-9]{13,19}$/', $card_number) || !preg_match('/^(0[1-9]|1[0-2])$/', $expiry_month) || !preg_match('/^[0-9]{2}$/', $expiry_year) || !preg_match('/^[0-9]{3,4}$/', $cvv)) {
        $_SESSION['error'] = "Invalid payment details provided.";
        header("Location: payment.php?booking_id=" . htmlspecialchars($booking_id));
        exit();
    }

    // --- Simulate Payment Gateway Interaction ---
    // In a real application, you would send these details to a payment gateway (Stripe, PayPal, etc.)
    // The gateway would return a success/failure response and a transaction ID.
    $payment_successful = true; // Simulate success
    $transaction_id = 'TRX-' . uniqid(); // Generate a unique transaction ID
    $payment_status = 'Completed';

    if ($payment_successful) {
        // Update booking status in the database
        $stmt = $conn->prepare("UPDATE booking SET status = ?, transaction_id = ? WHERE booking_id = ? AND customer_id = ?");
        if ($stmt === false) {
            $_SESSION['error'] = "Database error updating booking status: " . $conn->error;
            header("Location: payment.php?booking_id=" . htmlspecialchars($booking_id));
            exit();
        }
        $stmt->bind_param("ssii", $payment_status, $transaction_id, $booking_id, $customer_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Payment successful! Your booking (#" . htmlspecialchars($booking_id) . ") is confirmed. Transaction ID: " . htmlspecialchars($transaction_id) . ".";
            header("Location: mainPage.php"); // Redirect to main page or a confirmation page
            exit();
        } else {
            $_SESSION['error'] = "Payment processed but failed to update booking status: " . $stmt->error . ". Please contact support with transaction ID: " . htmlspecialchars($transaction_id) . ".";
            header("Location: payment.php?booking_id=" . htmlspecialchars($booking_id));
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Payment failed. Please try again or use a different payment method.";
        header("Location: payment.php?booking_id=" . htmlspecialchars($booking_id));
        exit();
    }

    $conn->close();
} else {
    $_SESSION['error'] = "Invalid access to payment processing.";
    header("Location: mainPage.php"); // Redirect if accessed directly
    exit();
}
