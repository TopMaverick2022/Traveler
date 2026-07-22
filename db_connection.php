<?php
// Centralized Database Connection

// Ensure config.php is included for database credentials
require_once 'config.php';

// CodeQuality: Removed redundant local variables; constants can be used directly.


// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Security: Log the detailed error for administrators without exposing it to the end-user.
    error_log("Database Connection Error: " . $conn->connect_error);
    // Security/UX: Display a generic error message to the user instead of sensitive details.
    die("An error occurred while connecting to the database. Please try again later.");
}