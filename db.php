<?php
// Set error reporting to catch issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dynamic Base Path Calculation
$base_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if (str_ends_with($base_path, '/admin')) {
    $base_path = substr($base_path, 0, -6);
}
if ($base_path === '/') {
    $base_path = '';
}
$base_path = rtrim($base_path, '/') . '/';
define('BASE_PATH', $base_path);

// 1. Check if configuration exists
if (!file_exists(__DIR__ . '/config.php')) {
    // If we are not already on the installer, redirect to it
    $current_file = basename($_SERVER['PHP_SELF']);
    if ($current_file !== 'install.php') {
        // Calculate relative path for redirection
        $depth = substr_count(str_replace("\\", "/", $_SERVER['PHP_SELF']), '/') - substr_count(str_replace("\\", "/", __DIR__), '/');
        $prefix = str_repeat("../", max(0, $depth - 1));

        header("Location: " . $prefix . "install.php");
        exit;
    }
} else {
    // 2. Load configuration and connect
    require_once __DIR__ . '/config.php';

    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Ensure database exists and select it
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db` COLLATE utf8mb4_general_ci");
    $conn->select_db($db);
}

// Helper to fetch site settings globally
$settings = [];
if (isset($conn)) {
    try {
        if ($conn->select_db($db)) {
            $res = $conn->query("SELECT setting_key, setting_value FROM homepage_settings");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }
        }

        // Set Timezone from settings
        if (isset($settings['timezone'])) {
            date_default_timezone_set($settings['timezone']);
        } else {
            date_default_timezone_set('Asia/Dhaka');
        }
    } catch (mysqli_sql_exception $e) {
        // Table doesn't exist or other DB error - only show error on public-facing pages
        $current_file = basename($_SERVER['PHP_SELF']);
        $is_install_or_admin = ($current_file === 'install.php' || str_contains($_SERVER['PHP_SELF'], '/admin/'));

        if (!$is_install_or_admin) {
            echo "<div style='background:#111; color:#fff; padding:50px; text-align:center; font-family:sans-serif;'>";
            echo "<h2>Database Tables Missing</h2>";
            echo "<p>It looks like your database '<b>$db</b>' is empty or the table 'homepage_settings' is missing. Please run the installer to set up your tables.</p>";
            echo "<a href='install.php' style='background:#ffcc00; color:#000; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;'>Run Installer</a>";
            echo "</div>";
            exit;
        }
    }
}

// Ensure Core Tables Exist
$conn->query("CREATE TABLE IF NOT EXISTS homepage_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS visitor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    page_url TEXT,
    user_agent TEXT,
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS post_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    comment TEXT NOT NULL,
    status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS file_vault (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    type ENUM('file', 'link', 'text') NOT NULL DEFAULT 'text',
    content LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Migration for existing tables (Compatible with all MySQL versions)
if (!function_exists('addColumnIfNotExists')) {
function addColumnIfNotExists($conn, $table, $column, $definition)
{
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}
}

addColumnIfNotExists($conn, 'visitor_logs', 'page_url', 'TEXT AFTER ip_address');
addColumnIfNotExists($conn, 'posts', 'views', 'INT DEFAULT 0 AFTER status');
addColumnIfNotExists($conn, 'posts', 'hero_image', 'VARCHAR(500) AFTER feature_image');
addColumnIfNotExists($conn, 'post_comments', 'name', 'VARCHAR(100) AFTER parent_id');
addColumnIfNotExists($conn, 'post_comments', 'email', 'VARCHAR(100) AFTER name');
addColumnIfNotExists($conn, 'post_comments', 'comment', 'TEXT AFTER email');
addColumnIfNotExists($conn, 'post_comments', 'is_read', 'TINYINT(1) DEFAULT 0 AFTER status');

// Ensure UNIQUE constraint on homepage_settings (Clean up duplicates first)
$index_check = $conn->query("SHOW INDEX FROM homepage_settings WHERE Column_name = 'setting_key' AND Non_unique = 0");
if ($index_check && $index_check->num_rows == 0) {
    // 1. Keep only the latest entry for each key (the one with the largest ID)
    $conn->query("DELETE t1 FROM homepage_settings t1 INNER JOIN homepage_settings t2 WHERE t1.id < t2.id AND t1.setting_key = t2.setting_key");
    
    // 2. Add the unique index
    $conn->query("ALTER TABLE homepage_settings ADD UNIQUE (setting_key)");
}

// Visitor Logging Logic
$is_admin = str_contains($_SERVER['PHP_SELF'], '/admin/');
if (!$is_admin && isset($conn)) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $page = $_SERVER['REQUEST_URI'];

    // Log visit if not recently logged from same IP for this specific page (e.g., within last 1 hour)
    $stmt = $conn->prepare("SELECT id FROM visitor_logs WHERE ip_address = ? AND page_url = ? AND visited_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1");
    $stmt->bind_param("ss", $ip, $page);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $log_stmt = $conn->prepare("INSERT INTO visitor_logs (ip_address, page_url, user_agent) VALUES (?, ?, ?)");
        $log_stmt->bind_param("sss", $ip, $page, $ua);
        $log_stmt->execute();
    }
}
?>