<?php
session_start();
require 'db.php';

$message = '';
$status = '';
$valid_token = false;

if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    $result = $conn->query("SELECT * FROM users WHERE reset_token = '$token' AND token_expiry > NOW()");
    
    if ($result->num_rows > 0) {
        $valid_token = true;
        $user = $result->fetch_assoc();
    } else {
        $message = "Invalid or expired reset link. Please request a new one.";
        $status = 'danger';
    }
} else {
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password === $confirm_password) {
        if (strlen($password) >= 6) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $conn->query("UPDATE users SET password = '$hashed', reset_token = NULL, token_expiry = NULL WHERE id = " . $user['id']);
            
            $message = "Password reset successful! You can now login.";
            $status = 'success';
            $valid_token = false; // Hide form after success
        } else {
            $message = "Password must be at least 6 characters.";
            $status = 'danger';
        }
    } else {
        $message = "Passwords do not match.";
        $status = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Portfolio CMS</title>
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
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="logo-box"><i class="fa fa-shield-alt"></i></div>
            <h2 class="text-white fw-bold h4">Set New Password</h2>
            <p class="text-white-50 small">Create a strong password for your account.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $status ?> small border-0 bg-opacity-10 py-2 mb-3">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($valid_token): ?>
        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label text-white-50 small">New Password</label>
                <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
            </div>
            <div class="mb-4">
                <label class="form-label text-white-50 small">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
            </div>
            <button type="submit" class="btn btn-login w-100">UPDATE PASSWORD</button>
        </form>
        <?php else: ?>
            <a href="login.php" class="btn btn-login w-100 mt-2">BACK TO LOGIN</a>
        <?php endif; ?>
    </div>
</body>
</html>
