<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $upload_dir = '../assets/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    
    $file = $_FILES['file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_name = 'upload_' . time() . '_' . uniqid() . '.' . strtolower($ext);
    $target = $upload_dir . $new_name;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode([
            'success' => true,
            'url' => 'assets/uploads/' . $new_name,
            'name' => $file['name'],
            'type' => $ext
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Upload failed']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No file provided']);
}
