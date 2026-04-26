<?php
require '../db.php';
echo "Checking about_items table...\n";
$res = $conn->query("SELECT DISTINCT type FROM about_items");
while($row = $res->fetch_assoc()) {
    echo "Found type: " . $row['type'] . "\n";
}

$certs = $conn->query("SELECT * FROM about_items WHERE type='certification'");
echo "Total Certifications: " . $certs->num_rows . "\n";
while($c = $certs->fetch_assoc()) {
    echo "- " . $c['title'] . " (" . $c['organization'] . ")\n";
}
?>
