<?php
session_start();
require_once 'db_connection.php'; // Include the new centralized database connection

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in to process refunds.';
    header("Location: index.php");
    exit();
}

// Check if the user is an admin for refund functionality
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = 'Access denied. Only administrators can process refunds.';
    header("Location: mainPage.php"); // Redirect to a user page or admin panel
    exit();
}

// Any existing 'include "junk.php";' or 'include "infop.php";' should be removed.
// All database interactions in this file should now use the $conn variable.

$refund_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_id = intval($_POST['booking_id']);
    $amount_to_refund = floatval($_POST['amount']);

    if (empty($booking_id) || $amount_to_refund <= 0) {
        $refund_message = 'Invalid booking ID or refund amount.';
    } else {
        // --- Start of original refund.php logic (adapted) ---
        // Fetch booking details to verify
        $sql_booking = "SELECT user_id, transaction_id, amount FROM payments WHERE booking_id = ? AND status = 'completed'";
        if ($stmt_booking = $conn->prepare($sql_booking)) {
            $stmt_booking->bind_param("i", $booking_id);
            $stmt_booking->execute();
            $result_booking = $stmt_booking->get_result();

            if ($result_booking->num_rows == 1) {
                $payment_info = $result_booking->fetch_assoc();
                if ($amount_to_refund > $payment_info['amount']) {
                    $refund_message = 'Refund amount exceeds original payment.';
                } else {
                    // Simulate refund process with payment gateway
                    $refund_successful = (rand(0, 100) > 20); // 80% chance of success

                    if ($refund_successful) {
                        // Update payment status to refunded or add a new refund entry
                        $sql_update_payment = "UPDATE payments SET status = 'refunded', refund_amount = ? WHERE booking_id = ?";
                        if ($stmt_update = $conn->prepare($sql_update_payment)) {
                            $stmt_update->bind_param("di", $amount_to_refund, $booking_id);
                            $stmt_update->execute();
                            $stmt_update->close();
                        }

                        // Update booking status if necessary
                        $sql_update_booking_status = "UPDATE bookings SET status = 'Refunded' WHERE id = ?";
                        if ($stmt_update_booking_status = $conn->prepare($sql_update_booking_status)) {
                            $stmt_update_booking_status->bind_param("i", $booking_id);
                            $stmt_update_booking_status->execute();
                            $stmt_update_booking_status->close();
                        }

                        $refund_message = 'Refund for Booking ID ' . $booking_id . ' of $' . number_format($amount_to_refund, 2) . ' processed successfully.';
                    } else {
                        $refund_message = 'Refund failed for Booking ID ' . $booking_id . '. Please try again or contact support.';
                    }
                }
            } else {
                $refund_message = 'No completed payment found for Booking ID ' . $booking_id . ' or already refunded.';
            }
            $stmt_booking->close();
        } else {
            $refund_message = 'Database error: Could not prepare statement for refund verification.';
        }
        // --- End of original refund.php logic ---
    }
    $_SESSION['refund_message'] = $refund_message;
    header("Location: admin.php"); // Redirect back to admin panel or a refund history page
    exit();
}

$conn->close();

// Display refund form for admin if not a POST request
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Process Refund</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="mainPage.php">Home</a></li>
                <li><a href="admin.php">Admin Panel</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Process Refund</h1>
        <?php
        if (isset($_SESSION['refund_message'])) {
            echo '<p style="color: ' . (strpos($_SESSION['refund_message'], 'failed') !== false ? 'red' : 'green') . '; text-align: center;">' . htmlspecialchars($_SESSION['refund_message']) . '</p>';
            unset($_SESSION['refund_message']);
        }
        ?>
        <p>Use this form to process refunds for bookings.</p>
        <form action="refund.php" method="post" class="refund-form">
            <label for="booking_id">Booking ID:</label>
            <input type="number" id="booking_id" name="booking_id" required>

            <label for="amount">Amount to Refund:</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>

            <button type="submit">Process Refund</button>
        </form>

        <section>
            <h2>Recent Payments (for Admin reference)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>User ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Transaction ID</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    require_once 'db_connection.php'; // Re-open connection for display
                    $result = $conn->query("SELECT booking_id, user_id, amount, status, transaction_id, payment_date FROM payments ORDER BY payment_date DESC LIMIT 10");
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['booking_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                            echo "<td>$" . number_format($row['amount'], 2) . "</td>";
                            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['transaction_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['payment_date']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No recent payments found.</td></tr>";
                    }
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </section>

    </main>
    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>