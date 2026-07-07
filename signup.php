<?php
session_start();
// No login check needed here, this is the registration page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler - Sign Up</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/signup.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Traveler</div>
            <ul class="nav-links">
                <li><a href="mainPage.php">Home</a></li>
                <li><a href="destination.php">Destinations</a></li>
                <li><a href="gallery.php">Gallery</a></li>
                <li><a href="guide.php">Guides</a></li>
                <li><a href="feedback.php">Feedback</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><span class="user-greeting">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span></li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="index.php">Login</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="signup-page">
        <h1>Create Your Traveler Account</h1>
        <p>Join us to discover amazing destinations and plan your next adventure.</p>

        <?php
        // Display session messages (e.g., registration success/failure)
        if (isset($_SESSION['message'])) {
            echo '<p class="message">' . htmlspecialchars($_SESSION['message']) . '</p>';
            unset($_SESSION['message']); // Clear the message after displaying
        }
        ?>

        <div class="signup-form-container">
            <form action="save.php" method="POST" class="signup-form">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone (Optional):</label>
                    <input type="text" id="phone" name="phone">
                </div>
                <button type="submit" class="btn">Sign Up</button>
            </form>
            <p class="login-link">Already have an account? <a href="index.php">Login here</a></p>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Traveler. All rights reserved.</p>
    </footer>
</body>
</html>