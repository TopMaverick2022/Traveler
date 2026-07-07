<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Raw password from form
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $dob = trim($_POST['dob']);

    // Default role for new users
    $role = 'user';

    // Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if username or email already exists
    $stmt_check = $conn->prepare("SELECT customer_id FROM customer WHERE username = ? OR email = ?");
    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $_SESSION['error'] = "Username or Email already exists. Please choose a different one.";
        header("Location: signup.php");
        exit();
    }
    $stmt_check->close();

    // Prepare and bind for insertion
    $stmt = $conn->prepare("INSERT INTO customer (username, email, password, phone, address, dob, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        $_SESSION['error'] = "Database error: Could not prepare statement. " . $conn->error;
        header("Location: signup.php");
        exit();
    }
    $stmt->bind_param("sssssss", $username, $email, $hashed_password, $phone, $address, $dob, $role);

    // Execute the statement
    if ($stmt->execute()) {
        // Registration successful, log the user in automatically
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['message'] = "Registration successful! Welcome, " . $username . ".";
        header("Location: mainPage.php");
        exit();
    } else {
        $_SESSION['error'] = "Registration failed: " . $stmt->error;
        header("Location: signup.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: signup.php"); // Redirect if accessed directly
    exit();
}
