<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page (index.php)
$_SESSION['message'] = "You have been successfully logged out.";
header("Location: index.php");
exit();
