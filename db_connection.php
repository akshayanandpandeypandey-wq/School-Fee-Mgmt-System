<?php
/**
 * Database Connection File
 * ========================
 * Handles the connection to the MySQL database and provides utility
 * security and session management functions across the system.
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database credentials
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'school_fee_management';

// Create connection using MySQLi inside try-catch to handle connection failures gracefully
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli($db_host, $db_username, $db_password, $db_name);
    
    // Also verify if database has been initialized by checking if 'users' table exists.
    // If not, redirect to the database setup page.
    $table_check = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$table_check || $table_check->num_rows === 0) {
        header("Location: db_init.php");
        exit;
    }
} catch (mysqli_sql_exception $e) {
    // If connection failed (e.g. database doesn't exist or MySQL server is down),
    // redirect to db_init.php wizard to guide the user.
    header("Location: db_init.php");
    exit;
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Set timezone (India Standard Time - IST)
date_default_timezone_set('Asia/Kolkata');

/**
 * Enforce session authentication. Redirects to login if user is not authorized.
 * @param string|null $required_role - Role requirement ('Admin' or 'Cashier')
 */
function check_auth($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    if ($required_role && $_SESSION['role'] !== $required_role) {
        $_SESSION['flash_error'] = "⚠️ Unauthorized access! You do not have permission to view that page.";
        header("Location: index.php");
        exit;
    }
}

/**
 * Fetch a school setting value by key
 * @param string $key - Setting key
 * @param string $default - Default value if key is not found
 * @return string - Configuration value
 */
function get_setting($key, $default = '') {
    global $conn;
    static $settings_cache = [];
    
    if (isset($settings_cache[$key])) {
        return $settings_cache[$key];
    }
    
    $stmt = $conn->prepare("SELECT setting_value FROM school_settings WHERE setting_key = ?");
    if ($stmt) {
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $value = $result->fetch_assoc()['setting_value'];
            $settings_cache[$key] = $value;
            $stmt->close();
            return $value;
        }
        $stmt->close();
    }
    return $default;
}

/**
 * Format currency according to settings
 * @param float $amount - Decimal amount to format
 * @return string - Formatted amount with currency symbol
 */
function format_amount($amount) {
    $currency = get_setting('currency', 'INR');
    $symbol = '₹';
    switch ($currency) {
        case 'USD': $symbol = '$'; break;
        case 'EUR': $symbol = '€'; break;
        case 'GBP': $symbol = '£'; break;
        case 'INR': $symbol = '₹'; break;
    }
    return $symbol . number_format($amount, 2);
}
?>
