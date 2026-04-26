<!-- Side Navigation Menu -->
<nav id="side-nav" class="side-nav">
    <div class="nav-pill-bg">
        <button class="close-nav" id="close-nav"><i class="fa fa-times"></i></button>
        <ul class="nav-items">
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <a href="index"><i class="fa fa-home"></i> <span>HOME</span></a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>">
                <a href="about"><i class="fa fa-user"></i> <span>ABOUT</span></a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'portfolio.php' ? 'active' : '' ?>">
                <a href="portfolio"><i class="fa fa-briefcase"></i> <span>PORTFOLIO</span></a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : '' ?>">
                <a href="services"><i class="fa fa-concierge-bell"></i> <span>SERVICES</span></a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>">
                <a href="contact"><i class="fa fa-comment"></i> <span>CONTACT</span></a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) == 'blog.php' ? 'active' : '' ?>">
                <a href="blog"><i class="fa fa-envelope"></i> <span>BLOG</span></a>
            </li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li>
                    <a href="admin/index"><i class="fa fa-tachometer-alt"></i> <span>DASHBOARD</span></a>
                </li>
            <?php else: ?>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : '' ?>">
                    <a href="login"><i class="fa fa-sign-in-alt"></i> <span>LOGIN</span></a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- Mobile Menu Toggle -->
<div class="menu-toggle">
    <i class="fa-solid fa-bars-staggered"></i>
</div>
