<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Fix: [Line 11] [Bug] Moved $_SESSION['message'] before session_destroy() (line 8).
// Setting the message after session_destroy() would prevent it from persisting.
// This ensures the message is set while the session is still active.
$_SESSION['message'] = "You have been successfully logged out.";

// Destroy the session
session_destroy();

// Redirect to the login page (index.php)
header("Location: index.php");
exit();