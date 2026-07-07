<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if (empty($username) || empty($email) || empty($password)) {
        $_SESSION['message'] = 'All fields are required for registration.';
        header('Location: signup.php');
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'user'; // Default role for new registrations

    // Check if username or email already exists using prepared statements
    $stmt_check = $conn->prepare("SELECT customer_id FROM customer WHERE username = ? OR email = ?");
    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $_SESSION['message'] = 'Username or email already exists.';
        header('Location: signup.php');
        $stmt_check->close();
        exit();
    }
    $stmt_check->close();

    // Insert new customer using prepared statements
    $stmt = $conn->prepare("INSERT INTO customer (username, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        error_log('Prepare failed: ' . htmlspecialchars($conn->error));
        $_SESSION['message'] = 'Registration failed due to an internal error.';
        header('Location: signup.php');
        exit();
    }

    $stmt->bind_param("sssss", $username, $email, $hashed_password, $phone, $role);

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Registration successful! Please log in.';
        header('Location: index.php'); // Redirect to login page
    } else {
        error_log('Execute failed: ' . htmlspecialchars($stmt->error));
        $_SESSION['message'] = 'Registration failed. Please try again.';
        header('Location: signup.php');
    }

    $stmt->close();
    $conn->close();
    exit();
} else {
    header('Location: signup.php'); // Redirect if accessed directly without POST
    exit();
}
?>