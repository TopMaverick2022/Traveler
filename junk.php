<?php

// [Line 2] [CodeQuality] The original filename 'junk.php' is highly misleading.
// It should be renamed to something descriptive like 'config/db.php' or 'includes/database.php'
// to reflect its purpose and improve maintainability. This comment serves as a placeholder
// acknowledging the issue, as file renaming cannot be done within the file content itself.


// [Line 10] [CodeQuality] Encapsulated database connection within a function to avoid global variables
// and provide a controlled point of access to the database object. This replaces the problematic
// global $db declaration and provides a cleaner interface.
function getDatabaseConnection() {
    // Prevent redundant database connections: 'static' ensures the connection is established only once
    // per request, improving performance and resource usage.
    static $db = null;

    if ($db === null) {
        // [Line 11] [Security] Removed insecure default fallbacks for database credentials.
        // In production, missing environment variables for critical credentials (host, user, name)
        // should prevent connection rather than defaulting to potentially insecure values.
        // This ensures secure deployment by requiring explicit configuration.
        $dbHost = getenv('DB_HOST');
        if (!$dbHost) {
            error_log("Security Error: DB_HOST environment variable not set. Cannot establish database connection.");
            die("<h1>Database Connection Error</h1><p>Configuration error: Missing database host.</p>");
        }

        $dbUser = getenv('DB_USER');
        if (!$dbUser) {
            error_log("Security Error: DB_USER environment variable not set. Cannot establish database connection.");
            die("<h1>Database Connection Error</h1><p>Configuration error: Missing database user.</p>");
        }

        // DB_PASSWORD can legitimately be an empty string, so no `die()` call if unset/empty.
        // It is fetched explicitly without any default fallback.
        $dbPassword = getenv('DB_PASSWORD');

        $dbName = getenv('DB_NAME');
        if (!$dbName) {
            error_log("Security Error: DB_NAME environment variable not set. Cannot establish database connection.");
            die("<h1>Database Connection Error</h1><p>Configuration error: Missing database name.</p>");
        }

        $db = mysqli_connect(
            $dbHost,
            $dbUser,
            $dbPassword,
            $dbName
        );

        // Added robust error handling for database connection failures (inherited from original Fix 4).
        // This prevents fatal errors, provides a user-friendly message,
        // and logs internal details for secure debugging.
        if (mysqli_connect_errno()) {
            error_log("Failed to connect to MySQL: " . mysqli_connect_error()); // Log the specific error
            die("<h1>Database Connection Error</h1><p>We are currently experiencing technical difficulties. Please try again later.</p>"); // Generic message for users
        }
    }
    return $db;
}

// The original file implicitly set a global $db variable upon inclusion.
// With this fix, consumers of this file should now explicitly call
// `getDatabaseConnection()` to obtain the database connection object.
// Example: $dbConnection = getDatabaseConnection();

?>