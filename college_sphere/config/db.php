<?php
/**
 * Database Configuration
 * Update these settings according to your MySQL setup
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_sphere');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper unicode support
$conn->set_charset("utf8mb4");

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * Execute query and return result
 */
function execute_query($query) {
    global $conn;
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query Error: " . $conn->error);
        return false;
    }
    return $result;
}

/**
 * Get single row from database
 */
function get_row($query) {
    $result = execute_query($query);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Get all rows from database
 */
function get_all($query) {
    $result = execute_query($query);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

/**
 * Insert data and return last insert ID
 */
function insert_data($query) {
    global $conn;
    if (execute_query($query)) {
        return $conn->insert_id;
    }
    return false;
}

/**
 * Update or delete data
 */
function modify_data($query) {
    global $conn;
    if (execute_query($query)) {
        return $conn->affected_rows;
    }
    return false;
}

/**
 * Close database connection
 */
function close_connection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}
?>