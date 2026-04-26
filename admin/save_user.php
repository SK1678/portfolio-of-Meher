<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $full_name = $conn->real_escape_string($_POST['full_name'] ?? '');
    $username  = $conn->real_escape_string($_POST['username'] ?? '');
    $role      = $conn->real_escape_string($_POST['role'] ?? 'editor');
    $password  = $_POST['password'] ?? '';

    if ($id > 0) {
        // Update existing user
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET full_name='$full_name', username='$username', role='$role', password='$hashed' WHERE id=$id");
        } else {
            $conn->query("UPDATE users SET full_name='$full_name', username='$username', role='$role' WHERE id=$id");
        }
        
        // If updating currently logged in user, update session
        if ($id == $_SESSION['user_id']) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['username']  = $username;
        }
    } else {
        // Create new user
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (full_name, username, password, role) VALUES ('$full_name', '$username', '$hashed', '$role')");
    }

    header("Location: " . BASE_PATH . "admin/settings?status=saved&tab=users");
    exit;
}
