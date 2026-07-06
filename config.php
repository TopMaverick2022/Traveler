<?php
// Existing config values would be here.
// Adding database connection details as per project requirements.

// Ensure these are defined. Replace with your actual credentials.
if (!defined('DB_SERVER')) {
    define('DB_SERVER', 'localhost');
}
if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', 'root'); // Your database username
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', '');     // Your database password
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'travel');   // Your database name
}

// You might have other configurations here as well.
// For example, base URL, constants, etc.
?>