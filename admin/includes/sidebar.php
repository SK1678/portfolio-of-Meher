<div class="sidebar">
    <div class="brand-section d-flex align-items-center">
        <div class="brand-logo"><i class="fa fa-palette"></i></div>
        <div class="brand-name">
            <h5>Portfolio CMS</h5>
            <span>Content Manager</span>
        </div>
    </div>

    <div class="nav-label">Navigation</div>
    <a href="index" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>"><i
            class="fa fa-tachometer-alt"></i> Overview</a>
    <a href="settings_basic"
        class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings_basic.php' ? 'active' : '' ?>"><i
            class="fa fa-info-circle"></i> Basic Info Setup</a>
    <a href="settings_homepage" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings_homepage.php' ? 'active' : '' ?>"><i
            class="fa fa-home"></i> Homepage Settings</a>
    <a href="media" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'media.php' ? 'active' : '' ?>"><i
            class="fa fa-image"></i> Media Library</a>
    <a href="about_settings"
        class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'about_settings.php' ? 'active' : '' ?>"><i
            class="fa fa-user-edit"></i> About Page Settings</a>
    <a href="posts" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'posts.php' ? 'active' : '' ?>"><i
            class="fa fa-th-large"></i> Posts & Portfolio</a>
    <a href="services" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : '' ?>"><i class="fa fa-concierge-bell"></i> Services</a>
    <a href="messages" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : '' ?>"><i class="fa fa-envelope"></i> Messages</a>
    <a href="settings" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>"><i
            class="fa fa-cog"></i> Site Settings</a>

    <div class="nav-label mt-4">Actions</div>
    <a href="../index" target="_blank" class="nav-link"><i class="fa fa-external-link-alt"></i> View Portfolio</a>
    <a href="backup" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : '' ?>"><i class="fa fa-database"></i> Backup Data</a>

    <div class="profile-footer">
        <div class="user-info">
            <div class="user-avatar"><?= substr($_SESSION['full_name'] ?? 'A', 0, 1) ?></div>
            <div class="user-details">
                <h6><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></h6>
                <span>Administrator</span>
            </div>
        </div>
        <a href="../logout.php" class="logout-btn"><i class="fa fa-sign-out-alt"></i></a>
    </div>
</div>