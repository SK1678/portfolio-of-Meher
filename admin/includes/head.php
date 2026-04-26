<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?= $page_title ?? 'CMS' ?></title>
    <link rel="icon" type="image/png" href="/po/assets/img/admin-favicon.png?v=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #1e1b2e;
            --sidebar-active: #3f2b6e;
            --primary-purple: #8e44ad;
            --bg-light: #f4f6f9;
            --text-gray: #a0a0a0;
        }

        body { font-family: 'Outfit', sans-serif; background: var(--bg-light); color: #333; }
        
        .sidebar { 
            width: 260px; 
            height: 100vh; 
            background: var(--sidebar-bg); 
            color: #fff; 
            position: fixed; 
            left: 0; 
            top: 0; 
            padding: 25px 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }
        
        .brand-section { padding: 0 25px 30px; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 20px; }
        .brand-logo { width: 40px; height: 40px; background: var(--primary-purple); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-right: 12px; }
        .brand-name h5 { margin: 0; font-weight: 700; font-size: 1rem; }
        .brand-name span { font-size: 0.75rem; color: var(--text-gray); }

        .nav-label { padding: 10px 25px; font-size: 0.7rem; font-weight: 700; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; }
        
        .nav-link { 
            color: #d1d1d1; 
            padding: 12px 25px; 
            display: flex; 
            align-items: center; 
            font-size: 0.9rem; 
            transition: all 0.3s;
            border-left: 4px solid transparent;
            text-decoration: none;
        }
        .nav-link i { margin-right: 15px; width: 20px; text-align: center; font-size: 1rem; }
        .nav-link:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .nav-link.active { background: var(--sidebar-active); color: #fff; border-left-color: var(--primary-purple); }

        .main-content { margin-left: 260px; padding: 0; min-height: 100vh; padding-top: 70px; /* Space for fixed header */ }
        
        .top-header {
            background: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            position: fixed;
            top: 0;
            right: 0;
            width: calc(100% - 260px);
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .breadcrumb-custom { font-size: 0.85rem; color: var(--text-gray); }
        .breadcrumb-custom span { color: #333; font-weight: 600; }
        
        .profile-footer {
            margin-top: auto;
            padding: 20px 25px;
            background: rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .user-info { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 35px; height: 35px; background: var(--primary-purple); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; }
        .user-details h6 { margin: 0; font-size: 0.85rem; font-weight: 700; color: #fff; }
        .user-details span { font-size: 0.7rem; color: var(--text-gray); }
        .logout-btn { color: var(--text-gray); cursor: pointer; transition: color 0.3s; }
        .logout-btn:hover { color: #ff4757; }

        .card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); margin-bottom: 24px; }
        .card-title { font-weight: 700; color: #1e1b2e; }
        .style-group { background: #fafafa; border: 1px solid #eee; border-radius: 10px; padding: 15px; margin-top: 10px; }
        
        /* Dynamic Button Styles */
        .btn-row { background: #fdfdfd; padding: 20px; border-radius: 15px; margin-bottom: 20px; border: 1px solid #f0f0f0; position: relative; transition: all 0.3s; }
        .btn-row:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        
        /* FIXED: Styled Remove Button */
        .remove-btn { 
            position: absolute; 
            top: -10px; 
            right: -10px; 
            background: #ff4757; 
            color: #fff; 
            border-radius: 50%; 
            width: 24px; 
            height: 24px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; 
            border: 2px solid #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            font-size: 10px;
            transition: all 0.3s;
            z-index: 5;
        }
        .remove-btn:hover { background: #ff6b81; transform: scale(1.1); }

        .media-preview-container { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 15px; padding: 15px; background: #fff; border-radius: 12px; border: 1px solid #eee; }
        .media-wrapper { position: relative; width: 80px; height: 80px; }
        .media-item { width: 100%; height: 100%; border-radius: 8px; object-fit: cover; border: 1px solid #eee; }
        .delete-media { position: absolute; top: -5px; right: -5px; background: #ff4757; color: #fff; border-radius: 50%; width: 20px; height: 20px; font-size: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .media-video-icon { width: 100%; height: 100%; border-radius: 8px; background: #2c3e50; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        
        .rounded-pill { border-radius: 50px !important; }
        .shadow-sm { box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important; }
    </style>
</head>
<body>
