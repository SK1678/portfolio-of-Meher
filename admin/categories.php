<?php
require '../db.php';

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cat'])) {
    $name      = trim($conn->real_escape_string($_POST['name'] ?? ''));
    $slug      = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($conn->real_escape_string($_POST['slug'] ?? ''))));
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    $parent_val = $parent_id > 0 ? $parent_id : 'NULL';
    if ($name && $slug) {
        $conn->query("INSERT INTO categories (name, slug, parent_id) VALUES ('$name', '$slug', $parent_val)");
    }
    header("Location: categories.php?status=added");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM categories WHERE id=$id");
    header("Location: categories.php?status=deleted");
    exit;
}

// Fetch all categories with parent name
$cats_res = $conn->query("
    SELECT c.*, p.name AS parent_name 
    FROM categories c 
    LEFT JOIN categories p ON p.id = c.parent_id 
    ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.name
");
$categories = [];
while ($r = $cats_res->fetch_assoc()) $categories[] = $r;

// Fetch parent categories for the add form
$parents_res = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
$parents = [];
while ($r = $parents_res->fetch_assoc()) $parents[] = $r;

$page_title = "Categories";
$show_save  = false;
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid px-4 py-4">

        <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-success alert-dismissible d-flex align-items-center mb-4" role="alert">
            <i class="fa fa-check-circle me-2"></i>
            <?= $_GET['status'] === 'added' ? 'Category added successfully!' : 'Category deleted.' ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Add Category Form -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold"><i class="fa fa-plus-circle me-2 text-primary"></i>Add New Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Category Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Brand Design" required
                                       oninput="autoSlug(this)">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Slug</label>
                                <input type="text" name="slug" id="slug-field" class="form-control" placeholder="e.g. brand-design" required>
                                <small class="text-muted">Auto-generated. Must be unique.</small>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Parent Category</label>
                                <select name="parent_id" class="form-select">
                                    <option value="0">— None (Top Level) —</option>
                                    <?php foreach ($parents as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Set parent to <strong>Portfolio</strong> to show as a portfolio filter.</small>
                            </div>
                            <button type="submit" name="add_cat" class="btn btn-primary w-100 rounded-pill">
                                <i class="fa fa-plus me-1"></i> Add Category
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Categories List -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fa fa-tags me-2 text-primary"></i>All Categories</h5>
                        <span class="badge bg-primary rounded-pill"><?= count($categories) ?> total</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4">Name</th>
                                    <th>Slug</th>
                                    <th>Parent</th>
                                    <th class="text-end px-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td class="px-4">
                                        <?php if ($cat['parent_id']): ?>
                                            <span class="text-muted ms-3">↳</span>
                                        <?php endif; ?>
                                        <strong><?= htmlspecialchars($cat['name']) ?></strong>
                                    </td>
                                    <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                                    <td>
                                        <?php if ($cat['parent_name']): ?>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($cat['parent_name']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-primary-subtle text-primary border-0">Root</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end px-4">
                                        <?php if (!in_array($cat['slug'], ['blog', 'portfolio'])): ?>
                                        <a href="categories.php?delete=<?= $cat['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-pill"
                                           onclick="return confirm('Delete category \'<?= htmlspecialchars($cat['name']) ?>\'?')">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted small">Protected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function autoSlug(input) {
    const slug = input.value.toLowerCase().trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
    document.getElementById('slug-field').value = slug;
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
