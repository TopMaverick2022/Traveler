<?php
session_start();

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
    $subject = trim($_POST['subject'] ?? '');
    $feedback_text = trim($_POST['feedback_text'] ?? '');
    $customer_id = $_SESSION['user_id']; // Get customer_id from session

    if (empty($subject) || empty($feedback_text)) {
        $error = "Subject and feedback message cannot be empty.";
    } else {
        // Assuming a feedback table exists: feedback_id, customer_id, subject, message, created_at
        $stmt = $conn->prepare("INSERT INTO feedback (customer_id, subject, message, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt === false) {
            $error = "Database error: Could not prepare statement. " . $conn->error;
        } else {
            $stmt->bind_param("iss", $customer_id, $subject, $feedback_text);
            if ($stmt->execute()) {
                $message = "Thank you for your feedback!";
            } else {
                $error = "Failed to submit feedback: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    $conn->close();
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
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="feedback_text">Message:</label>
                    <textarea id="feedback_text" name="feedback_text" rows="8" required></textarea>
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
