<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']); // Assuming email is part of registration

    // Validate input
    if (empty($username) || empty($password) || empty($email)) {
        $_SESSION['message'] = 'All fields are required.';
        header("Location: signup.php");
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'user'; // Assign default role as 'user'

    // Prepare an insert statement using prepared statements to prevent SQL injection
    $sql = "INSERT INTO customer (username, password, email, role) VALUES (?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("ssss", $username, $hashed_password, $email, $role);

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Registration successful! Please log in.';
            header("Location: index.php");
            exit();
        } else {
            // Check for duplicate username error specifically
            if ($conn->errno == 1062) { // MySQL error code for duplicate entry
                $_SESSION['message'] = 'Registration failed: Username already exists.';
            } else {
                $_SESSION['message'] = 'Registration failed: ' . $stmt->error;
            }
            header("Location: signup.php");
            exit();
        }

        // Close statement
        $stmt->close();
    } else {
        $_SESSION['message'] = 'Database error: Could not prepare statement.';
        header("Location: signup.php");
        exit();
    }
}

// Close connection
$conn->close();

// If somehow accessed directly without POST, redirect to signup page
header("Location: signup.php");
exit();
?>