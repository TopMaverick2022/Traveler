<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_email = trim($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username_email) || empty($password)) {
        $_SESSION['message'] = 'Please enter both username/email and password.';
        header('Location: index.php');
        exit();
    }

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT customer_id, username, password, role FROM customer WHERE username = ? OR email = ?");
    if ($stmt === false) {
        error_log('Prepare failed: ' . htmlspecialchars($conn->error));
        $_SESSION['message'] = 'Login failed due to an internal error.';
        header('Location: index.php');
        exit();
    }

    $stmt->bind_param("ss", $username_email, $username_email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $username, $hashed_password, $role);
    $stmt->fetch();

    if ($stmt->num_rows === 1 && password_verify($password, $hashed_password)) {
        // Login successful
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['message'] = 'Login successful!';

        if ($role === 'admin') {
            header('Location: admin.php'); // Redirect admin to admin panel
        } else {
            header('Location: mainPage.php'); // Redirect regular user to main page
        }
        exit();
    } else {
        // Invalid credentials
        $_SESSION['message'] = 'Invalid username/email or password.';
        header('Location: index.php');
        exit();
    }

    $stmt->close();
    $conn->close();
    exit();
} else {
    // If accessed directly without POST, redirect to login page
    header('Location: index.php');
    exit();
}
?>