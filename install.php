<?php
session_start();

// If already installed, check if tables exist before redirecting
if (file_exists('config.php')) {
    require_once 'config.php';
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli($host, $user, $pass, $db);
        if (!$conn->connect_error) {
            $res = $conn->query("SELECT 1 FROM homepage_settings LIMIT 1");
            if ($res !== false && !isset($_GET['reinstall'])) {
                header("Location: index.php");
                exit;
            }
        }
    } catch (Exception $e) {
        // Table or DB missing, allow installer to run
    }
}

// Pre-fill if config exists
$db_host = $host ?? 'localhost';
$db_user = $user ?? 'root';
$db_pass = $pass ?? '';
$db_name = $db ?? 'portfolio';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 1) {
        $host = $_POST['db_host'];
        $user = $_POST['db_user'];
        $pass = $_POST['db_pass'];
        $db   = $_POST['db_name'];

        // Test connection
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli($host, $user, $pass);
            $_SESSION['install_db'] = [
                'host' => $host,
                'user' => $user,
                'pass' => $pass,
                'name' => $db
            ];
            header("Location: install.php?step=2");
            exit;
        } catch (Exception $e) {
            $error = "Connection failed: " . $e->getMessage();
        }
    } elseif ($step == 2) {
        $db_info = $_SESSION['install_db'];
        $admin_user = $_POST['admin_user'];
        $admin_pass = $_POST['admin_pass'];
        $admin_name = $_POST['admin_name'];

        try {
            $conn = new mysqli($db_info['host'], $db_info['user'], $db_info['pass']);
            $db_name = $db_info['name'];
            
            // Attempt to create database
            $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn->select_db($db_name);
        } catch (mysqli_sql_exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }

        // --- DATABASE SCHEMA ---
        $queries = [
            "CREATE TABLE IF NOT EXISTS homepage_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(50) UNIQUE NOT NULL,
                setting_value TEXT
            )",
            "CREATE TABLE IF NOT EXISTS about_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                organization VARCHAR(255) NOT NULL,
                period VARCHAR(100) NOT NULL,
                duration VARCHAR(100),
                description TEXT,
                attachment TEXT,
                attachment_label VARCHAR(255),
                sort_order INT DEFAULT 0
            )",
            "CREATE TABLE IF NOT EXISTS item_attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                label VARCHAR(255) NOT NULL,
                url TEXT NOT NULL,
                is_protected TINYINT(1) DEFAULT 0,
                password VARCHAR(255),
                FOREIGN KEY (item_id) REFERENCES about_items(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS skills (
                id INT AUTO_INCREMENT PRIMARY KEY,
                skill_name VARCHAR(255) NOT NULL,
                percentage INT NOT NULL DEFAULT 0,
                sort_order INT DEFAULT 0
            )",
            "CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(150) NOT NULL UNIQUE,
                parent_id INT DEFAULT NULL
            )",
            "CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                content LONGTEXT,
                excerpt TEXT,
                feature_image VARCHAR(500),
                hero_image VARCHAR(500),
                status ENUM('published','draft') DEFAULT 'draft',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS post_categories (
                post_id INT NOT NULL,
                category_id INT NOT NULL,
                PRIMARY KEY (post_id, category_id)
            )",
            "CREATE TABLE IF NOT EXISTS post_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                parent_id INT DEFAULT NULL,
                author_name VARCHAR(100) NOT NULL,
                author_email VARCHAR(150) NOT NULL,
                content TEXT NOT NULL,
                status ENUM('approved','pending','spam') DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS post_attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                label VARCHAR(255) DEFAULT '',
                url VARCHAR(500) DEFAULT '',
                is_protected TINYINT(1) DEFAULT 0,
                password VARCHAR(255) DEFAULT ''
            )",
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100),
                role ENUM('admin', 'editor') DEFAULT 'admin',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                icon VARCHAR(100),
                sort_order INT DEFAULT 0
            )",
            "CREATE TABLE IF NOT EXISTS counters (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                value VARCHAR(50) NOT NULL,
                icon VARCHAR(100),
                sort_order INT DEFAULT 0
            )"
        ];

        if (empty($error)) {
            try {
                foreach ($queries as $q) {
                    $conn->query($q);
                }
            } catch (mysqli_sql_exception $e) {
                $error = "Error creating tables: " . $e->getMessage();
            }
        }

        if (empty($error)) {
            // Seed Categories
            $conn->query("INSERT IGNORE INTO categories (name, slug, parent_id) VALUES ('Blog', 'blog', NULL)");
            $conn->query("INSERT IGNORE INTO categories (name, slug, parent_id) VALUES ('Portfolio', 'portfolio', NULL)");

            // Create Admin
            $hp = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'admin')");
            $stmt->bind_param("sss", $admin_user, $hp, $admin_name);
            $stmt->execute();

            // Seed Settings
            $defaults = [
                'site_title' => 'Meher Kanti Sarkar | Portfolio',
                'name' => $admin_name,
                'hero_name_style' => json_encode(['size'=>'5','font'=>'Outfit','color_type'=>'plain','color_1'=>'#ffffff','color_2'=>'#ffcc00']),
                'title' => 'I\'m a Designer, Developer',
                'hero_title_style' => json_encode(['size'=>'1.5','font'=>'Outfit','color_type'=>'plain','color_1'=>'#dddddd','color_2'=>'#ffffff']),
                'hero_buttons' => json_encode([['text'=>'MORE ABOUT ME','link'=>'#about','color'=>'#ffcc00'],['text'=>'DOWNLOAD CV','link'=>'#','color'=>'#ffffff']]),
                'bg_type' => 'image',
                'bg_media' => 'assets/uploads/default_bg.jpg',
                'post_hero_bg' => '#333333'
            ];
            foreach ($defaults as $k => $v) {
                $sv = $conn->real_escape_string($v);
                $conn->query("INSERT IGNORE INTO homepage_settings (setting_key, setting_value) VALUES ('$k', '$sv')");
            }

            // Write config.php
            $config_content = "<?php\n// Auto-generated configuration file\n\$host = '{$db_info['host']}';\n\$user = '{$db_info['user']}';\n\$pass = '{$db_info['pass']}';\n\$db   = '{$db_info['name']}';\n?>";
            file_put_contents('config.php', $config_content);
            
            header("Location: install.php?step=3");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Portfolio Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #0f0f0f; color: #fff; font-family: 'Inter', sans-serif; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .install-card { background: #1a1a1a; border: 1px solid #333; border-radius: 20px; width: 500px; padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .brand-logo { width: 60px; height: 60px; background: #ffcc00; color: #000; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 20px; }
        .form-control { background: #222; border: 1px solid #444; color: #fff; border-radius: 10px; padding: 12px; }
        .form-control:focus { background: #222; border-color: #ffcc00; color: #fff; box-shadow: none; }
        .btn-primary { background: #ffcc00; border: none; color: #000; font-weight: 700; border-radius: 10px; padding: 12px; transition: 0.3s; }
        .btn-primary:hover { background: #e6b800; transform: translateY(-2px); }
        .step-indicator { display: flex; justify-content: center; gap: 10px; margin-bottom: 30px; }
        .step-dot { width: 10px; height: 10px; border-radius: 50%; background: #444; }
        .step-dot.active { background: #ffcc00; }
        .alert { border-radius: 10px; border: none; background: #3d0000; color: #ff8080; }
    </style>
</head>
<body>

<div class="install-card">
    <div class="brand-logo"><i class="fa fa-rocket"></i></div>
    <h3 class="text-center fw-bold mb-4">Installer</h3>

    <div class="step-indicator">
        <div class="step-dot <?= $step >= 1 ? 'active' : '' ?>"></div>
        <div class="step-dot <?= $step >= 2 ? 'active' : '' ?>"></div>
        <div class="step-dot <?= $step >= 3 ? 'active' : '' ?>"></div>
    </div>

    <?php if ($error): ?>
        <div class="alert small mb-4"><i class="fa fa-exclamation-triangle me-2"></i> <?= $error ?></div>
    <?php endif; ?>

    <?php if ($step == 1): ?>
        <form method="POST">
            <h5 class="mb-4 text-center">Database Setup</h5>
            <div class="mb-3">
                <label class="form-label small fw-bold">Database Host</label>
                <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($db_host) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Database Username</label>
                <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($db_user) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Database Password</label>
                <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars($db_pass) ?>">
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Database Name</label>
                <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($db_name) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Test & Continue <i class="fa fa-arrow-right ms-2"></i></button>
        </form>

    <?php elseif ($step == 2): ?>
        <form method="POST">
            <h5 class="mb-4 text-center">Admin Configuration</h5>
            <div class="mb-3">
                <label class="form-label small fw-bold">Admin Full Name</label>
                <input type="text" name="admin_name" class="form-control" placeholder="Meher Kanti Sarkar" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Admin Username</label>
                <input type="text" name="admin_user" class="form-control" value="admin" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Admin Password</label>
                <input type="password" name="admin_pass" class="form-control" placeholder="Create a secure password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Finish Installation <i class="fa fa-check-circle ms-2"></i></button>
        </form>

    <?php elseif ($step == 3): ?>
        <div class="text-center py-4">
            <div class="display-1 text-success mb-4"><i class="fa fa-circle-check"></i></div>
            <h4 class="fw-bold mb-3">Installation Successful!</h4>
            <p class="text-muted mb-4">Your portfolio system is ready to use. You can now log in to the dashboard.</p>
            <a href="login.php" class="btn btn-primary w-100">Log In to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
