<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
require '../db.php';

$upload_dir = '../assets/uploads/';
$files = is_dir($upload_dir) ? array_diff(scandir($upload_dir), array('.', '..')) : [];

$media = [];
foreach ($files as $file) {
    $media[] = [
        'name' => $file,
        'url' => 'assets/uploads/' . $file,
        'type' => pathinfo($file, PATHINFO_EXTENSION)
    ];
}

header('Content-Type: application/json');
echo json_encode($media);
