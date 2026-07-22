<?php
session_start();

// Check if a user is already logged in, redirect to main page
if (isset($_SESSION['user_id'])) {
    header("Location: mainPage.php");
    exit();
}

// Get and clear session messages/errors
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Sign In</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/signin.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="index.php">Traveler</a>
            </div>
            <ul class="nav-links">
                <!-- Removed historical placeholder comment block (original lines 30-33) as such metadata is better suited for version control or external documentation. -->
                <li><a href="index.php">Sign In</a></li>
                <li><a href="signup.php">Sign Up</a></li>
            </ul>
        </nav>
    </header>

    <main class="signin-container">
        <div class="signin-box">
            <h2>Sign In</h2>
            <?php if ($message): ?>
                <p class="success-message"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form action="signin.php" method="POST">
                <div class="form-group">
                    <label for="username_email">Username or Email</label>
                    <input type="text" id="username_email" name="username_email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="signin-button">Sign In</button>
            </form>
            <p class="signup-link">Don't have an account? <a href="signup.php">Sign Up</a></p>
        </div>
    </main>

    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>