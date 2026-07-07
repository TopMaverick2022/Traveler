<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_email = trim($_POST['username_email']);
    $password = $_POST['password'];

    // Prepare SQL statement to fetch user by username or email
    $stmt = $conn->prepare("SELECT customer_id, username, email, password, role FROM customer WHERE username = ? OR email = ?");
    if ($stmt === false) {
        $_SESSION['error'] = "Database error: Could not prepare statement. " . $conn->error;
        header("Location: index.php");
        exit();
    }
    $stmt->bind_param("ss", $username_email, $username_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['customer_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['message'] = "Login successful! Welcome, " . $user['username'] . ".";

            // Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: mainPage.php");
            }
            exit();
        } else {
            // Invalid password
            $_SESSION['error'] = "Invalid username/email or password.";
            header("Location: index.php");
            exit();
        }
    } else {
        // User not found or multiple users (shouldn't happen with unique username/email)
        $_SESSION['error'] = "Invalid username/email or password.";
        header("Location: index.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: index.php"); // Redirect if accessed directly
    exit();
}
