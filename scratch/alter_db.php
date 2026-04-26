<?php
require '../db.php';
$conn->query("ALTER TABLE about_items MODIFY COLUMN type VARCHAR(50) NOT NULL");
echo "Table altered successfully!";
?>
