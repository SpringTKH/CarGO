<?php
// Prevent mysqli from throwing uncaught exceptions that leak connection arguments or passwords in stack traces
mysqli_report(MYSQLI_REPORT_OFF);

// Helper function to load environment variables from .env file without external dependencies
if (!function_exists('loadEnvFile')) {
    function loadEnvFile($filePath) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Strip surrounding quotes if present
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                // Set in environment if not already defined
                if (getenv($key) === false) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}

// Locate and load the root .env file
$envFilePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
loadEnvFile($envFilePath);

// Retrieve configuration from environment variables with fallbacks
$database_host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : ($_ENV['DB_HOST'] ?? '');
$database_port = getenv('DB_PORT') !== false ? intval(getenv('DB_PORT')) : intval($_ENV['DB_PORT'] ?? 3306);
$database_name = getenv('DB_NAME') !== false ? getenv('DB_NAME') : ($_ENV['DB_NAME'] ?? '');
$username      = getenv('DB_USER') !== false ? getenv('DB_USER') : ($_ENV['DB_USER'] ?? '');
$password      = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? '');

// Validate required database configuration settings
if (empty($database_host) || empty($database_name) || $username === '') {
    die("Database configuration error: Missing required settings (DB_HOST, DB_NAME, or DB_USER). Please ensure your .env file is configured based on .env.example.");
}

// Establish database connection safely without printing credentials on failure
$conn = @mysqli_connect($database_host, $username, $password, $database_name, $database_port);

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please ensure the database server is running and configuration settings are correct.");
}

$conn->set_charset("utf8mb4");
?>