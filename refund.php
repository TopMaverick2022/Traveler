<?php
session_start();
require_once 'vendor/autoload.php'; // For Stripe PHP SDK
require_once 'config.php';
require_once 'db_connection.php';

if (!defined('STRIPE_SECRET_KEY')) {
    error_log('Stripe secret key is not defined in config.php');
    $_SESSION['message'] = 'Refund processing error: Missing Stripe configuration.';
    header('Location: admin.php'); // Or appropriate redirect
    exit();
}

Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Ensure this page is only accessible by authorized users (e.g., admins)
// This is a basic check; implement robust authentication/authorization for admin actions.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['message'] = 'Access denied. You must be an administrator to process refunds.';
    header('Location: signin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
    $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);
    $refund_amount = filter_input(INPUT_POST, 'refund_amount', FILTER_VALIDATE_FLOAT); // For partial refunds

    if (!$payment_id) {
        $_SESSION['message'] = 'Invalid payment ID provided.';
        header('Location: admin.php');
        exit();
    }

    // 1. Fetch payment details from database
    $stmt = $conn->prepare("SELECT * FROM payments WHERE payment_id = ?");
    $stmt->bind_param('i', $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        $_SESSION['message'] = 'Payment not found in database.';
        header('Location: admin.php');
        exit();
    }

    if ($payment['status'] === 'refunded') {
        $_SESSION['message'] = 'This payment has already been fully refunded.';
        header('Location: admin.php');
        exit();
    }

    $payment_intent_id = $payment['payment_intent_id'];
    $original_amount = $payment['amount'];
    $currency = $payment['currency'];

    // Determine actual refund amount. If refund_amount is not specified or invalid, assume full refund.
    $amount_to_refund = ($refund_amount > 0 && $refund_amount <= $original_amount) ? $refund_amount : $original_amount;
    $amount_cents = round($amount_to_refund * 100);

    try {
        // 2. Create a refund request in your database with 'pending' status
        $stmt_insert_refund = $conn->prepare(
            "INSERT INTO refund_requests (customer_id, payment_id, reason, status, requested_at)
             VALUES (?, ?, ?, 'pending', NOW())"
        );
        $stmt_insert_refund->bind_param('iis', $payment['customer_id'], $payment_id, $reason);
        $stmt_insert_refund->execute();
        $request_id = $stmt_insert_refund->insert_id;
        $stmt_insert_refund->close();

        // 3. Initiate refund with Stripe API
        $refund = Stripe\Refund::create([
            'payment_intent' => $payment_intent_id,
            'amount' => $amount_cents,
            'reason' => 'requested_by_customer', // Or other appropriate reason
            // 'metadata' => ['refund_request_id' => $request_id] // Optional: link to your refund request ID
        ]);

        // 4. Update refund request status in database
        $refund_status = $refund->status; // e.g., 'pending', 'succeeded', 'failed'
        $refund_db_status = 'pending';
        $payment_new_status = $payment['status'];

        if ($refund_status === 'succeeded') {
            $refund_db_status = 'refunded';
            $_SESSION['message'] = 'Refund processed successfully!';

            // Update payment status
            if ($amount_to_refund >= $original_amount) {
                $payment_new_status = 'refunded';
            } else {
                $payment_new_status = 'partially_refunded';
            }
        } else {
            $refund_db_status = 'failed';
            $_SESSION['message'] = 'Refund initiated but failed or is pending Stripe review. Status: ' . $refund_status;
            error_log("Stripe Refund Status: " . $refund_status . " for payment_id={$payment_id}");
        }

        $stmt_update_refund = $conn->prepare(
            "UPDATE refund_requests SET status = ?, refund_id = ?, processed_at = NOW() WHERE request_id = ?"
        );
        $stmt_update_refund->bind_param('ssi', $refund_db_status, $refund->id, $request_id);
        $stmt_update_refund->execute();
        $stmt_update_refund->close();

        // Update original payment record status if successful or partially successful
        $stmt_update_payment = $conn->prepare(
            "UPDATE payments SET status = ? WHERE payment_id = ?"
        );
        $stmt_update_payment->bind_param('si', $payment_new_status, $payment_id);
        $stmt_update_payment->execute();
        $stmt_update_payment->close();

    } catch (Stripe\Exception\ApiErrorException $e) {
        // Catch Stripe-specific errors
        $error_message = $e->getMessage();
        $_SESSION['message'] = 'Stripe API Error during refund: ' . $error_message;
        error_log("Stripe Refund API Error: " . $error_message . " for payment_id={$payment_id}");
        $db_status_for_log = 'failed';
        // Update initial refund_request to failed status if it was inserted
        if (isset($request_id)) {
            $stmt_update_refund = $conn->prepare(
                "UPDATE refund_requests SET status = ?, processed_at = NOW() WHERE request_id = ?"
            );
            $stmt_update_refund->bind_param('si', $db_status_for_log, $request_id);
            $stmt_update_refund->execute();
            $stmt_update_refund->close();
        }
    } catch (Exception $e) {
        // Catch any other general PHP errors
        $error_message = $e->getMessage();
        $_SESSION['message'] = 'An unexpected error occurred during refund processing: ' . $error_message;
        error_log("General Refund Error: " . $error_message . " for payment_id={$payment_id}");
        $db_status_for_log = 'failed';
        if (isset($request_id)) {
            $stmt_update_refund = $conn->prepare(
                "UPDATE refund_requests SET status = ?, processed_at = NOW() WHERE request_id = ?"
            );
            $stmt_update_refund->bind_param('si', $db_status_for_log, $request_id);
            $stmt_update_refund->execute();
            $stmt_update_refund->close();
        }
    }

    header('Location: admin.php'); // Redirect back to admin panel or a refund history page
    exit();

} else {
    // Display form for refund request (e.g., in admin.php or a dedicated refund request page)
    // This part would typically be rendered on the admin interface.
    // For demonstration, we'll just show a simple message if accessed directly without POST.
    $_SESSION['message'] = 'Please submit refund requests via the administration interface.';
    header('Location: admin.php');
    exit();
}

// Example of how to integrate this into admin.php:
// In admin.php, when listing payments, add a form or link to initiate refund:
/*
<form action="refund.php" method="post" style="display:inline;">
    <input type="hidden" name="payment_id" value="<?php echo $payment['payment_id']; ?>">
    <input type="number" name="refund_amount" placeholder="Amount (optional)" step="0.01" min="0.01" max="<?php echo $payment['amount']; ?>">
    <textarea name="reason" placeholder="Reason for refund" required></textarea>
    <button type="submit" onclick="return confirm('Are you sure you want to process this refund?');">Process Refund</button>
</form>
*/
?>
