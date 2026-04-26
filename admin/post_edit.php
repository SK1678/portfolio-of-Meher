<?php
require '../db.php';

$post = null;
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$assigned_cat_ids = [];
$existing_attachments = [];

if ($edit_id) {
    // Migration: ensure hero_image column exists
    $conn->query("ALTER TABLE posts ADD COLUMN IF NOT EXISTS hero_image VARCHAR(500) AFTER feature_image");
    
    $res = $conn->query("SELECT * FROM posts WHERE id=$edit_id LIMIT 1");
    $post = $res ? $res->fetch_assoc() : null;
    if ($post) {
        $cat_res = $conn->query("SELECT category_id FROM post_categories WHERE post_id=$edit_id");
        while ($r = $cat_res->fetch_assoc()) $assigned_cat_ids[] = $r['category_id'];
        $att_res = $conn->query("SELECT * FROM post_attachments WHERE post_id=$edit_id ORDER BY id ASC");
        while ($r = $att_res->fetch_assoc()) $existing_attachments[] = $r;
    }
} else {
    // Migration for new posts too
    $conn->query("ALTER TABLE posts ADD COLUMN IF NOT EXISTS hero_image VARCHAR(500) AFTER feature_image");
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($conn->real_escape_string($_POST['title'] ?? ''));
    $slug     = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($conn->real_escape_string($_POST['slug'] ?? $title))));
    $content  = $_POST['content'] ?? '';
    $excerpt  = trim($conn->real_escape_string($_POST['excerpt'] ?? ''));
    $status   = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'draft';
    $cat_ids  = array_map('intval', $_POST['categories'] ?? []);

    // Handle feature image upload (Multiple)
    $feature_images = [];
    if (!empty($_POST['existing_feature_image'])) {
        $feature_images = explode(',', $_POST['existing_feature_image']);
    }

    if (isset($_FILES['feature_image']) && !empty($_FILES['feature_image']['name'][0])) {
        $upload_dir = '../assets/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        foreach ($_FILES['feature_image']['name'] as $i => $name) {
            if ($_FILES['feature_image']['error'][$i] === 0) {
                $ext      = pathinfo($name, PATHINFO_EXTENSION);
                $new_name = 'post_' . uniqid() . '_' . $i . '.' . strtolower($ext);
                if (move_uploaded_file($_FILES['feature_image']['tmp_name'][$i], $upload_dir . $new_name)) {
                    $feature_images[] = 'assets/uploads/' . $new_name;
                }
            }
        }
    }
    $feature_image_str = $conn->real_escape_string(implode(',', $feature_images));

    // Handle hero background image upload
    $hero_image = $conn->real_escape_string($_POST['existing_hero_image'] ?? ($post['hero_image'] ?? ''));
    if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === 0) {
        $upload_dir = '../assets/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext      = pathinfo($_FILES['hero_image']['name'], PATHINFO_EXTENSION);
        $new_name = 'hero_' . uniqid() . '.' . strtolower($ext);
        if (move_uploaded_file($_FILES['hero_image']['tmp_name'], $upload_dir . $new_name)) {
            $hero_image = $conn->real_escape_string('assets/uploads/' . $new_name);
        }
    }

    if ($edit_id && $post) {
        // Strip ../ from image paths before saving to DB
        $clean_content = str_replace('src="../assets/uploads/', 'src="assets/uploads/', $content);
        $content_esc = $conn->real_escape_string($clean_content);
        $conn->query("UPDATE posts SET title='$title', slug='$slug', content='$content_esc', excerpt='$excerpt', feature_image='$feature_image_str', hero_image='$hero_image', status='$status' WHERE id=$edit_id");
        $post_id = $edit_id;
    } else {
        $base_slug = $slug; $counter = 1;
        while ($conn->query("SELECT id FROM posts WHERE slug='$slug' LIMIT 1")->num_rows > 0) {
            $slug = $base_slug . '-' . $counter++;
        }
        // Strip ../ from image paths before saving to DB
        $clean_content = str_replace('src="../assets/uploads/', 'src="assets/uploads/', $content);
        $content_esc = $conn->real_escape_string($clean_content);
        $conn->query("INSERT INTO posts (title, slug, content, excerpt, feature_image, hero_image, status) VALUES ('$title', '$slug', '$content_esc', '$excerpt', '$feature_image_str', '$hero_image', '$status')");
        $post_id = $conn->insert_id;
    }

    // Re-assign categories
    $conn->query("DELETE FROM post_categories WHERE post_id=$post_id");
    foreach ($cat_ids as $cid) {
        if ($cid > 0) $conn->query("INSERT IGNORE INTO post_categories (post_id, category_id) VALUES ($post_id, $cid)");
    }

    // Save attachments: wipe and re-insert
    $conn->query("DELETE FROM post_attachments WHERE post_id=$post_id");
    $att_labels = $_POST['att_label'] ?? [];
    $att_urls   = $_POST['att_url']   ?? [];
    $att_protected = $_POST['att_protected'] ?? [];
    $att_passwords = $_POST['att_password']  ?? [];

    foreach ($att_labels as $j => $att_label) {
        $att_label = trim($att_label);
        if (empty($att_label)) continue;

        $att_url = trim($att_urls[$j] ?? '');

        // Handle file upload for attachment
        if (!empty($_FILES['att_files']['name'][$j])) {
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename   = time() . '_' . basename($_FILES['att_files']['name'][$j]);
            $target     = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['att_files']['tmp_name'][$j], $target)) {
                $att_url = 'assets/uploads/' . $filename;
            }
        }

        $att_url = $conn->real_escape_string(str_replace('Selected: ', '', $att_url));
        $att_label_esc = $conn->real_escape_string($att_label);
        $is_protected  = (isset($att_protected[$j]) && $att_protected[$j] == 1) ? 1 : 0;
        $password      = $conn->real_escape_string($att_passwords[$j] ?? '');

        $conn->query("INSERT INTO post_attachments (post_id, label, url, is_protected, password)
                      VALUES ($post_id, '$att_label_esc', '$att_url', $is_protected, '$password')");
    }

    header("Location: posts.php?status=saved");
    exit;
}

// Fetch all categories grouped
$all_cats = [];
$cats_res = $conn->query("SELECT c.*, p.name AS parent_name FROM categories c LEFT JOIN categories p ON p.id = c.parent_id ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.name");
while ($r = $cats_res->fetch_assoc()) $all_cats[] = $r;

$page_title = $edit_id ? "Edit Post" : "New Post";
$show_save  = false;
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid px-4 py-4">
        <form method="POST" enctype="multipart/form-data">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="posts.php" class="text-muted text-decoration-none small"><i class="fa fa-arrow-left me-1"></i> Back to Posts</a>
                    <h4 class="fw-bold mb-0 mt-1"><?= $edit_id ? 'Edit Post' : 'New Post' ?></h4>
                </div>
                <div class="d-flex gap-2">
                    <select name="status" class="form-select rounded-pill" style="width:auto;">
                        <option value="draft" <?= ($post['status'] ?? 'draft') == 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($post['status'] ?? '') == 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="fa fa-save me-1"></i> Save Post</button>
                </div>
            </div>

            <div class="row g-4">
                <!-- LEFT: Main Content -->
                <div class="col-lg-8">

                    <!-- Title & Slug -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <input type="text" name="title" class="form-control form-control-lg fw-bold border-0 px-0"
                                   placeholder="Post Title" value="<?= htmlspecialchars($post['title'] ?? '') ?>"
                                   style="font-size:1.6rem;box-shadow:none;" oninput="autoSlug(this)" required>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <span class="text-muted small">Slug:</span>
                                <code id="slug-preview"><?= htmlspecialchars($post['slug'] ?? '') ?></code>
                                <input type="hidden" name="slug" id="slug-input" value="<?= htmlspecialchars($post['slug'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Rich Text Editor -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-0">
                            <div class="editor-toolbar border-bottom px-3 py-2 d-flex flex-wrap gap-1 bg-light rounded-top align-items-center">
                                <div class="btn-group me-1">
                                    <button type="button" class="tb-btn" onclick="fmt('bold')" title="Bold"><i class="fa fa-bold"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('italic')" title="Italic"><i class="fa fa-italic"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('underline')" title="Underline"><i class="fa fa-underline"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('strikethrough')" title="Strikethrough"><i class="fa fa-strikethrough"></i></button>
                                </div>
                                <div class="vr mx-1"></div>
                                <div class="btn-group me-1">
                                    <button type="button" class="tb-btn" onclick="fmtBlock('h2')">H2</button>
                                    <button type="button" class="tb-btn" onclick="fmtBlock('h3')">H3</button>
                                    <button type="button" class="tb-btn" onclick="fmtBlock('p')">P</button>
                                    <button type="button" class="tb-btn" onclick="fmtBlock('blockquote')" title="Quote"><i class="fa fa-quote-left"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmtBlock('pre')" title="Code Block"><i class="fa fa-code"></i></button>
                                </div>
                                <div class="vr mx-1"></div>
                                <div class="btn-group me-1">
                                    <button type="button" class="tb-btn" onclick="fmt('insertUnorderedList')" title="Bullet List"><i class="fa fa-list-ul"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('insertOrderedList')" title="Numbered List"><i class="fa fa-list-ol"></i></button>
                                </div>
                                <div class="vr mx-1"></div>
                                <div class="btn-group me-1">
                                    <button type="button" class="tb-btn" onclick="fmt('justifyLeft')"><i class="fa fa-align-left"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('justifyCenter')"><i class="fa fa-align-center"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('justifyRight')"><i class="fa fa-align-right"></i></button>
                                </div>
                                <div class="vr mx-1"></div>
                                <div class="btn-group me-1">
                                    <button type="button" class="tb-btn" onclick="insertLink()" title="Insert Link"><i class="fa fa-link"></i></button>
                                    <button type="button" class="tb-btn" onclick="openMediaModal()" title="Insert Image from Media Library"><i class="fa fa-image"></i></button>
                                </div>
                                <div class="vr mx-1"></div>
                                <div class="d-flex align-items-center gap-2">
                                    <label class="small text-muted mb-0"><i class="fa fa-font"></i></label>
                                    <input type="color" class="form-control form-control-color p-0 border-0 bg-transparent" style="width:24px;height:24px;cursor:pointer;" onchange="fmt('foreColor', this.value)" title="Text Color">
                                    <label class="small text-muted mb-0"><i class="fa fa-paint-roller"></i></label>
                                    <input type="color" class="form-control form-control-color p-0 border-0 bg-transparent" style="width:24px;height:24px;cursor:pointer;" onchange="fmt('hiliteColor', this.value)" title="Highlight Color" value="#ffff00">
                                </div>
                                <div class="vr mx-1"></div>
                                <div class="btn-group me-1">
                                    <button type="button" class="tb-btn" onclick="imgAlign('left')" title="Float Left"><i class="fa fa-align-left text-primary"></i></button>
                                    <button type="button" class="tb-btn" onclick="imgAlign('center')" title="Center Image"><i class="fa fa-align-center text-primary"></i></button>
                                    <button type="button" class="tb-btn" onclick="imgAlign('right')" title="Float Right"><i class="fa fa-align-right text-primary"></i></button>
                                </div>
                                <div class="vr mx-1"></div>
                                <button type="button" class="tb-btn" onclick="fmt('removeFormat')" title="Clear Formatting"><i class="fa fa-eraser"></i></button>
                            </div>
                            <div id="editor" contenteditable="true"
                                 style="min-height:420px;padding:24px;outline:none;font-size:1rem;line-height:1.85;color:#333;"><?= str_replace('src="assets/uploads/', 'src="../assets/uploads/', $post['content'] ?? '') ?></div>
                            <textarea name="content" id="content-hidden" style="display:none;"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Excerpt -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <label class="form-label fw-semibold">Excerpt <small class="text-muted fw-normal">(Short description for portfolio grid)</small></label>
                            <textarea name="excerpt" class="form-control" rows="3" placeholder="Short summary shown in grid cards..."><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Attachments & Links -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0"><i class="fa fa-link me-2 text-primary"></i>Attachments &amp; Links</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="addAttachment()">
                                <i class="fa fa-plus me-1"></i> Add
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="attachments-list">
                                <?php foreach ($existing_attachments as $j => $att): ?>
                                <div class="attachment-row input-group input-group-sm mb-2 shadow-none border rounded p-1">
                                    <input type="text" name="att_label[]" class="form-control border-0" placeholder="Label e.g. GitHub, Live Demo" value="<?= htmlspecialchars($att['label']) ?>">
                                    <div class="input-group input-group-sm" style="flex:2;">
                                        <input type="text" name="att_url[]" class="form-control border-0" placeholder="URL or uploaded file" value="<?= htmlspecialchars($att['url']) ?>">
                                        <input type="file" name="att_files[<?= $j ?>]" class="d-none" onchange="updateFileName(this)">
                                        <button type="button" class="btn btn-light border-0 text-muted px-2" onclick="this.previousElementSibling.click()" title="Upload File"><i class="fa fa-upload"></i></button>
                                    </div>
                                    <div class="input-group-text border-0 bg-transparent">
                                        <div class="form-check form-switch mb-0">
                                            <input type="hidden" name="att_protected[<?= $j ?>]" value="0">
                                            <input class="form-check-input" type="checkbox" name="att_protected[<?= $j ?>]" value="1" <?= $att['is_protected'] ? 'checked' : '' ?> onchange="togglePass(this)">
                                            <label class="small mb-0 ms-1"><i class="fa fa-lock" style="font-size:0.7rem;"></i></label>
                                        </div>
                                    </div>
                                    <input type="password" name="att_password[]" class="form-control border-0 <?= $att['is_protected'] ? '' : 'd-none' ?>" placeholder="Pass" value="<?= htmlspecialchars($att['password'] ?? '') ?>">
                                    <button type="button" class="btn btn-link text-danger p-0 px-2" onclick="this.closest('.attachment-row').remove()"><i class="fa fa-times"></i></button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <p class="text-muted small mt-2 mb-0"><i class="fa fa-info-circle me-1"></i>Add GitHub links, live demos, PDFs, or any project files.</p>
                        </div>
                    </div>

                </div>

                <!-- RIGHT: Sidebar -->
                <div class="col-lg-4">

                    <!-- Feature Image (Multiple Support) -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="fw-bold mb-0"><i class="fa fa-images me-2 text-primary"></i>Feature Gallery</h6>
                        </div>
                        <div class="card-body">
                            <div id="img-preview-grid" class="d-flex flex-wrap gap-2 mb-3">
                                <?php 
                                $f_images = !empty($post['feature_image']) ? explode(',', $post['feature_image']) : [];
                                foreach ($f_images as $img): ?>
                                    <div class="position-relative">
                                        <img src="../<?= htmlspecialchars($img) ?>" style="width:70px;height:70px;object-fit:cover;border-radius:6px;">
                                        <button type="button" class="btn btn-danger btn-sm p-0 position-absolute top-0 end-0 rounded-circle" style="width:18px;height:18px;font-size:10px;" onclick="removeFeatureImg('<?= $img ?>', this)">×</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="file" name="feature_image[]" class="form-control form-control-sm" accept="image/*" multiple onchange="previewMultipleImgs(this)">
                            <input type="hidden" name="existing_feature_image" id="existing-f-imgs" value="<?= htmlspecialchars($post['feature_image'] ?? '') ?>">
                            <small class="text-muted d-block mt-2">Select multiple images for slider.</small>
                        </div>
                    </div>

                    <!-- Hero Background Image -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="fw-bold mb-0"><i class="fa fa-photo-video me-2 text-primary"></i>Hero Background</h6>
                        </div>
                        <div class="card-body text-center">
                            <div id="hero-preview-wrap">
                                <?php if (!empty($post['hero_image'])): ?>
                                    <img src="../<?= htmlspecialchars($post['hero_image']) ?>" id="hero-preview"
                                         style="width:100%;border-radius:8px;object-fit:cover;max-height:160px;margin-bottom:12px;">
                                <?php else: ?>
                                    <div id="hero-preview-placeholder" style="width:100%;height:140px;background:#4CAF50;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;margin-bottom:12px;border:2px dashed rgba(255,255,255,0.3);">
                                        <div class="text-center">
                                            <i class="fa fa-mountain fa-2x d-block mb-1"></i>
                                            <small>Default Green</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="hero_image" class="form-control form-control-sm" accept="image/*" onchange="previewImg(this, 'hero-preview')">
                            <input type="hidden" name="existing_hero_image" value="<?= htmlspecialchars($post['hero_image'] ?? '') ?>">
                            <small class="text-muted d-block mt-2">Landscape recommended (1920×600)</small>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="fw-bold mb-0"><i class="fa fa-tags me-2 text-primary"></i>Categories</h6>
                        </div>
                        <div class="card-body" style="max-height:280px;overflow-y:auto;">
                            <?php
                            $grouped = [];
                            foreach ($all_cats as $cat) {
                                if ($cat['parent_id'] === null) $grouped[$cat['id']] = ['cat' => $cat, 'children' => []];
                            }
                            foreach ($all_cats as $cat) {
                                if ($cat['parent_id'] !== null && isset($grouped[$cat['parent_id']])) {
                                    $grouped[$cat['parent_id']]['children'][] = $cat;
                                }
                            }
                            ?>
                            <?php foreach ($grouped as $gid => $group): ?>
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="categories[]"
                                           value="<?= $group['cat']['id'] ?>" id="cat_<?= $gid ?>"
                                           <?= in_array($group['cat']['id'], $assigned_cat_ids) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="cat_<?= $gid ?>"><?= htmlspecialchars($group['cat']['name']) ?></label>
                                </div>
                                <?php foreach ($group['children'] as $child): ?>
                                <div class="form-check ms-3">
                                    <input class="form-check-input" type="checkbox" name="categories[]"
                                           value="<?= $child['id'] ?>" id="cat_<?= $child['id'] ?>"
                                           <?= in_array($child['id'], $assigned_cat_ids) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="cat_<?= $child['id'] ?>"><?= htmlspecialchars($child['name']) ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                            <a href="categories.php" class="btn btn-sm btn-outline-secondary rounded-pill mt-2 w-100">
                                <i class="fa fa-plus me-1"></i> Manage Categories
                            </a>
                        </div>
                    </div>

                    <!-- Save -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill"><i class="fa fa-save me-1"></i> Save Post</button>
                        <?php if ($edit_id && $post): ?>
                        <a href="../post.php?slug=<?= urlencode($post['slug']) ?>" target="_blank" class="btn btn-outline-secondary rounded-pill">
                            <i class="fa fa-eye me-1"></i> Preview Post
                        </a>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>

<style>
.tb-btn { background:#fff; border:1px solid #dee2e6; border-radius:5px; width:34px; height:34px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.85rem; color:#444; transition:all 0.15s; }
.tb-btn:hover { background:#f0f0f0; border-color:#8e44ad; color:#8e44ad; }
.tb-btn i { font-size: 0.8rem; }
#editor { border:none !important; }
#editor:focus { outline:none; }
#editor p { margin-bottom:16px; }
#editor h2 { font-size:1.8rem; font-weight:700; margin:32px 0 16px; color: #1e1b2e; }
#editor h3 { font-size:1.4rem; font-weight:700; margin:24px 0 12px; color: #1e1b2e; }
#editor blockquote { border-left:4px solid #8e44ad; padding-left:20px; font-style:italic; color:#666; margin:20px 0; }
#editor pre { background:#f4f6f9; padding:15px; border-radius:8px; font-family:'Courier New', monospace; font-size:0.9rem; overflow-x:auto; margin:20px 0; }
#editor img { max-width:100%; border-radius:12px; margin:20px 0; box-shadow:0 5px 15px rgba(0,0,0,0.08); transition:outline 0.2s; cursor:pointer; position:relative; }
#editor img.img-left { float:left; margin-right:20px; max-width:50%; }
#editor img.img-right { float:right; margin-left:20px; max-width:50%; }
#editor img.img-center { display:block; margin-left:auto; margin-right:auto; }
.attachment-row { align-items: center; }

/* Image Resizer Handle */
.img-resize-handle {
    position: absolute; width: 12px; height: 12px; background: #8e44ad; border: 2px solid #fff;
    border-radius: 50%; cursor: nwse-resize; z-index: 100; display: none;
}

/* Media Modal Styles */
#media-modal-body { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; padding: 15px; max-height: 400px; overflow-y: auto; }
.media-item-thumb { cursor: pointer; border: 2px solid transparent; border-radius: 8px; overflow: hidden; transition: all 0.2s; }
.media-item-thumb:hover { border-color: #8e44ad; transform: scale(1.05); }
.media-item-thumb img { width: 100%; height: 80px; object-fit: cover; }
.media-item-thumb span { font-size: 0.7rem; display: block; text-align: center; padding: 4px; background: #f8f9fa; text-truncate; }
</style>

<!-- Media Selection Modal -->
<div class="modal fade" id="mediaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Select Media</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                    <p class="mb-0 small text-muted">Select from library or upload new:</p>
                    <div class="d-flex gap-2">
                        <input type="file" id="live-upload-input" class="d-none" onchange="handleLiveUpload(this)">
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="document.getElementById('live-upload-input').click()">
                            <i class="fa fa-upload me-1"></i> Upload & Insert
                        </button>
                    </div>
                </div>
                <div id="media-modal-body">
                    <!-- Loaded via JS -->
                    <div class="text-center py-5 w-100"><i class="fa fa-spinner fa-spin fa-2x text-muted"></i></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div id="resizer" class="img-resize-handle"></div>

<script>
let attIdx = <?= count($existing_attachments) ?>;

document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('content-hidden').value = document.getElementById('editor').innerHTML;
});

function fmt(cmd, val = null) { 
    document.getElementById('editor').focus(); 
    document.execCommand(cmd, false, val); 
}
function fmtBlock(tag) { 
    document.getElementById('editor').focus(); 
    document.execCommand('formatBlock', false, tag); 
}
function insertLink() {
    const url = prompt('Enter URL:');
    if (url) { document.getElementById('editor').focus(); document.execCommand('createLink', false, url); }
}

function openMediaModal() {
    const modal = new bootstrap.Modal(document.getElementById('mediaModal'));
    modal.show();
    
    fetch('api_media.php')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('media-modal-body');
            container.innerHTML = '';
            if (data.length === 0) {
                container.innerHTML = '<p class="text-center w-100 py-4">No media files found.</p>';
                return;
            }
            data.forEach(item => {
                const isImg = ['jpg','jpeg','png','gif','webp','svg'].includes(item.type.toLowerCase());
                const div = document.createElement('div');
                div.className = 'media-item-thumb';
                div.innerHTML = `
                    ${isImg ? `<img src="../${item.url}">` : `<div class="bg-light d-flex align-items-center justify-content-center" style="height:80px;"><i class="fa fa-file fa-2x text-muted"></i></div>`}
                    <span class="text-truncate">${item.name}</span>
                `;
                div.onclick = () => {
                    insertMedia(item.url, isImg);
                    modal.hide();
                };
                container.appendChild(div);
            });
        });
}

function insertMedia(url, isImg) {
    document.getElementById('editor').focus();
    if (isImg) {
        // We add ../ here so it shows in the admin editor, 
        // but it will be stripped out on save for the public frontend.
        document.execCommand('insertImage', false, '../' + url);
    } else {
        const link = `<a href="../${url}" target="_blank">${url.split('/').pop()}</a>`;
        document.execCommand('insertHTML', false, link);
    }
}

function handleLiveUpload(input) {
    if (!input.files || !input.files[0]) return;
    
    const formData = new FormData();
    formData.append('file', input.files[0]);
    
    const btn = input.nextElementSibling;
    const oldText = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Uploading...';
    btn.disabled = true;

    fetch('ajax_upload.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = oldText;
        btn.disabled = false;
        if (data.success) {
            const isImg = ['jpg','jpeg','png','gif','webp','svg'].includes(data.type.toLowerCase());
            insertMedia(data.url, isImg);
            bootstrap.Modal.getInstance(document.getElementById('mediaModal')).hide();
            input.value = ''; // Reset input
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        btn.innerHTML = oldText;
        btn.disabled = false;
        alert('Error uploading file');
    });
}

function imgAlign(dir) {
    const sel = window.getSelection();
    if (!sel.rangeCount) return;
    
    let node = sel.anchorNode;
    if (node.nodeType === 3) node = node.parentNode;
    
    const img = (node.tagName === 'IMG') ? node : node.querySelector('img');
    
    if (img) {
        img.className = ''; // Reset
        if (dir === 'left') img.classList.add('img-left');
        if (dir === 'right') img.classList.add('img-right');
        if (dir === 'center') img.classList.add('img-center');
        updateResizerPosition(img);
    } else {
        alert('Please click on an image first to align it.');
    }
}

// Global Image Resizing Logic
let currentResizingImg = null;
const resizer = document.getElementById('resizer');

document.getElementById('editor').addEventListener('click', function(e) {
    if (e.target.tagName === 'IMG') {
        const img = e.target;
        img.style.outline = '2px solid #8e44ad';
        document.querySelectorAll('#editor img').forEach(i => {
            if (i !== img) i.style.outline = 'none';
        });
        currentResizingImg = img;
        updateResizerPosition(img);
        resizer.style.display = 'block';
    } else {
        document.querySelectorAll('#editor img').forEach(i => i.style.outline = 'none');
        resizer.style.display = 'none';
        currentResizingImg = null;
    }
});

function updateResizerPosition(img) {
    const rect = img.getBoundingClientRect();
    const editorRect = document.getElementById('editor').getBoundingClientRect();
    
    resizer.style.left = (rect.right - 6) + 'px';
    resizer.style.top = (rect.bottom - 6) + 'px';
}

resizer.addEventListener('mousedown', function(e) {
    e.preventDefault();
    const startX = e.clientX;
    const startWidth = currentResizingImg.clientWidth;

    function doResize(e) {
        const newWidth = startWidth + (e.clientX - startX);
        if (newWidth > 50) {
            currentResizingImg.style.width = newWidth + 'px';
            updateResizerPosition(currentResizingImg);
        }
    }

    function stopResize() {
        window.removeEventListener('mousemove', doResize);
        window.removeEventListener('mouseup', stopResize);
    }

    window.addEventListener('mousemove', doResize);
    window.addEventListener('mouseup', stopResize);
});

// Update resizer on scroll or resize
window.addEventListener('scroll', () => { if(currentResizingImg) updateResizerPosition(currentResizingImg); });
window.addEventListener('resize', () => { if(currentResizingImg) updateResizerPosition(currentResizingImg); });

function autoSlug(input) {
    const slug = input.value.toLowerCase().trim().replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-');
    document.getElementById('slug-input').value = slug;
    document.getElementById('slug-preview').textContent = slug;
}

function previewMultipleImgs(input) {
    if (input.files) {
        const grid = document.getElementById('img-preview-grid');
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'position-relative';
                div.innerHTML = `<img src="${e.target.result}" style="width:70px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #eee;">`;
                grid.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    }
}

function removeFeatureImg(path, btn) {
    let existing = document.getElementById('existing-f-imgs').value.split(',');
    existing = existing.filter(p => p !== path);
    document.getElementById('existing-f-imgs').value = existing.join(',');
    btn.parentElement.remove();
}

function addAttachment() {
    const j = attIdx++;
    const list = document.getElementById('attachments-list');
    const row = document.createElement('div');
    row.className = 'attachment-row input-group input-group-sm mb-2 shadow-none border rounded p-1';
    row.innerHTML = `
        <input type="text" name="att_label[]" class="form-control border-0" placeholder="Label e.g. GitHub, Live Demo">
        <div class="input-group input-group-sm" style="flex:2;">
            <input type="text" name="att_url[]" class="form-control border-0" placeholder="URL or uploaded file">
            <input type="file" name="att_files[${j}]" class="d-none" onchange="updateFileName(this)">
            <button type="button" class="btn btn-light border-0 text-muted px-2" onclick="this.previousElementSibling.click()" title="Upload File"><i class="fa fa-upload"></i></button>
        </div>
        <div class="input-group-text border-0 bg-transparent">
            <div class="form-check form-switch mb-0">
                <input type="hidden" name="att_protected[${j}]" value="0">
                <input class="form-check-input" type="checkbox" name="att_protected[${j}]" value="1" onchange="togglePass(this)">
                <label class="small mb-0 ms-1"><i class="fa fa-lock" style="font-size:0.7rem;"></i></label>
            </div>
        </div>
        <input type="password" name="att_password[]" class="form-control border-0 d-none" placeholder="Pass">
        <button type="button" class="btn btn-link text-danger p-0 px-2" onclick="this.closest('.attachment-row').remove()"><i class="fa fa-times"></i></button>
    `;
    list.appendChild(row);
}

function updateFileName(input) {
    if (input.files && input.files[0]) {
        const urlInput = input.previousElementSibling;
        urlInput.value = 'Selected: ' + input.files[0].name;
    }
}

function togglePass(cb) {
    const passInput = cb.closest('.attachment-row').querySelector('input[type="password"]');
    if (cb.checked) passInput.classList.remove('d-none');
    else { passInput.classList.add('d-none'); passInput.value = ''; }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
