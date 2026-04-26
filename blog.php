<?php
$page_title = "My Blog";
require_once 'db.php';
include 'includes/public_head.php';

// Get Blog parent category ID
$blog_cat = $conn->query("SELECT id FROM categories WHERE slug='blog' LIMIT 1")->fetch_assoc();
$blog_parent_id = $blog_cat ? $blog_cat['id'] : 0;

$posts = [];
$res = $conn->query("
    SELECT p.*, 
           GROUP_CONCAT(c.name SEPARATOR ',') AS cat_names
    FROM posts p
    LEFT JOIN post_categories pc ON pc.post_id = p.id
    LEFT JOIN categories c ON c.id = pc.category_id
    WHERE p.status='published'
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
if ($res) while ($r = $res->fetch_assoc()) $posts[] = $r;
?>

<div class="blog-page-outer">
    <?php include 'includes/public_nav.php'; ?>

    <div class="blog-page-container">
        <!-- Header -->
        <header class="blog-header">
            <h1 class="blog-title text-uppercase">MY <span class="accent-text">BLOG</span></h1>
            <div class="subtitle-wrapper">
                <div class="decor-line"></div>
                <p class="subtitle-text text-uppercase">Check out my latest blog posts</p>
                <div class="decor-line"></div>
            </div>
        </header>

        <?php if (!empty($posts)): ?>

        <!-- Blog Grid -->
        <div class="blog-grid">
            <?php foreach ($posts as $post): ?>
            <article class="blog-card">
                <a href="post/<?= urlencode($post['slug']) ?>" class="blog-card-link">
                    <div class="blog-img-wrap">
                        <?php 
                        $f_imgs = !empty($post['feature_image']) ? explode(',', $post['feature_image']) : [];
                        if (!empty($f_imgs)): ?>
                            <img src="<?= htmlspecialchars($f_imgs[0]) ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="blog-img-placeholder"><i class="fa fa-image"></i></div>
                        <?php endif; ?>
                        
                        <!-- Date Badge -->
                        <div class="blog-date-badge">
                            <?= date('d M, y', strtotime($post['created_at'])) ?>
                        </div>
                    </div>
                    
                    <div class="blog-info">
                        <div class="blog-meta-top">
                            <span class="blog-views"><i class="fa fa-eye"></i> <?= rand(100, 5000) ?></span>
                            <?php if (!empty($post['cat_names'])): 
                                $cats = explode(',', $post['cat_names']);
                                foreach ($cats as $cat): ?>
                                    <span class="blog-tag"><?= htmlspecialchars($cat) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <h3 class="blog-card-title"><?= htmlspecialchars($post['title']) ?></h3>
                        <?php if (!empty($post['excerpt'])): ?>
                            <p class="blog-card-excerpt">
                                <?= htmlspecialchars(mb_strimwidth(strip_tags($post['excerpt']), 0, 110, '...')) ?>
                            </p>
                        <?php else: ?>
                            <p class="blog-card-excerpt">
                                <?= htmlspecialchars(mb_strimwidth(strip_tags($post['content']), 0, 110, '...')) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.blog-page-outer {
    background: #111;
    color: #fff;
    min-height: 100vh;
    padding-bottom: 100px;
    font-family: 'Poppins', sans-serif;
}
.blog-page-container {
    max-width: 1140px;
    margin: 0 auto;
    padding: 100px 20px 0;
}

/* Header */
.blog-header {
    text-align: center;
    margin-bottom: 90px;
}
.blog-title {
    font-size: 4.5rem;
    font-weight: 900;
    letter-spacing: 2px;
    margin-bottom: 20px;
    line-height: 1;
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

/* Grid */
.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 30px;
}

/* Card */
.blog-card {
    background: transparent;
    transition: transform 0.3s ease;
}
.blog-card:hover {
    transform: translateY(-5px);
}
.blog-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.blog-img-wrap {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 10;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 18px;
    background: #1a1a1a;
}
.blog-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
.blog-card:hover .blog-img-wrap img {
    transform: scale(1.05);
}

.blog-date-badge {
    position: absolute;
    bottom: 0;
    left: 0;
    background: var(--primary-theme);
    color: #fff;
    padding: 6px 14px;
    font-size: 0.75rem;
    font-weight: 700;
}

.blog-info {
    padding: 0 5px;
}
.blog-meta-top {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.blog-views {
    font-size: 0.75rem;
    color: #666;
    font-weight: 600;
}
.blog-tag {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--primary-theme);
    background: rgba(var(--primary-theme-rgb), 0.1);
    padding: 2px 8px;
    border-radius: 4px;
    letter-spacing: 0.5px;
}
.blog-card-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 10px;
    line-height: 1.4;
    transition: color 0.3s;
}
.blog-card:hover .blog-card-title {
    color: var(--primary-theme);
}
.blog-card-excerpt {
    color: #999;
    font-size: 0.92rem;
    line-height: 1.6;
    margin-bottom: 0;
}

.blog-img-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #333;
    font-size: 2.5rem;
}

.blog-empty {
    text-align: center;
    padding: 100px 0;
    color: #555;
}

@media (max-width: 768px) {
    .blog-title { font-size: 2.2rem; }
    .blog-grid { grid-template-columns: 1fr; }
}
</style>

<?php include 'includes/public_footer.php'; ?>
