<?php
session_start();
require_once 'db_connection.php'; // Include the new centralized database connection

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please log in to submit feedback.';
    header("Location: index.php");
    exit();
}

$username = htmlspecialchars($_SESSION['username']);
$user_role = htmlspecialchars($_SESSION['role']);

$feedback_message = '';
if (isset($_SESSION['feedback_message'])) {
    $feedback_message = $_SESSION['feedback_message'];
    unset($_SESSION['feedback_message']); // Clear the message after displaying
}

// Handle feedback submission (existing feedback.php logic)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = trim($_POST['subject']);
    $message_content = trim($_POST['message']);
    $user_id = $_SESSION['user_id']; // Get user ID from session

    if (empty($subject) || empty($message_content)) {
        $_SESSION['feedback_message'] = 'Subject and message cannot be empty.';
    } else {
        // Assuming 'feedback' table exists with columns: id, user_id, subject, message, created_at
        $sql = "INSERT INTO feedback (user_id, subject, message, created_at) VALUES (?, ?, ?, NOW())";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("iss", $user_id, $subject, $message_content);
            if ($stmt->execute()) {
                $_SESSION['feedback_message'] = 'Thank you for your feedback!';
            } else {
                $_SESSION['feedback_message'] = 'Error submitting feedback: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['feedback_message'] = 'Database error: Could not prepare statement.';
        }
    }
    // Redirect to self to prevent form resubmission and display message
    header("Location: feedback.php");
    exit();
}

// Close connection after all operations
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Feedback</title>
    <link rel="stylesheet" href="css/feedback.css">
    <link rel="stylesheet" href="css/style.css"> <!-- For header/footer styling -->
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="mainPage.php">Home</a></li>
                <li><a href="destination.php">Destinations</a></li>
                <li><a href="gallery.php">Gallery</a></li>
                <li><a href="guide.php">Guides</a></li>
                <li><a href="feedback.php">Feedback</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><span>Welcome, <?php echo $username; ?>!</span></li>
                    <li><a href="logout.php">Logout</a></li>
                    <?php if ($user_role === 'admin'): ?>
                        <li><a href="admin.php">Admin Panel</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="index.php">Login</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Provide Feedback</h1>
        <p>We'd love to hear your thoughts on your Traveler experience.</p>
        
        <?php if ($feedback_message): ?>
            <p style="color: <?php echo (strpos($feedback_message, 'Error') !== false || strpos($feedback_message, 'cannot be empty') !== false) ? 'red' : 'green'; ?>; text-align: center;"><?php echo htmlspecialchars($feedback_message); ?></p>
        <?php endif; ?>

        <!-- Original content of feedback.html (feedback form) starts here -->
        <form action="feedback.php" method="post" class="feedback-form">
            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" required>

            <label for="message">Your Message:</label>
            <textarea id="message" name="message" rows="8" required></textarea>

            <button type="submit">Submit Feedback</button>
        </form>
        <!-- End of original content -->

    </main>
    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>