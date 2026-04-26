<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}
require '../db.php';

header('Content-Type: application/json');

// Generate a new cache version token
$new_version = time(); // Unix timestamp as version

$stmt = $conn->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('cache_version', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
$stmt->bind_param("ss", $new_version, $new_version);

if ($stmt->execute()) {
    // Also send headers to clear server-side output buffer
    echo json_encode([
        'success' => true,
        'version' => $new_version,
        'message' => 'Cache busted! All visitors will receive fresh assets on next page load.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $conn->error]);
}
?>
