<?php
session_start();

// [Fix 3]: Security - Implement CSRF protection by generating a token if one doesn't exist in the session.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to submit feedback.";
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // [Fix 3]: Security - Validate CSRF token to prevent Cross-Site Request Forgery attacks.
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token. Please try again.";
        // Regenerate token on failure to prevent replay attacks and allow a new attempt.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Token is valid, regenerate for the next request to prevent replay attacks.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // [Fix 1]: Bug - Align PHP processing with the described 'feedback.html' form fields ('name', 'email', 'feedbk').
        // The HTML form fields below are also updated to match these names to ensure consistency within this file.
        $name = trim($_POST['name'] ?? ''); // User's name from form
        $email = trim($_POST['email'] ?? ''); // User's email from form (not stored in feedback table, but processed)
        $feedbk = trim($_POST['feedbk'] ?? ''); // The actual feedback message from form

        $customer_id = $_SESSION['user_id']; // Get customer_id from session

        // Construct the 'subject' for the database, as the 'feedback.html' form (as described) doesn't have a dedicated 'subject' field.
        $subject_for_db = !empty($name) ? "Feedback from: " . $name : "Anonymous Feedback";
        // The actual message for the database
        $message_for_db = $feedbk;

        // [Fix 1]: Bug - Update validation to check the new required form fields ('name' and 'feedbk').
        if (empty($name) || empty($feedbk)) {
            $error = "Your Name and feedback message cannot be empty.";
        } else {
            // Assuming a feedback table exists: feedback_id, customer_id, subject, message, created_at
            $stmt = $conn->prepare("INSERT INTO feedback (customer_id, subject, message, created_at) VALUES (?, ?, ?, NOW())");
            if ($stmt === false) {
                // [Fix 2]: Security - Prevent exposure of sensitive database error details to the user.
                // Log the detailed error internally and provide a generic message to the user.
                error_log("Database error: Could not prepare statement. Customer ID: " . $customer_id . ". Error: " . $conn->error);
                $error = "An unexpected database error occurred. Please try again later.";
            } else {
                // [Fix 1]: Bug - Bind parameters using the new variables derived from the form fields.
                $stmt->bind_param("iss", $customer_id, $subject_for_db, $message_for_db);
                if ($stmt->execute()) {
                    $message = "Thank you for your feedback!";
                } else {
                    // [Fix 2]: Security - Prevent exposure of sensitive database error details to the user.
                    // Log the detailed error internally and provide a generic message to the user.
                    error_log("Failed to submit feedback for Customer ID: " . $customer_id . ". Error: " . $stmt->error);
                    $error = "Failed to submit feedback. Please try again later.";
                }
                $stmt->close();
            }
        }
    } // End of CSRF token validation else block
    // [Fix 4]: CodeQuality - Database connection should be closed consistently at the end of script execution.
    // Removed $conn->close() from here to be moved to script end, ensuring it always runs.
    // $conn->close(); // REMOVED FROM HERE
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Feedback</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/feedback.css">
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

    <main class="feedback-page">
        <h1>Your Feedback Matters</h1>
        <p>We appreciate your thoughts and suggestions.</p>

        <div class="feedback-form-container">
            <?php if ($message): ?>
                <p class="success-message"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form action="feedback.php" method="POST">
                <!-- [Fix 3]: Security - Add hidden CSRF token to protect against Cross-Site Request Forgery. -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <!-- [Fix 1]: Bug - Update HTML form fields to match the 'feedback.html' names ('name', 'email', 'feedbk') as described in the issue. -->
                <div class="form-group">
                    <label for="name">Your Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Your Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="feedbk">Message:</label>
                    <textarea id="feedbk" name="feedbk" rows="8" required></textarea>
                </div>
                <button type="submit" class="submit-button">Submit Feedback</button>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>
<?php
// [Fix 1]: CodeQuality - Explicitly close the database connection to ensure resources are released.
// This improves clarity and resource management, closing for both POST/GET requests and regardless of early exits.
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>