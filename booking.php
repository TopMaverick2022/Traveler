<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to make a booking.";
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$destination_id = $_GET['destination_id'] ?? null;
$destination_name = 'Selected Destination';
$destination_price = 0;
$message = '';
$error = '';

// Fetch destination details if ID is provided
if ($destination_id) {
    $stmt = $conn->prepare("SELECT destination_name, price FROM destination WHERE destination_id = ?");
    $stmt->bind_param("i", $destination_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $dest = $result->fetch_assoc();
        $destination_name = $dest['destination_name'];
        $destination_price = $dest['price'];
    } else {
        $error = "Destination not found.";
        $destination_id = null; // Invalidate destination if not found
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $destination_id) {
    $customer_id = $_SESSION['user_id'];
    $booking_date = date('Y-m-d'); // Current date for booking
    $travel_date = trim($_POST['travel_date']);
    $num_travelers = intval(trim($_POST['num_travelers']));
    $total_price = $destination_price * $num_travelers;
    $status = 'Pending'; // Default status

    if (empty($travel_date) || $num_travelers <= 0) {
        $error = "Please fill all required fields and ensure number of travelers is positive.";
    } else {
        $stmt = $conn->prepare("INSERT INTO booking (customer_id, destination_id, booking_date, travel_date, num_travelers, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            $error = "Database error: Could not prepare statement. " . $conn->error;
        } else {
            $stmt->bind_param("iissids", $customer_id, $destination_id, $booking_date, $travel_date, $num_travelers, $total_price, $status);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Booking for '" . htmlspecialchars($destination_name) . "' submitted successfully! Total: $" . number_format($total_price, 2) . ". Please proceed to payment.";
                header("Location: payment.php?booking_id=" . $conn->insert_id); // Redirect to payment with booking ID
                exit();
            } else {
                $error = "Booking failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Book Your Trip</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/booking.css">
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

    <main class="booking-page">
        <h1>Book Your Trip to <?php echo htmlspecialchars($destination_name); ?></h1>
        <p>Complete the form below to book your adventure.</p>

        <?php if ($error): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($message): // Display message if redirected here without errors but potentially with a success message from prev step ?>
            <p class="success-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($destination_id): ?>
        <div class="booking-form-container">
            <form action="booking.php?destination_id=<?php echo htmlspecialchars($destination_id); ?>" method="POST">
                <div class="form-group">
                    <label for="destination">Destination</label>
                    <input type="text" id="destination" value="<?php echo htmlspecialchars($destination_name); ?> (Price: $<?php echo htmlspecialchars(number_format($destination_price, 2)); ?>)" readonly>
                </div>

                <div class="form-group">
                    <label for="travel_date">Desired Travel Date</label>
                    <input type="date" id="travel_date" name="travel_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="num_travelers">Number of Travelers</label>
                    <input type="number" id="num_travelers" name="num_travelers" min="1" value="1" required>
                </div>

                <button type="submit" class="book-button">Proceed to Payment</button>
            </form>
        </div>
        <?php else: ?>
            <p>Please select a valid destination from our <a href="destination.php">Destinations page</a>.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>
