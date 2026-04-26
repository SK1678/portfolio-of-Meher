<?php
session_start();
if (!isset($_SESSION['user_id'])) die("Access denied.");
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = $conn->real_escape_string($_POST['title'] ?? '');
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $icon = $conn->real_escape_string($_POST['icon'] ?? '');
    if ($id > 0) {
        $query = "UPDATE services SET title='$title', description='$description', icon='$icon' WHERE id=$id";
    } else {
        $query = "INSERT INTO services (title, description, icon) VALUES ('$title', '$description', '$icon')";
    }

    if ($conn->query($query)) {
        header("Location: /po/admin/services?status=success&tab=services");
    } else {
        header("Location: /po/admin/services?status=error&tab=services");
    }
}
?>
