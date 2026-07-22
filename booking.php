<?php
session_start();

// Fix [Line 7] [Security] Session Fixation Vulnerability: Regenerate session ID after authentication to prevent fixation.
// This is done once per authenticated session to avoid changing ID on every request,
// ensuring the session ID changes from any pre-authentication ID.
if (isset($_SESSION['user_id']) && !isset($_SESSION['session_id_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['session_id_regenerated'] = true;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to make a booking.";
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

// Fix [Line 39] [Security] Cross-Site Request Forgery (CSRF) Vulnerability: Generate and store a CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
    // Fix [Line 39] [Security] Cross-Site Request Forgery (CSRF) Vulnerability: Verify CSRF token.
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
        // Regenerate token on failure to prevent replay attacks and allow user to retry
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Token is valid, regenerate for subsequent requests to enhance security (once-per-token mechanism).
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $customer_id = $_SESSION['user_id'];
        $booking_date = date('Y-m-d'); // Current date for booking

        // Fix [Line 53] [CodeQuality] The `travel_date` input is `trim`med but not explicitly validated for its date format or if it's a future date.
        // Add explicit validation for 'Y-m-d' format and ensure it's not a past date.
        $travel_date = trim($_POST['travel_date']);
        $today = new DateTime();
        $today->setTime(0, 0, 0); // Normalize to start of day for comparison
        $input_date_obj = DateTime::createFromFormat('Y-m-d', $travel_date);

        // Fix [Line 53] [CodeQuality] Using `intval()` for `num_travelers` is basic.
        // Replace with `filter_var($value, FILTER_VALIDATE_INT)` for more robust validation, distinguishing non-integers from valid 0.
        $num_travelers_input = trim($_POST['num_travelers']);
        $num_travelers = filter_var($num_travelers_input, FILTER_VALIDATE_INT);

        // Replaced original `if (empty($travel_date) || $num_travelers <= 0)` with granular validation checks.
        if (empty($travel_date)) {
            $error = "Travel date is required.";
        } elseif (!$input_date_obj || $input_date_obj->format('Y-m-d') !== $travel_date) {
            $error = "Invalid travel date format. Please use YYYY-MM-DD.";
        } elseif ($input_date_obj < $today) {
            $error = "Travel date cannot be in the past.";
        } elseif ($num_travelers === false || $num_travelers < 1) {
            // Checks if input is not a valid integer or is less than 1.
            $error = "Number of travelers must be a positive whole number.";
        } else {
            // All validations passed, proceed with booking logic
            $total_price = $destination_price * $num_travelers;
            $status = 'Pending'; // Default status

            $stmt = $conn->prepare("INSERT INTO booking (customer_id, destination_id, booking_date, travel_date, num_travelers, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                // Fix [Line 49] [Security] Information Disclosure: Replace raw DB error with generic message.
                // In a production environment, the actual error ($conn->error) should be logged internally.
                $error = "A database error occurred. Please try again later.";
            } else {
                $stmt->bind_param("iissids", $customer_id, $destination_id, $booking_date, $travel_date, $num_travelers, $total_price, $status);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Booking for '" . htmlspecialchars($destination_name) . "' submitted successfully! Total: $" . number_format($total_price, 2) . ". Please proceed to payment.";
                    header("Location: payment.php?booking_id=" . $conn->insert_id); // Redirect to payment with booking ID
                    exit();
                } else {
                    // Fix [Line 49] [Security] Information Disclosure: Replace raw DB error with generic message.
                    // In a production environment, the actual error ($stmt->error) should be logged internally.
                    $error = "Booking failed due to a database issue. Please try again later.";
                }
                $stmt->close();
            }
        }
    }
}
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
        <?php
        // Fix [Line 89] [CodeQuality] Unused Variable: The $message variable is initialized but never assigned a local value to be displayed.
        // The success message is handled via $_SESSION['message'] for cross-page redirects to payment.php.
        // This 'if ($message)' block is dead code and has been removed as it would never evaluate to true.
        ?>

        <?php if ($destination_id): ?>
        <div class="booking-form-container">
            <form action="booking.php?destination_id=<?php echo htmlspecialchars($destination_id); ?>" method="POST">
                <!-- Fix [Line 39] [Security] Cross-Site Request Forgery (CSRF) Vulnerability: Add a hidden CSRF token field. -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

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
<?php
// Fix [Line 71] [CodeQuality] The database connection (`$conn->close()`) is closed explicitly at the very end of the script.
// This ensures all PHP logic, including potential error handling or data preparation for the HTML,
// has completed before the connection is terminated. While PHP automatically closes connections at script end,
// explicit placement is a good practice if there's any ambiguity or complex script flow.
$conn->close();
?>