<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to request a refund.";
    header("Location: index.php");
    exit();
}

// Check if user is an admin or the owner of the booking for refund requests
$is_admin = ($_SESSION['role'] === 'admin');

require_once 'db_connection.php';

$booking_id = $_GET['booking_id'] ?? null;
$message = '';
$error = '';
$booking_details = null;

// Fetch booking details for display
if ($booking_id) {
    $query = "SELECT b.booking_id, b.total_price, b.status, d.destination_name, c.username, b.transaction_id ";
    $query .= "FROM booking b JOIN destination d ON b.destination_id = d.destination_id JOIN customer c ON b.customer_id = c.customer_id ";
    $query .= "WHERE b.booking_id = ? ";
    // Only allow owner or admin to view refund page for a specific booking
    if (!$is_admin) {
        $query .= "AND b.customer_id = ?";
    }

    $stmt = $conn->prepare($query);
    if ($is_admin) {
        $stmt->bind_param("i", $booking_id);
    } else {
        $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $booking_details = $result->fetch_assoc();
    } else {
        $error = "Booking not found or you don't have permission to view this refund request.";
    }
    $stmt->close();
} else {
    $error = "No booking ID provided for refund.";
}

// Handle refund request submission (typically by admin after review, or user initiated)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_refund']) && $booking_details) {
    // In a real system, this would trigger a refund process with the payment gateway
    // and potentially require admin approval.

    if ($booking_details['status'] === 'Refunded' || $booking_details['status'] === 'Cancelled') {
        $error = "This booking has already been refunded or cancelled.";
    } else if ($is_admin) {
        // Admin can directly process refund (simulate)
        $new_status = 'Refunded';
        $stmt = $conn->prepare("UPDATE booking SET status = ? WHERE booking_id = ?");
        $stmt->bind_param("si", $new_status, $booking_id);
        if ($stmt->execute()) {
            $message = "Booking #" . htmlspecialchars($booking_id) . " successfully refunded by admin.";
            $booking_details['status'] = $new_status; // Update status for display
        } else {
            $error = "Failed to process refund: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // User requests refund - usually changes status to 'RefundRequested' for admin review
        $new_status = 'Refund Requested';
        $stmt = $conn->prepare("UPDATE booking SET status = ? WHERE booking_id = ? AND customer_id = ?");
        $stmt->bind_param("sii", $new_status, $booking_id, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $message = "Your refund request for booking #" . htmlspecialchars($booking_id) . " has been submitted for review.";
            $booking_details['status'] = $new_status; // Update status for display
        } else {
            $error = "Failed to submit refund request: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Refund Request</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Assuming a specific CSS for refund might be needed, or reuse payment.css/booking.css -->
    <link rel="stylesheet" href="css/payment.css">
    <style>
        .refund-container { max-width: 600px; margin: 40px auto; padding: 20px; background-color: #f9f9f9; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .refund-container h1, .refund-container h2 { text-align: center; color: #333; }
        .refund-details p { margin-bottom: 10px; }
        .refund-details strong { color: #555; }
        .refund-button { display: block; width: 100%; padding: 10px; background-color: #dc3545; color: white; border: none; border-radius: 5px; font-size: 1.1em; cursor: pointer; transition: background-color 0.3s ease; margin-top: 20px; }
        .refund-button:hover { background-color: #c82333; }
        .message { color: green; font-weight: bold; text-align: center; margin-bottom: 15px; }
        .error { color: red; font-weight: bold; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="mainPage.php">Traveler</a>
            </div>
            <ul class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="mainPage.php">Home</a></li>
                    <li><a href="destination.php">Destinations</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="guide.php">Guides</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                    <li><a href="info.php">About Us</a></li>
                    <li><span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="index.php">Sign In</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <div class="refund-container">
            <h1>Refund Request</h1>

            <?php if ($message): ?>
                <p class="message"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if ($booking_details): ?>
                <h2>Booking Details</h2>
                <div class="refund-details">
                    <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking_details['booking_id']); ?></p>
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($booking_details['username']); ?></p>
                    <p><strong>Destination:</strong> <?php echo htmlspecialchars($booking_details['destination_name']); ?></p>
                    <p><strong>Total Price:</strong> $<?php echo htmlspecialchars(number_format($booking_details['total_price'], 2)); ?></p>
                    <p><strong>Current Status:</strong> <?php echo htmlspecialchars($booking_details['status']); ?></p>
                    <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($booking_details['transaction_id'] ?? 'N/A'); ?></p>
                </div>

                <?php if ($booking_details['status'] === 'Refunded'): ?>
                    <p style="text-align: center; color: green; font-weight: bold;">This booking has already been successfully refunded.</p>
                <?php elseif ($booking_details['status'] === 'Refund Requested'): ?>
                    <p style="text-align: center; color: orange; font-weight: bold;">A refund for this booking is already under review.</p>
                <?php else: // Can request refund if not already refunded or requested ?>
                    <form action="refund.php?booking_id=<?php echo htmlspecialchars($booking_id); ?>" method="POST">
                        <input type="hidden" name="request_refund" value="1">
                        <button type="submit" class="refund-button" onclick="return confirm('Are you sure you want to request a refund for this booking?');">
                            <?php echo $is_admin ? 'Process Refund' : 'Request Refund'; ?>
                        </button>
                    </form>
                    <p style="text-align: center; margin-top: 15px; font-size: 0.9em; color: #777;">Refunds are subject to terms and conditions.</p>
                <?php endif; ?>

            <?php else: ?>
                <p style="text-align: center;">Please provide a valid booking ID to initiate a refund request.</p>
                <?php if ($is_admin): ?>
                    <p style="text-align: center;"><a href="admin_op.php?action=manage_bookings">Back to Manage Bookings</a></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>
