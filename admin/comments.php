<?php
require '../db.php';

// Handle Actions
if (isset($_GET['action'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM post_comments WHERE id=$id");
        header("Location: comments.php?msg=deleted");
        exit;
    } elseif ($_GET['action'] == 'read') {
        $conn->query("UPDATE post_comments SET is_read=1 WHERE id=$id");
        header("Location: comments.php?msg=updated");
        exit;
    }
}

// Fetch Comments with Post Titles
$query = "SELECT c.*, p.title as post_title, p.slug as post_slug 
          FROM post_comments c 
          JOIN posts p ON c.post_id = p.id 
          ORDER BY c.created_at DESC";
$res = $conn->query($query);
$comments = [];
while ($row = $res->fetch_assoc()) $comments[] = $row;

$page_title = "Post Comments";
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-4 px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">Post Comments</h2>
                <p class="text-muted small mb-0">Manage and moderate discussion on your blog posts.</p>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i> Action completed successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Date</th>
                            <th class="py-3">Commenter</th>
                            <th class="py-3">On Post</th>
                            <th class="py-3">Comment Content</th>
                            <th class="text-end pe-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($comments)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No comments found.</td>
                            </tr>
                        <?php else: foreach ($comments as $c): ?>
                            <tr class="<?= $c['is_read'] == 0 ? 'fw-bold bg-light bg-opacity-50' : '' ?>">
                                <td class="ps-4 small text-muted">
                                    <?= date('M j, Y', strtotime($c['created_at'])) ?>
                                    <div class="smaller"><?= date('h:i A', strtotime($c['created_at'])) ?></div>
                                </td>
                                <td>
                                    <div class="small fw-bold"><?= htmlspecialchars($c['name']) ?></div>
                                    <div class="text-muted smaller"><?= htmlspecialchars($c['email']) ?></div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 150px;">
                                        <a href="../post/<?= $c['post_slug'] ?>" target="_blank" class="text-primary small text-decoration-none fw-bold">
                                            <i class="fa fa-external-link-alt me-1"></i> <?= htmlspecialchars($c['post_title']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div class="p-2 rounded bg-white border small shadow-sm" style="max-width: 300px; white-space: normal;">
                                        <?= htmlspecialchars($c['comment']) ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <?php if ($c['is_read'] == 0): ?>
                                            <a href="comments.php?action=read&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 me-2" title="Mark as Read">
                                                <i class="fa fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="comments.php?action=delete&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Delete this comment permanentely?')" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .smaller { font-size: 0.7rem; }
    .table td { vertical-align: top; padding: 1.2rem 0.5rem; }
</style>

<?php include 'includes/footer.php'; ?>
