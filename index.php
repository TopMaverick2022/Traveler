<?php
session_start();

// Redirect if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: mainPage.php");
    exit();
}

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Login</title>
    <link rel="stylesheet" href="css/signin.css">
    <link rel="stylesheet" href="css/style.css"> <!-- For general styling -->
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Login</a></li>
                <li><a href="signup.php">Sign Up</a></li>
            </ul>
        </nav>
    </header>
    <div class="container">
        <?php if ($message): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        
        <!-- Original content of index.html (login form) starts here -->
        <form action="signin.php" method="post">
            <h2>Login</h2>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
        <!-- End of original content -->
    </div>
    <footer>
        <p>&copy; 2023 Traveler. All rights reserved.</p>
    </footer>
</body>
</html>