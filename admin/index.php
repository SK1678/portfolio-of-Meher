<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
require '../db.php';

// Fetch Statistics
// 1. Total Visitors (Unique IPs)
$total_visitors = $conn->query("SELECT COUNT(DISTINCT ip_address) FROM visitor_logs")->fetch_row()[0] ?? 0;

// 2. Visitor Change Rate (Last 24h vs Previous 24h)
$visitors_24h = $conn->query("SELECT COUNT(DISTINCT ip_address) FROM visitor_logs WHERE visited_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_row()[0] ?? 0;
$visitors_prev_24h = $conn->query("SELECT COUNT(DISTINCT ip_address) FROM visitor_logs WHERE visited_at BETWEEN DATE_SUB(NOW(), INTERVAL 48 HOUR) AND DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_row()[0] ?? 0;

$visitor_change = ($visitors_prev_24h > 0) ? (($visitors_24h - $visitors_prev_24h) / $visitors_prev_24h) * 100 : ($visitors_24h > 0 ? 100 : 0);

// 3. Message Change Rate (Last 24h vs Previous 24h)
$msgs_24h = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_row()[0] ?? 0;
$msgs_prev_24h = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 48 HOUR) AND DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_row()[0] ?? 0;

$message_change = ($msgs_prev_24h > 0) ? (($msgs_24h - $msgs_prev_24h) / $msgs_prev_24h) * 100 : ($msgs_24h > 0 ? 100 : 0);

// 4. Comment Change Rate (Last 24h vs Previous 24h)
$coms_24h = $conn->query("SELECT COUNT(*) FROM post_comments WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_row()[0] ?? 0;
$coms_prev_24h = $conn->query("SELECT COUNT(*) FROM post_comments WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 48 HOUR) AND DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_row()[0] ?? 0;

$comment_change = ($coms_prev_24h > 0) ? (($coms_24h - $coms_prev_24h) / $coms_prev_24h) * 100 : ($coms_24h > 0 ? 100 : 0);

$stats = [
    'visitors' => $total_visitors,
    'visitor_change' => round($visitor_change, 1),
    'message_change' => round($message_change, 1),
    'comment_change' => round($comment_change, 1),
    'projects' => $conn->query("SELECT COUNT(*) FROM posts p JOIN post_categories pc ON p.id = pc.post_id JOIN categories c ON c.id = pc.category_id WHERE c.slug = 'portfolio'")->fetch_row()[0] ?? 0,
    'posts' => $conn->query("SELECT COUNT(*) FROM posts p JOIN post_categories pc ON p.id = pc.post_id JOIN categories c ON c.id = pc.category_id WHERE c.slug = 'blog'")->fetch_row()[0] ?? 0,
    'messages' => $conn->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetch_row()[0] ?? 0,
    'comments' => $conn->query("SELECT COUNT(*) FROM post_comments WHERE is_read = 0")->fetch_row()[0] ?? 0
];

// Fetch Recent Messages
$recent_messages = [];
$res = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
while ($row = $res->fetch_assoc())
    $recent_messages[] = $row;

$page_title = "Dashboard Overview";
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-4 px-4">
        <!-- Notification Alert -->
        <?php if ($stats['messages'] > 0 || $stats['comments'] > 0): ?>
            <div class="alert alert-primary border-0 shadow-sm rounded-4 d-flex align-items-center mb-4" role="alert">
                <i class="fa fa-bell fa-lg me-3 pulse-text" style="color: #3f2b6e;"></i>
                <div>
                    <h6 class="fw-bold mb-1">Pending Tasks</h6>
                    <p class="small mb-0 opacity-75">
                        You have <strong><?= $stats['messages'] ?></strong> unread messages and
                        <strong><?= $stats['comments'] ?></strong> new comments to review.
                    </p>
                </div>
                <div class="ms-auto">
                    <a href="messages" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm">Review Now</a>
                </div>
            </div>
        <?php endif; ?>
        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <a href="visitors" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 stats-card-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-3 p-3 me-3">
                                    <i class="fa fa-users fa-lg"></i>
                                </div>
                                <div class="fw-bold text-muted small text-uppercase">Total Visitors</div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between">
                                <h2 class="fw-bold mb-0 text-dark"><?= $stats['visitors'] ?></h2>
                                <div class="small fw-bold <?= $stats['visitor_change'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <i class="fa <?= $stats['visitor_change'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?> me-1"></i>
                                    <?= abs($stats['visitor_change']) ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="messages" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 stats-card-hover <?= $stats['messages'] > 0 ? 'bg-warning bg-opacity-10' : '' ?>">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-shape <?= $stats['messages'] > 0 ? 'bg-warning text-white' : 'bg-warning bg-opacity-10 text-warning' ?> rounded-3 p-3 me-3">
                                    <i class="fa fa-envelope fa-lg"></i>
                                </div>
                                <div class="fw-bold text-muted small text-uppercase">Unread Messages</div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <h2 class="fw-bold mb-0 text-dark"><?= $stats['messages'] ?></h2>
                                    <?php if ($stats['messages'] > 0): ?>
                                        <span class="badge bg-danger rounded-pill pulse-badge">New</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small fw-bold <?= $stats['message_change'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <i class="fa <?= $stats['message_change'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?> me-1"></i>
                                    <?= abs($stats['message_change']) ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="comments" class="text-decoration-none">
                    <div class="card border-0 shadow-sm rounded-4 h-100 stats-card-hover <?= $stats['comments'] > 0 ? 'bg-info bg-opacity-10' : '' ?>">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-shape <?= $stats['comments'] > 0 ? 'bg-info text-white' : 'bg-info bg-opacity-10 text-info' ?> rounded-3 p-3 me-3">
                                    <i class="fa fa-comments fa-lg"></i>
                                </div>
                                <div class="fw-bold text-muted small text-uppercase">New Comments</div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <h2 class="fw-bold mb-0 text-dark"><?= $stats['comments'] ?></h2>
                                    <?php if ($stats['comments'] > 0): ?>
                                        <span class="badge bg-danger rounded-pill pulse-badge">Action</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small fw-bold <?= $stats['comment_change'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <i class="fa <?= $stats['comment_change'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?> me-1"></i>
                                    <?= abs($stats['comment_change']) ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-shape bg-success bg-opacity-10 text-success rounded-3 p-3 me-3">
                                <i class="fa fa-pen-nib fa-lg"></i>
                            </div>
                            <div class="fw-bold text-muted small text-uppercase">Blog Posts</div>
                        </div>
                        <h2 class="fw-bold mb-0"><?= $stats['posts'] ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Quick Navigation Hub -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-transparent border-0 p-4">
                        <h5 class="fw-bold mb-0">Quick Navigation Hub</h5>
                        <p class="text-muted smaller mb-0">Jump directly to any management module</p>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="row g-3">
                            <div class="col-md-4 col-sm-6">
                                <a href="settings_basic" class="nav-hub-item">
                                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                                        <i class="fa fa-info-circle"></i>
                                    </div>
                                    <span>Basic Info</span>
                                </a>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <a href="settings_homepage" class="nav-hub-item">
                                    <div class="icon-box bg-info bg-opacity-10 text-info">
                                        <i class="fa fa-home"></i>
                                    </div>
                                    <span>Homepage</span>
                                </a>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <a href="media" class="nav-hub-item">
                                    <div class="icon-box bg-success bg-opacity-10 text-success">
                                        <i class="fa fa-image"></i>
                                    </div>
                                    <span>Media Library</span>
                                </a>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <a href="about_settings" class="nav-hub-item">
                                    <div class="icon-box bg-warning bg-opacity-10 text-warning">
                                        <i class="fa fa-user-edit"></i>
                                    </div>
                                    <span>About Page</span>
                                </a>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <a href="posts" class="nav-hub-item">
                                    <div class="icon-box bg-danger bg-opacity-10 text-danger">
                                        <i class="fa fa-th-large"></i>
                                    </div>
                                    <span>Posts & Work</span>
                                </a>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <a href="services" class="nav-hub-item">
                                    <div class="icon-box bg-secondary bg-opacity-10 text-secondary">
                                        <i class="fa fa-concierge-bell"></i>
                                    </div>
                                    <span>Services</span>
                                </a>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <a href="messages" class="nav-hub-item">
                                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                                        <i class="fa fa-envelope"></i>
                                    </div>
                                    <span>Messages</span>
                                </a>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <a href="settings" class="nav-hub-item">
                                    <div class="icon-box bg-dark bg-opacity-10 text-dark">
                                        <i class="fa fa-cog"></i>
                                    </div>
                                    <span>Site Settings</span>
                                </a>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <a href="backup" class="nav-hub-item">
                                    <div class="icon-box bg-danger bg-opacity-10 text-danger">
                                        <i class="fa fa-database"></i>
                                    </div>
                                    <span>Backup Data</span>
                                </a>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <a href="vault" class="nav-hub-item">
                                    <div class="icon-box bg-success bg-opacity-10 text-success">
                                        <i class="fa fa-folder-open"></i>
                                    </div>
                                    <span>File Vault</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unified System Information & Health -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">System Information</h5>
                        
                        <!-- System Stats -->
                        <div class="system-stat-row">
                            <span class="text-muted"><i class="fa fa-code-branch me-2"></i>PHP Version</span>
                            <span class="fw-bold"><?= phpversion() ?></span>
                        </div>
                        <div class="system-stat-row">
                            <span class="text-muted"><i class="fa fa-database me-2"></i>Database</span>
                            <span class="fw-bold text-success">Connected</span>
                        </div>
                        <div class="system-stat-row">
                            <span class="text-muted"><i class="fa fa-clock me-2"></i>Timezone</span>
                            <span class="fw-bold text-primary"><?= date_default_timezone_get() ?></span>
                        </div>

                        <!-- Health Stats -->
                        <?php
                        $is_mail_configured = !empty($settings['smtp_host']) && !empty($settings['smtp_user']);
                        $mail_status = $is_mail_configured ? 'Active' : 'Not Connected';
                        
                        $start_time = microtime(true);
                        $conn->query("SELECT 1"); 
                        $end_time = microtime(true);
                        $speed = round(($end_time - $start_time) * 1000, 2);
                        
                        $today_traffic = $conn->query("SELECT COUNT(*) FROM visitor_logs WHERE visited_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_row()[0] ?? 0;
                        ?>
                        <div class="system-stat-row">
                            <span class="text-muted"><i class="fa fa-server me-2"></i>Mail Server</span>
                            <span class="badge <?= $mail_status == 'Active' ? 'bg-success' : 'bg-secondary opacity-50' ?> rounded-pill"><?= $mail_status ?></span>
                        </div>
                        <div class="system-stat-row">
                            <span class="text-muted"><i class="fa fa-bolt me-2"></i>Server Speed</span>
                            <span class="fw-bold text-success"><?= $speed ?> ms</span>
                        </div>
                        <div class="system-stat-row">
                            <span class="text-muted"><i class="fa fa-chart-line me-2"></i>Today's Traffic</span>
                            <span class="fw-bold"><?= $today_traffic ?> Hits</span>
                        </div>
                    </div>
                </div>
                
                <!-- Cache Management -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Cache Management</h5>
                        <p class="small text-muted mb-3">
                            <i class="fa fa-info-circle me-1"></i>Force all browsers to reload fresh assets instantly.
                        </p>
                        <button id="clear-cache-btn" onclick="clearGlobalCache()" 
                            class="btn btn-sm w-100 rounded-pill fw-bold py-2 shadow-sm"
                            style="background: linear-gradient(135deg, #667eea, #764ba2); color:#fff; border:none;">
                            <i class="fa fa-sync-alt me-2" id="cache-icon"></i>
                            <span id="cache-btn-text">Clear Global Cache</span>
                        </button>
                        <p id="cache-status" class="small text-center mt-3 mb-0" style="display:none;"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
    .pulse-badge {
        animation: pulse-red 2s infinite;
        font-size: 0.65rem;
        padding: 0.4rem 0.8rem;
        letter-spacing: 0.5px;
    }

    @keyframes pulse-red {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        }

        70% {
            transform: scale(1);
            box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
        }

        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }

    .smaller {
        font-size: 0.75rem;
    }

    .icon-shape {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .stats-card-hover {
        transition: all 0.3s ease;
    }

    .stats-card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }

    .nav-hub-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 16px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s ease;
        text-align: center;
        height: 100%;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .nav-hub-item:hover {
        background: #fff;
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        border-color: var(--primary-theme);
    }

    .nav-hub-item .icon-box {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .nav-hub-item:hover .icon-box {
        transform: scale(1.1);
    }

    .nav-hub-item span {
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    .system-stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 12px;
        margin-bottom: 12px;
        border-bottom: 1px solid #f1f1f1;
        font-size: 0.85rem;
    }

    .system-stat-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
</style>
<script>
function clearGlobalCache() {
    const btn = document.getElementById('clear-cache-btn');
    const icon = document.getElementById('cache-icon');
    const text = document.getElementById('cache-btn-text');
    const status = document.getElementById('cache-status');
    btn.disabled = true;
    icon.classList.add('fa-spin');
    text.textContent = 'Clearing...';
    status.style.display = 'none';
    fetch('clear_cache', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(data => {
        icon.classList.remove('fa-spin');
        if (data.success) {
            btn.style.background = 'linear-gradient(135deg,#00b09b,#96c93d)';
            icon.className = 'fa fa-check-circle me-2';
            text.textContent = 'Cache Cleared!';
            status.style.display = 'block';
            status.style.color = '#00b09b';
            status.innerHTML = '<i class="fa fa-bolt me-1"></i>All browsers will reload fresh on next visit';
            setTimeout(() => {
                btn.disabled = false;
                btn.style.background = 'linear-gradient(135deg,#667eea,#764ba2)';
                icon.className = 'fa fa-sync-alt me-2';
                text.textContent = 'Clear Global Cache';
                status.style.display = 'none';
            }, 4000);
        } else {
            btn.disabled = false;
            btn.style.background = 'linear-gradient(135deg,#ff416c,#ff4b2b)';
            icon.className = 'fa fa-exclamation-triangle me-2';
            text.textContent = 'Failed - Try Again';
            status.style.display = 'block';
            status.style.color = '#ff416c';
            status.textContent = data.message;
        }
    })
    .catch(() => {
        btn.disabled = false;
        icon.classList.remove('fa-spin');
        text.textContent = 'Network Error';
    });
}
</script>
