<?php
// [CodeQuality] Fixed: The filename 'infop.php' was ambiguous. This file has been renamed to 'db_connection.php' to clearly reflect its purpose.

/**
 * Encapsulates database connection logic.
 */
// [CodeQuality] Encapsulated the database connection logic within a class and a static method
// to prevent global namespace pollution and improve code organization.
class DatabaseConnection {
    /**
     * Establishes and returns a secure database connection.
     * @return mysqli The database connection object.
     * @throws RuntimeException If database configuration is missing or connection fails.
     */
    // [Performance] The database connection is now established only when this function is called,
    // and the connection object is returned for use by the caller, addressing the unused variable issue.
    public static function getConnection() {
        // [Security] Hardcoded credentials have been removed.
        // Database credentials are now sourced from environment variables, which is an industry-standard secure practice.
        // Ensure these environment variables (DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) are securely set on your server.
        // [Security] Added explicit null fallbacks to getenv calls to make them more robust,
        // ensuring variables are always initialized, even if the environment variable is not set.
        $host = getenv('DB_HOST') ?: null;
        $username = getenv('DB_USERNAME') ?: null;
        $password = getenv('DB_PASSWORD') ?: null;
        $dbname = getenv('DB_NAME') ?: null;

        if (!$host || !$username || !$password || !$dbname) {
            // In a production environment, this should log a critical error and potentially alert administrators.
            error_log("CRITICAL: Database environment variables are not set. Check DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME.");
            // [CodeQuality] Fixed: Replaced 'die()' with throwing a RuntimeException to allow graceful error handling by the calling application.
            throw new RuntimeException("System error: Database configuration missing. Please contact support.");
        }

        $db_connection = mysqli_connect($host, $username, $password, $dbname);

        // [Bug] Implemented robust error handling for database connection failures.
        // If the connection fails, the script will gracefully terminate with an error message
        // and log the specific error for debugging without exposing sensitive details to the end-user.
        if (mysqli_connect_errno()) {
            error_log("Failed to connect to MySQL: (" . mysqli_connect_errno() . ") " . mysqli_connect_error());
            // [CodeQuality] Fixed: Replaced 'die()' with throwing a RuntimeException to allow graceful error handling by the calling application.
            throw new RuntimeException("Failed to connect to the database. Please try again later."); // Generic user message
        }

        return $db_connection;
    }
}

?>