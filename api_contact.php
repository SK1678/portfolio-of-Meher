<?php
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$name    = trim($conn->real_escape_string($_POST['name'] ?? ''));
$email   = trim($conn->real_escape_string($_POST['email'] ?? ''));
$subject = trim($conn->real_escape_string($_POST['subject'] ?? ''));
$message = trim($conn->real_escape_string($_POST['message'] ?? ''));

if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

try {
    // 1. Log to database
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);
    $stmt->execute();

    // 2. Send notification email
    $admin_email = $settings['contact_email'] ?? 'admin@example.com';
    $site_name = $settings['name'] ?? 'Portfolio CMS';
    
    $to = $admin_email;
    $mail_subject = "New Contact Message: " . $subject;
    $body = "You have received a new message from your portfolio contact form.\n\n" .
            "Name: $name\n" .
            "Email: $email\n" .
            "Subject: $subject\n\n" .
            "Message:\n$message\n\n" .
            "--- \n" .
            "Sent from $site_name";
    
    $headers = "From: " . ($settings['smtp_from_name'] ?? $site_name) . " <" . ($settings['smtp_from_email'] ?? 'noreply@yourportfolio.com') . ">";

    // Attempt to send mail (using native mail() for simplicity, or PHPMailer if preferred)
    // Note: Localhost mail() often requires configuration.
    @mail($to, $mail_subject, $body, $headers);

    echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
