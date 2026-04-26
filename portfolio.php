<?php
$page_title = "Portfolio";
require_once 'db.php';
include 'includes/public_head.php';

// Get Portfolio parent category ID
$port_cat = $conn->query("SELECT id FROM categories WHERE slug='portfolio' LIMIT 1")->fetch_assoc();
$portfolio_parent_id = $port_cat ? $port_cat['id'] : 0;

// Get all portfolio child categories (filters)
$sub_cats = [];
if ($portfolio_parent_id) {
    $res = $conn->query("SELECT * FROM categories WHERE parent_id=$portfolio_parent_id ORDER BY name ASC");
    while ($r = $res->fetch_assoc()) $sub_cats[] = $r;
}

// Collect all portfolio posts: posts assigned to "Portfolio" parent OR any child
$cat_ids = [$portfolio_parent_id];
foreach ($sub_cats as $sc) $cat_ids[] = $sc['id'];
$cat_ids_str = implode(',', $cat_ids);

$posts = [];
if (!empty($cat_ids_str)) {
    $res = $conn->query("
        SELECT DISTINCT p.*, 
               GROUP_CONCAT(DISTINCT c.slug SEPARATOR ' ') AS cat_slugs,
               GROUP_CONCAT(CONCAT(pa.label, ':::', pa.url) SEPARATOR '|||') as attachments_raw
        FROM posts p
        JOIN post_categories pc ON pc.post_id = p.id
        JOIN categories c ON c.id = pc.category_id
        LEFT JOIN post_attachments pa ON pa.post_id = p.id
        WHERE pc.category_id IN ($cat_ids_str) AND p.status='published'
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    while ($r = $res->fetch_assoc()) $posts[] = $r;
}
?>

<div class="portfolio-page-outer">
    <?php include 'includes/public_nav.php'; ?>

    <div class="portfolio-page-container">
        <!-- Header -->
        <header class="portfolio-page-header">
            <h1 class="port-main-title text-uppercase">MY <span class="accent-text">PORTFOLIO</span></h1>
            <div class="subtitle-wrapper">
                <div class="decor-line"></div>
                <p class="subtitle-text text-uppercase">Check out my latest projects</p>
                <div class="decor-line"></div>
            </div>
        </header>

        <?php if (!empty($posts)): ?>

        <!-- Filter Tabs -->
        <?php if (!empty($sub_cats)): ?>
        <div class="filter-bar">
            <button class="filter-btn active" data-filter="all">All</button>
            <?php foreach ($sub_cats as $sc): ?>
                <button class="filter-btn" data-filter="<?= htmlspecialchars($sc['slug']) ?>">
                    <?= htmlspecialchars($sc['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Portfolio Grid -->
        <div class="portfolio-grid" id="portfolio-grid">
            <?php foreach ($posts as $post): 
                $f_imgs = !empty($post['feature_image']) ? explode(',', $post['feature_image']) : [];
                
                // Parse raw attachments string
                $atts = [];
                if (!empty($post['attachments_raw'])) {
                    $raw_list = explode('|||', $post['attachments_raw']);
                    foreach ($raw_list as $item) {
                        $parts = explode(':::', $item);
                        if (count($parts) === 2) {
                            $atts[] = ['label' => $parts[0], 'url' => $parts[1]];
                        }
                    }
                }
            ?>
            <div class="port-item visible" data-cats="<?= htmlspecialchars($post['cat_slugs']) ?>">
                <div class="port-img-wrap">
                    <?php if (count($f_imgs) > 1): ?>
                        <div class="swiper mini-swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($f_imgs as $img): ?>
                                    <div class="swiper-slide">
                                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php elseif (!empty($f_imgs)): ?>
                        <img src="<?= htmlspecialchars($f_imgs[0]) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                    <?php else: ?>
                        <div class="port-img-placeholder"><i class="fa fa-image"></i></div>
                    <?php endif; ?>

                    <div class="port-overlay">
                        <div class="port-overlay-content">
                            <h3 class="port-item-title"><?= htmlspecialchars($post['title']) ?></h3>
                            <div class="port-item-links">
                                <a href="post/<?= urlencode($post['slug']) ?>" class="port-link-icon" title="View Details"><i class="fa fa-eye"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mini swipers
    const miniSwipers = new Swiper('.mini-swiper', {
        loop: true,
        effect: 'fade',
        fadeEffect: { crossFade: true },
        autoplay: { delay: 2000, disableOnInteraction: false },
        speed: 800,
    });

    const filterBtns = document.querySelectorAll('.filter-btn');
    const items = document.querySelectorAll('.port-item');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');

            items.forEach(item => {
                if (filter === 'all') {
                    item.classList.remove('hidden');
                    setTimeout(() => item.classList.add('visible'), 10);
                } else {
                    const cats = (item.getAttribute('data-cats') || '').split(' ');
                    if (cats.includes(filter)) {
                        item.classList.remove('hidden');
                        setTimeout(() => item.classList.add('visible'), 10);
                    } else {
                        item.classList.remove('visible');
                        setTimeout(() => item.classList.add('hidden'), 300);
                    }
                }
            });
        });
    });
});
</script>

<style>
html, body {
    overflow-y: auto !important;
    height: auto !important;
    min-height: 100vh !important;
}

.portfolio-page-outer {
    background: #111;
    color: #fff;
    min-height: 100vh;
    font-family: 'Poppins', sans-serif;
    padding-bottom: 100px;
}

.portfolio-page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 80px 20px 0;
}

/* Header */
.portfolio-page-header {
    text-align: center;
    margin-bottom: 90px;
    padding-top: 40px;
}
.port-main-title {
    font-size: 4.5rem;
    font-weight: 900;
    letter-spacing: 2px;
    margin-bottom: 20px;
    line-height: 1;
    color: #fff;
}
.accent-text {
    color: var(--primary-theme);
}
.subtitle-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 25px;
}
.decor-line {
    width: 45px;
    height: 1px;
    background: var(--primary-theme);
    opacity: 0.8;
}
.subtitle-text {
    font-size: 0.85rem;
    letter-spacing: 3px;
    font-weight: 600;
    margin: 0;
    color: #fff;
    opacity: 0.9;
}

/* Filter Bar */
.filter-bar {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 50px;
}
.filter-btn {
    background: transparent;
    border: none;
    color: #aaa;
    font-size: 0.9rem;
    font-weight: 600;
    padding: 6px 18px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
}
.filter-btn:hover { color: var(--primary-theme); }
.filter-btn.active {
    color: var(--primary-theme);
    border-bottom-color: var(--primary-theme);
}

/* Portfolio Grid */
.portfolio-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* Smaller boxes: 4 columns */
    gap: 15px; /* More gap for breathing room */
}

.port-item {
    display: block;
    overflow: hidden;
    background: #1a1a1a;
    border-radius: 8px;
    transition: opacity 0.3s ease, transform 0.4s ease;
}
.port-item.hidden { opacity: 0; pointer-events: none; transform: scale(0.95); display: none; }
.port-item.visible { opacity: 1; pointer-events: all; transform: scale(1); }

.port-img-wrap {
    position: relative;
    width: 100%;
    aspect-ratio: 4 / 3; /* Fixed professional aspect ratio */
    overflow: hidden;
    background: #000;
}
.port-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain; /* Full image no crop */
    display: block;
    transition: transform 0.5s ease;
}
.port-item:hover .port-img-wrap img { transform: scale(1.05); }

/* Swiper inside grid */
.mini-swiper { width: 100%; height: 100%; }

.port-img-placeholder {
    width: 100%; height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #444;
    font-size: 2rem;
}

/* Overlay */
.port-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.85); /* Darker for better link visibility */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 10;
}
.port-item:hover .port-overlay { opacity: 1; }
.port-overlay-content { text-align: center; transform: translateY(15px); transition: transform 0.3s ease; }
.port-item:hover .port-overlay-content { transform: translateY(0); }

.port-item-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.port-item-links {
    display: flex;
    justify-content: center;
    gap: 12px;
}
.port-link-icon {
    width: 38px;
    height: 38px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}
.port-link-icon:hover {
    background: var(--primary-theme);
    border-color: var(--primary-theme);
    color: #000;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.port-empty {
    text-align: center;
    padding: 80px 0;
    color: #555;
    font-size: 1rem;
}

@media (max-width: 768px) {
    .portfolio-grid { grid-template-columns: repeat(2, 1fr); }
    .port-main-title { font-size: 2.5rem; }
}
@media (max-width: 480px) {
    .portfolio-grid { grid-template-columns: 1fr; }
}
</style>

<?php include 'includes/public_footer.php'; ?>
