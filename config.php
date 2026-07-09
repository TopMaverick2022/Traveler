<?php
// Database configuration (assuming db_connection.php uses these)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'travel');

// Stripe API keys
// Replace with your actual publishable and secret keys
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_STRIPE_PUBLISHABLE_KEY');
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_STRIPE_SECRET_KEY');
define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_STRIPE_WEBHOOK_SECRET');

// Base URL for redirects (adjust as needed)
define('BASE_URL', 'http://localhost/traveler/');

// Other configurations
// ...

// For development, display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>