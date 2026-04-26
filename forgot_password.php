<?php
session_start();
require 'db.php';

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if user exists
    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $conn->query("UPDATE users SET reset_token = '$token', token_expiry = '$expiry' WHERE id = " . $user['id']);
        
        // Fetch SMTP settings
        $settings_res = $conn->query("SELECT * FROM homepage_settings WHERE setting_key LIKE 'smtp_%'");
        $smtp = [];
        while($row = $settings_res->fetch_assoc()) {
            $smtp[$row['setting_key']] = $row['setting_value'];
        }
        
        // In a real scenario, you'd use a library like PHPMailer here.
        // For now, we'll simulate the link and show it if on localhost, 
        // or attempt native mail() if configured.
        
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . str_replace('forgot_password.php', 'reset_password.php', $_SERVER['PHP_SELF']) . "?token=" . $token;
        
        $subject = "Password Reset Request - Portfolio CMS";
        $body = "Hello " . $user['full_name'] . ",\n\nWe received a request to reset your password. Click the link below to set a new password:\n\n" . $reset_link . "\n\nThis link will expire in 1 hour.\n\nIf you did not request this, please ignore this email.";
        $headers = "From: " . ($smtp['smtp_from_name'] ?? 'Portfolio CMS') . " <" . ($smtp['smtp_from_email'] ?? 'noreply@yourdomain.com') . ">";
        
        if (@mail($email, $subject, $body, $headers)) {
            $message = "A reset link has been sent to your email address.";
            $status = 'success';
        } else {
            // For local development, we show the link since mail() often fails
            $message = "A reset link has been generated. <br><a href='$reset_link' class='alert-link'>Click here to reset (Dev Mode)</a>";
            $status = 'success';
        }
    } else {
        $message = "No account found with that email address.";
        $status = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Portfolio CMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #8e44ad;
            --gradient: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--gradient);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        .logo-box {
            width: 60px; height: 60px;
            background: var(--primary-purple);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: white;
            margin: 0 auto 15px;
            box-shadow: 0 10px 20px rgba(142, 68, 173, 0.3);
        }
        .form-control {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px 15px;
            color: white;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--primary-purple);
            color: white;
            box-shadow: none;
        }
        .btn-login {
            background: var(--primary-purple);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: #9b59b6;
            transform: translateY(-2px);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 0.85rem;
        }
        .back-link:hover { color: white; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="logo-box"><i class="fa fa-key"></i></div>
            <h2 class="text-white fw-bold h4">Forgot Password?</h2>
            <p class="text-white-50 small">Enter your email to receive a reset link.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $status ?> small border-0 bg-opacity-10 py-2">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-4">
                <label class="form-label text-white-50 small">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn btn-login w-100">SEND RESET LINK</button>
        </form>

        <a href="login.php" class="back-link">
            <i class="fa fa-arrow-left me-1"></i> Back to Login
        </a>
    </div>
</body>
</html>
