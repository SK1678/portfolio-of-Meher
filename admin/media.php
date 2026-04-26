<?php
require '../db.php';

// Handle Single or Bulk Deletion
if (isset($_POST['delete_files'])) {
    $files_to_delete = (array)$_POST['delete_files'];
    $deleted_count = 0;
    
    foreach ($files_to_delete as $file_to_delete) {
        $file_path = '../' . $file_to_delete;
        if (file_exists($file_path)) {
            if (unlink($file_path)) {
                $deleted_count++;
            }
        }
    }
    header("Location: /po/admin/media?status=deleted&count=$deleted_count");
    exit();
}

// Fetch all settings to check for usage
$res = $conn->query("SELECT * FROM homepage_settings");
$settings = [];
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Extract used media paths
$used_media = [];

// 1. Homepage Settings
if (!empty($settings['bg_media'])) {
    $paths = explode(',', $settings['bg_media']);
    foreach ($paths as $p) $used_media[] = trim($p);
}
if (!empty($settings['hero_buttons'])) {
    $btns = json_decode($settings['hero_buttons'], true);
    if (is_array($btns)) {
        foreach ($btns as $b) {
            if (!empty($b['link'])) $used_media[] = trim($b['link']);
        }
    }
}
$setting_keys = ['favicon', 'admin_favicon', 'cv_link', 'about_image', 'logo'];
foreach ($setting_keys as $key) {
    if (!empty($settings[$key])) $used_media[] = trim($settings[$key]);
}

// 2. Posts (Feature Images, Hero Images, and Embedded Content)
$res_posts = $conn->query("SELECT feature_image, hero_image, content FROM posts");
if ($res_posts) {
    while ($r = $res_posts->fetch_assoc()) {
        // Feature Images (comma-separated)
        if (!empty($r['feature_image'])) {
            $f_imgs = explode(',', $r['feature_image']);
            foreach ($f_imgs as $fi) $used_media[] = trim($fi);
        }
        // Hero Image
        if (!empty($r['hero_image'])) {
            $used_media[] = trim($r['hero_image']);
        }
        // Embedded Images in Content
        if (!empty($r['content'])) {
            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $r['content'], $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $src) {
                    $used_media[] = trim($src);
                }
            }
        }
    }
}

// 3. Post Attachments
$res_p_att = $conn->query("SELECT url FROM post_attachments WHERE url IS NOT NULL AND url != ''");
if ($res_p_att) {
    while ($r = $res_p_att->fetch_assoc()) {
        $used_media[] = trim($r['url']);
    }
}

// 4. About Item Attachments
$res_i_att = $conn->query("SELECT url FROM item_attachments WHERE url IS NOT NULL AND url != ''");
if ($res_i_att) {
    while ($r = $res_i_att->fetch_assoc()) {
        $used_media[] = trim($r['url']);
    }
}

// Extract basenames for reliable matching regardless of path prefixes
$used_basenames = [];
foreach ($used_media as $path) {
    if (!empty($path)) {
        $used_basenames[] = basename($path);
    }
}
$used_basenames = array_unique($used_basenames);

// Scan directory
$upload_dir = '../assets/uploads/';
$all_files = is_dir($upload_dir) ? array_diff(scandir($upload_dir), array('.', '..')) : [];

$page_title = "Media Library";
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid px-4 py-4">
        <form id="bulk-media-form" action="media" method="POST">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Media Library</h2>
                    <p class="text-muted small mb-0">Manage all uploaded assets for your portfolio.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- Selection Summary -->
                    <div id="selection-summary" class="bg-white px-3 py-2 rounded-pill shadow-sm small fw-bold d-none">
                        <span id="selected-count">0</span> items selected
                    </div>
                    <!-- Bulk Actions -->
                    <div id="bulk-actions" class="d-none">
                        <button type="submit" name="bulk_delete" class="btn btn-danger btn-sm rounded-pill px-4 shadow-sm" onclick="return confirm('Delete selected files permanently?')">
                            <i class="fa fa-trash-alt me-2"></i> Bulk Delete
                        </button>
                    </div>
                    <div class="form-check d-flex align-items-center bg-white px-3 py-2 rounded-pill shadow-sm">
                        <input class="form-check-input me-2 mt-0" type="checkbox" id="selectAll">
                        <label class="form-check-label small fw-bold mt-0" for="selectAll">Select All</label>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
                <div class="alert alert-warning alert-dismissible fade show shadow-sm border-0 bg-white" role="alert">
                    <i class="fa fa-info-circle me-2 text-warning"></i> <strong><?= $_GET['count'] ?? 0 ?></strong> files were permanently deleted.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <?php foreach ($all_files as $file): 
                    $relative_path = 'assets/uploads/' . $file;
                    $is_used = in_array(basename($file), $used_basenames);
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    $is_video = strtolower($ext) == 'mp4';
                ?>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                        <div class="card h-100 border-0 shadow-sm overflow-hidden position-relative media-card <?= $is_used ? 'in-use' : 'unused' ?>">
                            <!-- Checkbox -->
                            <div class="position-absolute top-0 end-0 m-2 z-3">
                                <input class="form-check-input file-checkbox shadow-sm" type="checkbox" name="delete_files[]" value="<?= $relative_path ?>">
                            </div>
                            
                            <!-- Status Badge -->
                            <div class="position-absolute top-0 start-0 m-2 z-3">
                                <?php if ($is_used): ?>
                                    <span class="badge bg-primary shadow-sm"><i class="fa fa-check-circle me-1"></i> In Use</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary shadow-sm">Unused</span>
                                <?php endif; ?>
                            </div>

                            <!-- Preview Area -->
                            <div class="media-preview-bg bg-dark d-flex align-items-center justify-content-center overflow-hidden" style="height: 160px;">
                                <?php if ($is_video): ?>
                                    <i class="fa fa-video fa-3x text-white-50"></i>
                                <?php else: ?>
                                    <img src="../<?= $relative_path ?>" class="img-fluid h-100 w-100 object-fit-cover transition-img">
                                <?php endif; ?>
                            </div>

                            <!-- Card Body -->
                            <div class="card-body p-3">
                                <p class="card-text text-truncate mb-1 fw-semibold small" title="<?= $file ?>"><?= $file ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted extra-small"><?= strtoupper($ext) ?> • <?= round(filesize($upload_dir . $file) / 1024, 1) ?> KB</span>
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle shadow-none" type="button" data-bs-toggle="dropdown">
                                            <i class="fa fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                            <li><a class="dropdown-item" href="../<?= $relative_path ?>" target="_blank"><i class="fa fa-eye me-2"></i> View Full</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" onclick="deleteSingle('<?= $relative_path ?>', <?= $is_used ? 'true' : 'false' ?>)">
                                                    <i class="fa fa-trash-alt me-2"></i> Delete Permanently
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($all_files)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="fa fa-folder-open fa-4x text-light mb-3"></i>
                        <h4 class="text-muted">No media found in library</h4>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<style>
    .media-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 2px solid transparent !important; }
    .media-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important; }
    .media-card.selected { border-color: var(--primary-purple) !important; background: rgba(142, 68, 173, 0.02); }
    .media-card.in-use { opacity: 0.85; }
    .media-card.in-use:hover { opacity: 1; }
    
    .transition-img { transition: transform 0.5s ease; }
    .media-card:hover .transition-img { transform: scale(1.1); }
    
    .file-checkbox { width: 20px; height: 20px; cursor: pointer; border-radius: 4px; border: 2px solid #ddd; }
    .file-checkbox:checked { background-color: var(--primary-purple); border-color: var(--primary-purple); }
    
    .extra-small { font-size: 0.7rem; }
    .object-fit-cover { object-fit: cover; }
    .z-3 { z-index: 3; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.file-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectionSummary = document.getElementById('selection-summary');
    const selectedCount = document.getElementById('selected-count');
    const form = document.getElementById('bulk-media-form');

    function updateBulkUI() {
        const checked = document.querySelectorAll('.file-checkbox:checked').length;
        if (checked > 0) {
            bulkActions.classList.remove('d-none');
            selectionSummary.classList.remove('d-none');
            selectedCount.textContent = checked;
        } else {
            bulkActions.classList.add('d-none');
            selectionSummary.classList.add('d-none');
        }
        
        checkboxes.forEach(cb => {
            const card = cb.closest('.media-card');
            if (cb.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
    }

    selectAll.addEventListener('change', (e) => {
        checkboxes.forEach(cb => {
            if (!cb.disabled) cb.checked = e.target.checked;
        });
        updateBulkUI();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkUI);
    });

    function deleteSingle(path, isUsed = false) {
        let msg = 'Permanently delete this file? This cannot be undone.';
        if (isUsed) {
            msg = 'WARNING: This file is currently IN USE. Deleting it will cause broken images on your site. Are you absolutely sure?';
        }
        if (confirm(msg)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_files[]';
            input.value = path;
            form.appendChild(input);
            form.submit();
        }
    }
</script>
</body>
</html>
