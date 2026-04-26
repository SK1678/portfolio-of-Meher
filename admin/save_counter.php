<?php
session_start();
if (!isset($_SESSION['user_id'])) die("Access denied.");
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = $conn->real_escape_string($_POST['title'] ?? '');
    $value = $conn->real_escape_string($_POST['value'] ?? '');
    $icon = $conn->real_escape_string($_POST['icon'] ?? '');

    if ($id > 0) {
        $query = "UPDATE counters SET title='$title', value='$value', icon='$icon' WHERE id=$id";
    } else {
        $query = "INSERT INTO counters (title, value, icon) VALUES ('$title', '$value', '$icon')";
    }

    if ($conn->query($query)) {
        header("Location: /po/admin/services?status=success&tab=counters");
    } else {
        header("Location: /po/admin/services?status=error&tab=counters");
    }
}
?>
