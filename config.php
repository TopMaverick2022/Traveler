<?php
// Database configuration (assuming db_connection.php uses these)
// All database credentials are now retrieved from environment variables for enhanced security,
// adherence to the principle of least privilege, and improved environment management.
define('DB_SERVER', getenv('DB_SERVER') ?: 'localhost'); // Retrieve DB server from environment variable. Default 'localhost' for development, but must be securely set for production.
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'app_user'); // Retrieve database username from environment variable. Replaces 'root' with a dedicated user ('app_user' as an example) for the principle of least privilege. This user needs to be created with only necessary permissions.
define('DB_PASSWORD', getenv('DB_PASSWORD')); // NO DEFAULT PROVIDED. An empty DB_PASSWORD is a critical security risk; it MUST be securely set in the environment. // [Fix 3] Consuming code MUST perform runtime validation to ensure this critical secret is present.
define('DB_NAME', getenv('DB_NAME') ?: 'travel'); // Retrieve database name from environment variable. Default 'travel' for development, but must be securely set for production.

// Stripe API keys
// Replace with your actual publishable and secret keys
// All Stripe keys are now retrieved from environment variables to prevent hardcoding secrets and facilitate environment-specific configurations.
// [Fix 1] Remove confusing placeholder; the publishable key should always be explicitly configured, even for testing.
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY')); // Retrieve Stripe Publishable Key from environment variable. It MUST be set in the environment; no default is provided to prevent placeholder confusion.
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY')); // NO DEFAULT PROVIDED. Secret keys MUST be securely set in the environment. // [Fix 3] Consuming code MUST perform runtime validation to ensure this critical secret is present.
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET')); // NO DEFAULT PROVIDED. Webhook secrets MUST be securely set in the environment. // [Fix 3] Consuming code MUST perform runtime validation to ensure this critical secret is present.

// Base URL for redirects (adjust as needed)
// Retrieve BASE_URL from an environment variable for portability and easier environment management.
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/traveler/'); // Retrieve BASE_URL from environment variable. Default 'http://localhost/traveler/' for local development.

// Other configurations
// ...

// For development, display all errors
// Conditionally enable error display only in development environments to prevent sensitive data leakage in production.
// [Fix 4] Default to 'production' if APP_ENV is not explicitly set, to prevent accidental information disclosure in potentially misconfigured environments.
$app_env = getenv('APP_ENV') ?: 'production'; // Determine the application environment, defaulting to 'production' for security.
if ($app_env === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // In production or other non-development environments, suppress error display to prevent information disclosure.
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL); // Still log all errors (usually to a file), but do not display them to users.
}
?>