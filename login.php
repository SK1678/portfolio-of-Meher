<?php
session_start();
require 'db.php';

// If already logged in, redirect to admin
if (isset($_SESSION['user_id'])) {
    header("Location: admin/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username = '$username'");
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            header("Location: admin/index.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Portfolio CMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #8e44ad;
            --dark-bg: #0f0c29;
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
            overflow: hidden;
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
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-box {
            width: 60px;
            height: 60px;
            background: var(--primary-purple);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin: 0 auto 15px;
            box-shadow: 0 10px 20px rgba(142, 68, 173, 0.3);
        }

        .login-header h2 {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .form-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px 15px;
            color: white;
            transition: all 0.3s;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 4px rgba(142, 68, 173, 0.15);
            color: white;
        }

        .btn-login {
            background: var(--primary-purple);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-top: 10px;
            transition: all 0.3s;
            color: white;
        }

        .btn-login:hover {
            background: #9b59b6;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(142, 68, 173, 0.4);
            color: white;
        }

        .error-alert {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #ff7675;
            border-radius: 12px;
            padding: 10px 15px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s;
        }

        .back-link:hover, .hover-white:hover {
            color: white !important;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <div class="logo-box">
                <i class="fa fa-palette"></i>
            </div>
            <h2>Welcome Back</h2>
            <p>Admin Control Panel</p>
        </div>

        <?php if ($error): ?>
            <div class="error-alert">
                <i class="fa fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required>
            </div>
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Password</label>
                    <a href="forgot_password.php" class="text-white-50 small text-decoration-none hover-white">Forgot Password?</a>
                </div>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn btn-login w-100">SIGN IN</button>
        </form>

        <a href="index.php" class="back-link">
            <i class="fa fa-arrow-left me-1"></i> Back to Portfolio
        </a>
    </div>

</body>
</html>
