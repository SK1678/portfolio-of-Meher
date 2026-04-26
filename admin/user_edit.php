<?php
require '../db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user = null;
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($edit_id) {
    $res = $conn->query("SELECT id, username, full_name, role FROM users WHERE id=$edit_id LIMIT 1");
    $user = $res ? $res->fetch_assoc() : null;
}

$page_title = $edit_id ? "Edit User" : "Add New User";
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid px-4 py-4">
        <form action="save_user" method="POST" id="user-form">
            <?php if($edit_id): ?>
                <input type="hidden" name="id" value="<?= $edit_id ?>">
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="users.php" class="text-muted text-decoration-none small"><i class="fa fa-arrow-left me-1"></i> Back to Users</a>
                    <h4 class="fw-bold mb-0 mt-1"><?= $edit_id ? 'Edit Admin User' : 'Create New Admin' ?></h4>
                </div>
                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="fa fa-save me-1"></i> Save User
                </button>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-4"><i class="fa fa-id-card me-2 text-primary"></i> Profile Information</h6>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Full Name</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" 
                                       placeholder="e.g. John Doe" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Username</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                                       placeholder="Login handle" required>
                            </div>

                            <div class="mb-0">
                                <label class="form-label small fw-bold">Role</label>
                                <select name="role" class="form-select">
                                    <option value="admin" <?= ($user['role'] ?? 'admin') == 'admin' ? 'selected' : '' ?>>Administrator</option>
                                    <option value="editor" <?= ($user['role'] ?? '') == 'editor' ? 'selected' : '' ?>>Editor</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-4"><i class="fa fa-key me-2 text-primary"></i> Security & Password</h6>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">
                                    <?= $edit_id ? 'New Password' : 'Password' ?>
                                </label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password-field" class="form-control" 
                                           placeholder="<?= $edit_id ? 'Leave blank to keep current' : 'Enter strong password' ?>" 
                                           <?= $edit_id ? '' : 'required' ?>>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="fa fa-eye" id="toggle-icon"></i>
                                    </button>
                                </div>
                            </div>

                            <?php if($edit_id): ?>
                            <div class="alert alert-warning py-2 px-3 mb-0 small border-0" style="background: rgba(243, 156, 18, 0.1); color: #d35400;">
                                <i class="fa fa-info-circle me-1"></i>
                                Only fill the password field if you want to change it.
                            </div>
                            <?php endif; ?>
                            
                            <div class="mt-4">
                                <label class="form-label small fw-bold d-block">Password Requirements:</label>
                                <ul class="small text-muted ps-3 mb-0">
                                    <li>Minimum 6 characters</li>
                                    <li>Mix of letters and numbers recommended</li>
                                    <li>This will be hashed securely before saving</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword() {
    const field = document.getElementById('password-field');
    const icon = document.getElementById('toggle-icon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
