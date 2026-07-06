<?php
session_start();
require_once 'db_connection.php';

// Redirect if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: mainPage.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['message'] = 'Please enter username and password.';
        header("Location: index.php");
        exit();
    }

    // Prepare a select statement
    $sql = "SELECT id, username, password, role FROM customer WHERE username = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("s", $username);

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Store result
            $stmt->store_result();

            // Check if username exists, if yes then verify password
            if ($stmt->num_rows == 1) {
                // Bind result variables
                $stmt->bind_result($id, $username, $hashed_password, $role);
                if ($stmt->fetch()) {
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
                        session_regenerate_id(); // Regenerate session ID for security

                        // Store data in session variables
                        $_SESSION['user_id'] = $id;
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $role;
                        $_SESSION['message'] = 'Login successful!';

                        // Redirect user to main page
                        header("Location: mainPage.php");
                        exit();
                    } else {
                        // Password is not valid
                        $_SESSION['message'] = 'Invalid username or password.';
                    }
                }
            } else {
                // Username doesn't exist
                $_SESSION['message'] = 'Invalid username or password.';
            }
        } else {
            $_SESSION['message'] = 'Oops! Something went wrong. Please try again later.';
        }

        // Close statement
        $stmt->close();
    } else {
        $_SESSION['message'] = 'Database error: Could not prepare statement.';
    }
}

// Close connection
$conn->close();

// Redirect to login page if authentication failed or accessed directly without POST
header("Location: index.php");
exit();
?>