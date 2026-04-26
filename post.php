<?php
include 'db.php';

// Helper for environments without mysqlnd
function fetchRows($stmt) {
    $res = $stmt->get_result();
    if ($res) return $res;
    
    $stmt->store_result();
    $meta = $stmt->result_metadata();
    $fields = []; $row = [];
    while ($field = $meta->fetch_field()) {
        $fields[] = &$row[$field->name];
    }
    call_user_func_array([$stmt, 'bind_result'], $fields);
    return ['row' => $row, 'stmt' => $stmt, 'is_fallback' => true];
}

function getNextRow(&$fetchRes) {
    if (isset($fetchRes->num_rows)) return $fetchRes->fetch_assoc();
    
    if ($fetchRes['stmt']->fetch()) {
        $r = [];
        foreach ($fetchRes['row'] as $k => $v) {
            $r[$k] = $v; // Copy value out of the referenced array
        }
        return $r;
    }
    return null;
}

// Get post by slug
$slug = $_GET['slug'] ?? '';
$post = null;
if ($slug) {
    $stmt = $conn->prepare("SELECT * FROM posts WHERE slug=? AND status='published' LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $fetchRes = fetchRows($stmt);
    $post = getNextRow($fetchRes);
}

// Site settings are already fetched in db.php via include 'db.php'


if ($post) {
    // Increment Views
    $post_id = (int)$post['id'];
    $conn->query("UPDATE posts SET views = views + 1 WHERE id = $post_id");
    $post['views']++; // Update current array to reflect the new view count
}

if (!$post) {
    header("HTTP/1.0 404 Not Found");
    $page_title = "Post Not Found";
    include 'includes/public_head.php';
    echo '<div style="text-align:center;padding:120px 20px;color:#fff;background:#111;min-height:100vh;"><h1 style="font-size:3rem;">404</h1><p style="color:#888;">This post was not found.</p><a href="portfolio.php" style="color:var(--primary-theme);">← Back to Portfolio</a></div>';
    include 'includes/public_footer.php';
    exit;
}

$page_title = htmlspecialchars($post['title']);
$post_id = (int)$post['id'];

// Get categories for this post
$cats_stmt = $conn->prepare("SELECT c.name, c.slug FROM categories c JOIN post_categories pc ON pc.category_id=c.id WHERE pc.post_id=?");
$cats_stmt->bind_param("i", $post_id);
$cats_stmt->execute();
$cats_res = fetchRows($cats_stmt);
$post_cats = [];
while ($c = getNextRow($cats_res)) $post_cats[] = $c;

// Handle comment submission
$comment_success = false;
$comment_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $c_name  = trim($_POST['author_name'] ?? '');
    $c_email = trim($_POST['author_email'] ?? '');
    $c_body  = trim($_POST['content'] ?? '');
    $c_parent = (int)($_POST['parent_id'] ?? 0);
    $parent_val = $c_parent > 0 ? $c_parent : null;

    if ($c_name && $c_email && $c_body) {
        $stmt = $conn->prepare("INSERT INTO post_comments (post_id, parent_id, name, email, comment, status) VALUES (?, ?, ?, ?, ?, 'approved')");
        // Handle null parent_id correctly
        $final_parent = ($parent_val > 0) ? $parent_val : null;
        $stmt->bind_param("iisss", $post_id, $final_parent, $c_name, $c_email, $c_body);
        
        if ($stmt->execute()) {
            $comment_success = true;
            // Redirect to clean URL
            header("Location: post/" . urlencode($slug) . "#comments");
            exit;
        } else {
            $comment_error = 'Database error: ' . $conn->error;
        }
    } else {
        $comment_error = 'Please fill in all fields.';
    }
}

// Get approved comments
$all_com_stmt = $conn->prepare("SELECT * FROM post_comments WHERE post_id=? AND status='approved' ORDER BY created_at ASC");
$all_com_stmt->bind_param("i", $post_id);
$all_com_stmt->execute();
$all_comments_res = fetchRows($all_com_stmt);
$all_comments = [];
while ($row = getNextRow($all_comments_res)) $all_comments[] = $row;

// Build a threaded tree
function buildCommentTree(array $elements, $parentId = null) {
    $branch = [];
    foreach ($elements as $element) {
        $e_parent = $element['parent_id'];
        if ($e_parent == $parentId || ($parentId === null && $e_parent === null)) {
            $children = buildCommentTree($elements, $element['id']);
            $element['replies'] = $children ? $children : [];
            $branch[] = $element;
        }
    }
    return $branch;
}
$comments = buildCommentTree($all_comments);

// Recursive render function
function renderComments($comments, $level = 0) {
    foreach ($comments as $comment): ?>
        <div class="comment-item <?= $level > 0 ? 'comment-reply' : '' ?>" id="comment-<?= $comment['id'] ?>">
            <div class="comment-avatar">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($comment['name']) ?>&background=333&color=fff&size=40" alt="<?= htmlspecialchars($comment['name']) ?>">
            </div>
            <div class="comment-body">
                <div class="comment-header">
                    <span class="comment-author"><?= htmlspecialchars(strtoupper($comment['name'])) ?></span>
                    <span class="comment-date"><?= date('M d, Y', strtotime($comment['created_at'])) ?></span>
                </div>
                <p class="comment-text"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                <button class="reply-link-btn" onclick="showReplyForm(<?= $comment['id'] ?>, '<?= htmlspecialchars($comment['name'], ENT_QUOTES) ?>')">REPLY</button>
            </div>
        </div>
        <?php if (!empty($comment['replies'])): ?>
            <div class="nested-comments">
                <?php renderComments($comment['replies'], $level + 1); ?>
            </div>
        <?php endif; ?>
    <?php endforeach;
}

// Get post attachments
$attachments = [];
$att_res = $conn->query("SELECT * FROM post_attachments WHERE post_id=$post_id ORDER BY id ASC");
if ($att_res) while ($r = $att_res->fetch_assoc()) $attachments[] = $r;

// Get recent posts for sidebar
$recent_posts = [];
$rp_res = $conn->query("SELECT title, slug FROM posts WHERE status='published' AND id != $post_id ORDER BY created_at DESC LIMIT 5");
if ($rp_res) while($rp = $rp_res->fetch_assoc()) $recent_posts[] = $rp;

include 'includes/public_head.php';

?>

<div class="post-page-outer">
    <?php include 'includes/public_nav.php'; ?>

    <!-- New Hero -->
    <div class="new-post-hero">
        <div class="hero-overlay"></div> <!-- Added overlay for readability -->
        <div class="hero-inner text-center">
            <h1 class="new-post-title"><?= htmlspecialchars($post['title']) ?></h1>
            <div class="new-post-meta">
                <span><i class="fa fa-user"></i> Admin</span>
                <span><i class="fa fa-calendar-alt"></i> <?= date('d M Y', strtotime($post['created_at'])) ?></span>
                <span><i class="fa fa-folder-open"></i> <?= !empty($post_cats) ? htmlspecialchars(implode(', ', array_column($post_cats, 'name'))) : 'Uncategorized' ?></span>
                <span><i class="fa fa-eye"></i> <?= number_format($post['views']) ?></span>
            </div>
        </div>
    </div>
    <div class="post-hero-accent-line"></div>

    <!-- Layout Container -->
    <div class="post-layout-container">
        
        <!-- Left Sidebar -->
        <aside class="post-sidebar">
            <?php 
            $f_images = !empty($post['feature_image']) ? explode(',', $post['feature_image']) : [];
            if (!empty($f_images)): ?>
            <div class="sidebar-card no-padding overflow-hidden">
                <?php if (count($f_images) > 1): ?>
                    <!-- Swiper Slider -->
                    <div class="swiper feature-swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($f_images as $img): ?>
                                <div class="swiper-slide">
                                    <img src="<?= htmlspecialchars($img) ?>" alt="Feature Image" class="sidebar-feature-img">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                <?php else: ?>
                    <img src="<?= htmlspecialchars($f_images[0]) ?>" alt="Feature Image" class="sidebar-feature-img">
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Attachments & Links (Moved to Sidebar) -->
            <?php if (!empty($attachments)): ?>
            <div class="sidebar-card">
                <h4 class="sidebar-heading">Project Links &amp; Files</h4>
                <div class="sidebar-att-list">
                    <?php foreach ($attachments as $att):
                        $locked = (bool)$att['is_protected'];
                    ?>
                    <a href="<?= $locked ? 'javascript:void(0)' : htmlspecialchars($att['url']) ?>"
                       <?php if ($locked): ?>
                           onclick="checkAccess('<?= htmlspecialchars($att['url'], ENT_QUOTES) ?>', '<?= htmlspecialchars($att['password'] ?? '', ENT_QUOTES) ?>')"
                       <?php else: ?>
                           target="_blank"
                       <?php endif; ?>
                       class="sidebar-att-link <?= $locked ? 'sidebar-att-locked' : '' ?>">
                        <i class="fa <?= $locked ? 'fa-lock' : 'fa-external-link-alt' ?> me-2"></i>
                        <?= htmlspecialchars($att['label']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="sidebar-card p-4">
                <h4 class="sidebar-heading">Recent Posts</h4>
                <ul class="recent-posts-list">
                    <?php if(empty($recent_posts)): ?>
                        <li class="text-muted">No recent posts.</li>
                    <?php else: foreach($recent_posts as $rp): ?>
                        <li><a href="post.php?slug=<?= urlencode($rp['slug']) ?>"><?= htmlspecialchars($rp['title']) ?></a></li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </aside>

        <!-- Right Main Content -->
        <main class="post-main">
            <!-- Content Area Card -->
            <article class="main-card p-4 p-md-5 mb-4">
            <div class="post-body">
                <?= str_replace('src="../assets/uploads/', 'src="assets/uploads/', $post['content']) ?>
            </div>
            </article>


        <!-- Comments Section Card -->
        <section class="main-card p-4 p-md-5 comments-section" id="comments">
            <h2 class="comments-heading">Comments (<?= count($all_comments) ?>)</h2>
            <?php if (empty($comments)): ?>
                <p class="no-comments">No comments yet. Be the first!</p>
            <?php else: ?>
                <?php renderComments($comments); ?>
            <?php endif; ?>

            <!-- Add Comment Form -->
            <div class="comment-form-wrap">
                <h3 class="add-comment-heading">ADD A COMMENT</h3>
                <?php if ($comment_error): ?>
                    <p style="color:#ff4444; margin-bottom:16px;"><?= $comment_error ?></p>
                <?php endif; ?>
                <form method="POST" action="post.php?slug=<?= urlencode($slug) ?>#comments">
                    <input type="hidden" name="parent_id" id="reply-parent-id" value="0">
                    <div class="form-group-post">
                        <i class="fa fa-user"></i>
                        <input type="text" name="author_name" placeholder="YOUR NAME" required>
                    </div>
                    <div class="form-group-post">
                        <i class="fa fa-envelope"></i>
                        <input type="email" name="author_email" placeholder="YOUR EMAIL" required>
                    </div>
                    <div class="form-group-post form-group-textarea">
                        <i class="fa fa-comment"></i>
                        <textarea name="content" placeholder="YOUR COMMENT" rows="5" required></textarea>
                    </div>
                    <div id="reply-notice" style="display:none; color: var(--primary-theme); font-size:0.8rem; margin-bottom:12px;">
                        Replying to a comment. <a href="#" onclick="cancelReply(event)" style="color:#aaa;">Cancel</a>
                    </div>
                    <button type="submit" name="submit_comment" class="submit-comment-btn">
                        <i class="fa fa-comment me-2"></i> ADD COMMENT
                    </button>
                </form>
            </div>
        </section>
        
        </main>
    </div>
</div>

<script>
function showReplyForm(id, name) {
    document.getElementById('reply-parent-id').value = id;
    const notice = document.getElementById('reply-notice');
    notice.style.display = 'block';
    notice.innerHTML = `Replying to <b style="color:var(--primary-theme)">${name}</b>. <a href="#" onclick="cancelReply(event)" style="color:#aaa; text-decoration:none; margin-left:10px;">[ Cancel ]</a>`;
    document.querySelector('.comment-form-wrap').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function cancelReply(e) {
    if(e) e.preventDefault();
    document.getElementById('reply-parent-id').value = 0;
    document.getElementById('reply-notice').style.display = 'none';
}
</script>

<style>
html, body { overflow-y: auto !important; height: auto !important; min-height: 100vh !important; }

.post-page-outer {
    background: #111;
    color: #fff;
    min-height: 100vh;
    font-family: 'Poppins', sans-serif;
}

/* New Layout Styles */
.new-post-hero {
    <?php if(!empty($post['hero_image'])): ?>
    background: url('<?= htmlspecialchars($post['hero_image']) ?>') center/cover no-repeat;
    <?php else: ?>
    background: #4CAF50; /* Default Green Fallback */
    <?php endif; ?>
    padding: 60px 20px; /* More compact */
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7)); /* Smoother gradient */
}
.hero-inner { position: relative; z-index: 2; width: 100%; max-width: 1200px; }
.new-post-title {
    font-size: 2.8rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 24px;
    letter-spacing: -1px;
    text-align: center;
    line-height: 1.2;
}
.new-post-meta {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 12px;
    color: rgba(255,255,255,0.8);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.new-post-meta span {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.08);
    padding: 6px 16px;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.1);
    backdrop-filter: blur(5px);
    transition: all 0.3s ease;
}
.new-post-meta span i {
    color: var(--primary-theme);
    font-size: 0.85rem;
}
.new-post-meta span:hover {
    background: rgba(255,255,255,0.12);
    transform: translateY(-2px);
    border-color: rgba(255,255,255,0.2);
}
.post-hero-accent-line {
    height: 3px; /* Thinner, more elegant accent */
    background: var(--primary-theme);
    opacity: 0.8;
}

/* Two Column Layout (Open Style) */
.post-layout-container {
    max-width: 1200px;
    margin: 60px auto;
    padding: 0 30px;
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 60px; /* More whitespace for eye-smoothing effect */
    align-items: start;
}

/* Clean Containers (No Box Style) */
.sidebar-card, .main-card {
    background: transparent;
    border: none;
    border-radius: 0;
    margin-bottom: 50px;
    padding: 0;
    overflow: visible;
}

/* Sidebar */
.sidebar-feature-img {
    width: 100%;
    border-radius: 12px; /* Smooth rounded corners */
    object-fit: cover;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3); /* Subtle depth */
    margin-bottom: 30px;
}
.sidebar-heading {
    color: #fff;
    font-size: 0.9rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.recent-posts-list {
    list-style: none;
    padding: 0; margin: 0;
}
.recent-posts-list li {
    margin-bottom: 12px;
}
.recent-posts-list a {
    color: rgba(255,255,255,0.5);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    line-height: 1.6;
    display: block;
    padding: 8px 0;
}
.recent-posts-list a:hover {
    color: #fff;
    transform: translateX(5px);
}

/* Body text */
.post-body {
    color: rgba(255,255,255,0.8);
    font-size: 1.05rem;
    line-height: 1.9;
    margin-bottom: 50px;
}
.post-body::after {
    content: "";
    display: table;
    clear: both; /* Fix for floating images collapse */
}
.post-body p { margin-bottom: 22px; }
.post-body img { 
    max-width: 100%; 
    border-radius: 12px; 
    margin: 25px 0; 
    box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
    display: block; 
    transition: transform 0.3s ease;
}
.post-body img.img-left { 
    float: left; 
    margin: 10px 30px 25px 0; 
    max-width: 45%; /* Prevent squeezing text too much */
    display: inline;
}
.post-body img.img-right { 
    float: right; 
    margin: 10px 0 25px 30px; 
    max-width: 45%; 
    display: inline;
}
.post-body img.img-center { 
    display: block; 
    margin-left: auto; 
    margin-right: auto; 
}
.post-body h2 { color: #fff; font-size: 1.8rem; font-weight: 700; margin: 40px 0 20px; }
.post-body h3 { color: #fff; font-size: 1.4rem; font-weight: 700; margin: 30px 0 15px; }
.post-body a { color: var(--primary-theme); text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); }

/* Sidebar Attachments (Inline Tag Style) */
.sidebar-att-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.sidebar-att-link {
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 8px 16px;
    background: rgba(255,255,255,0.05);
    border-radius: 20px; /* Tag/Pill style */
    border: 1px solid rgba(255,255,255,0.1);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.sidebar-att-link:hover {
    color: #fff;
    background: var(--primary-theme);
    border-color: var(--primary-theme);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.sidebar-att-locked {
    color: #ffb400;
    border-color: rgba(255,180,0,0.2);
}

/* Comments */
.comments-section { 
    padding-top: 50px; 
    clear: both; /* Ensure comments always start on a new line below floats */
    border-top: 1px solid rgba(255,255,255,0.05);
}

.comments-heading { font-size: 1.4rem; font-weight: 800; letter-spacing: 2px; margin-bottom: 35px; color: #fff; }
.no-comments { color: #555; font-style: italic; margin-bottom: 40px; }

.comment-item {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.03);
}
.comment-reply { margin-bottom: 15px; border-bottom: none; padding-bottom: 0; }
.nested-comments { 
    margin-left: 45px; 
    border-left: 1px solid rgba(255,255,255,0.05);
    padding-left: 20px;
}
.comment-avatar img {
    width: 36px; height: 36px;
    border-radius: 50%;
    object-fit: cover;
    filter: grayscale(1);
}
.comment-body { flex: 1; }
.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}
.comment-author { font-size: 0.8rem; font-weight: 800; color: #fff; }
.comment-date { font-size: 0.7rem; color: rgba(255,255,255,0.3); }
.comment-text { font-size: 0.85rem; color: rgba(255,255,255,0.6); line-height: 1.6; margin-bottom: 5px; }
.reply-link-btn {
    background: none;
    border: none;
    color: var(--primary-theme);
    font-size: 0.65rem;
    font-weight: 900;
    letter-spacing: 1px;
    cursor: pointer;
    padding: 0;
    transition: opacity 0.2s;
}
.reply-link-btn:hover { opacity: 0.7; }

/* Comment Form */
.comment-form-wrap { margin-top: 50px; }
.add-comment-heading {
    font-size: 1.2rem;
    font-weight: 900;
    letter-spacing: 2px;
    color: #fff;
    margin-bottom: 28px;
}
.form-group-post {
    position: relative;
    margin-bottom: 16px;
}
.form-group-post i {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255,255,255,0.3); /* Subtle icons */
    font-size: 0.8rem;
}
.form-group-textarea i { top: 18px; transform: none; }
.form-group-post input,
.form-group-post textarea {
    width: 100%;
    background: transparent; /* Clean transparent look */
    border: none;
    border-bottom: 1px solid rgba(255,255,255,0.15); /* Minimalist underline */
    border-radius: 0;
    color: #fff;
    padding: 12px 0 12px 30px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    outline: none;
    box-sizing: border-box;
}
.form-group-post textarea {
    border-radius: 0;
    resize: vertical;
    min-height: 100px;
}
.form-group-post input:focus,
.form-group-post textarea:focus { border-color: var(--primary-theme); padding-left: 35px; }
.submit-comment-btn {
    background: var(--primary-theme);
    color: #000;
    border: none;
    padding: 14px 36px;
    border-radius: 30px;
    font-weight: 900;
    font-size: 0.85rem;
    letter-spacing: 1.5px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
    margin-top: 8px;
}
.submit-comment-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
}

@media (max-width: 992px) {
    .post-layout-container {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 600px) {
    .new-post-title { font-size: 1.8rem; }
    .new-post-meta { font-size: 0.8rem; }
    .comment-reply { margin-left: 20px; }
}

/* Swiper Gallery Styles */
.feature-swiper { width: 100%; height: auto; border-radius: 12px; }
.swiper-pagination-bullet { background: #fff !important; opacity: 0.5; }
.swiper-pagination-bullet-active { background: var(--primary-theme) !important; opacity: 1; }
.sidebar-card.no-padding { padding: 0 !important; background: transparent; border: none; }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Swiper('.feature-swiper', {
        loop: true,
        effect: 'fade',
        fadeEffect: { crossFade: true },
        autoplay: { delay: 3000, disableOnInteraction: false },
        pagination: { el: '.swiper-pagination', clickable: true },
    });
});
function checkAccess(url, pass) {
    const input = prompt("This link is protected. Please enter the password:");
    if (input === pass) {
        window.open(url, '_blank');
    } else if (input !== null) {
        alert("Incorrect password.");
    }
}
</script>
<?php include 'includes/public_footer.php'; ?>
