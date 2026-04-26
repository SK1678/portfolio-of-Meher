<?php
require '../db.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM posts WHERE id=$id");
    header("Location: posts.php?status=deleted");
    exit;
}

// Handle status toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE posts SET status = IF(status='published','draft','published') WHERE id=$id");
    header("Location: posts.php?status=updated");
    exit;
}

// Fetch all posts with their categories
$posts_res = $conn->query("
    SELECT p.*, GROUP_CONCAT(c.name SEPARATOR ', ') AS cats
    FROM posts p
    LEFT JOIN post_categories pc ON pc.post_id = p.id
    LEFT JOIN categories c ON c.id = pc.category_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$posts = [];
while ($r = $posts_res->fetch_assoc()) $posts[] = $r;

$page_title = "Posts & Portfolio";
$show_save  = false;
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid px-4 py-4">

        <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-success alert-dismissible d-flex align-items-center mb-4">
            <i class="fa fa-check-circle me-2"></i>
            <?php
            $msgs = ['saved' => 'Post saved successfully!', 'deleted' => 'Post deleted.', 'updated' => 'Post status updated.'];
            echo $msgs[$_GET['status']] ?? 'Done!';
            ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0"><i class="fa fa-th-large me-2 text-primary"></i>Posts & Portfolio</h4>
            <div class="d-flex gap-2">
                <a href="categories.php" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="fa fa-tags me-1"></i> Categories
                </a>
                <a href="post_edit.php" class="btn btn-primary rounded-pill px-4">
                    <i class="fa fa-plus me-1"></i> New Post
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No posts yet. <a href="post_edit.php">Create your first post</a>.</p>
                    </div>
                <?php else: ?>
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4" style="width:60px"></th>
                            <th>Title</th>
                            <th>Categories</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                        <tr>
                            <td class="px-4">
                                <?php 
                                $f_images = !empty($post['feature_image']) ? explode(',', $post['feature_image']) : [];
                                if (!empty($f_images)): ?>
                                    <img src="../<?= htmlspecialchars($f_images[0]) ?>" 
                                         style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
                                <?php else: ?>
                                    <div style="width:50px;height:50px;background:#f0f0f0;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#ccc;">
                                        <i class="fa fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($post['title']) ?></strong><br>
                                <small class="text-muted">/<?= htmlspecialchars($post['slug']) ?></small>
                            </td>
                            <td>
                                <?php foreach (explode(', ', $post['cats'] ?? '') as $cat): ?>
                                    <?php if (trim($cat)): ?>
                                    <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars(trim($cat)) ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <a href="posts.php?toggle=<?= $post['id'] ?>" class="badge text-decoration-none
                                    <?= $post['status'] === 'published' ? 'bg-success' : 'bg-secondary' ?>"
                                   title="Click to toggle">
                                    <?= ucfirst($post['status']) ?>
                                </a>
                            </td>
                            <td class="text-muted small"><?= date('d M Y', strtotime($post['created_at'])) ?></td>
                            <td class="text-end px-4">
                                <a href="post_edit.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="../post.php?slug=<?= urlencode($post['slug']) ?>" target="_blank" 
                                   class="btn btn-sm btn-outline-secondary rounded-pill me-1">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="posts.php?delete=<?= $post['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-pill"
                                   onclick="return confirm('Delete this post permanently?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
