<?php
require '../db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Prevent self-deletion
    if ($id == $_SESSION['user_id']) {
        header("Location: settings.php?status=error&msg=cannot_delete_self&tab=users");
    } else {
        $conn->query("DELETE FROM users WHERE id=$id");
        header("Location: settings.php?status=deleted&tab=users");
    }
    exit;
}

// Fetch all users
$users_res = $conn->query("SELECT id, username, full_name, role FROM users ORDER BY id ASC");
$users = [];
while ($r = $users_res->fetch_assoc()) $users[] = $r;

$page_title = "User Management";
$show_save  = false;
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid px-4 py-4">

        <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-<?= ($_GET['status'] == 'error') ? 'danger' : 'success' ?> alert-dismissible d-flex align-items-center mb-4">
            <i class="fa <?= ($_GET['status'] == 'error') ? 'fa-exclamation-circle' : 'fa-check-circle' ?> me-2"></i>
            <?php
            $msgs = [
                'saved' => 'User saved successfully!', 
                'deleted' => 'User removed.',
                'error' => 'An error occurred.'
            ];
            if (isset($_GET['msg']) && $_GET['msg'] == 'cannot_delete_self') echo "You cannot delete your own account.";
            else echo $msgs[$_GET['status']] ?? 'Done!';
            ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0"><i class="fa fa-users-cog me-2 text-primary"></i>User Management</h4>
            <a href="user_edit.php" class="btn btn-primary rounded-pill px-4">
                <i class="fa fa-user-plus me-1"></i> Add New User
            </a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($users)): ?>
                    <div class="text-center py-5">
                        <i class="fa fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No users found.</p>
                    </div>
                <?php else: ?>
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4" style="width:60px">ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-4 text-muted small">#<?= $user['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3" style="width:35px;height:35px;font-size:0.9rem;">
                                        <?= substr($user['full_name'], 0, 1) ?>
                                    </div>
                                    <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                                    <?php if($user['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-primary ms-2 small" style="font-size:0.65rem;">YOU</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><code class="text-primary"><?= htmlspecialchars($user['username']) ?></code></td>
                            <td>
                                <span class="badge rounded-pill bg-light text-dark border">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <a href="user_edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1" title="Edit / Change Password">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <a href="users.php?delete=<?= $user['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-pill"
                                   onclick="return confirm('Remove this user access permanently?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-4 p-3 bg-light rounded border">
            <p class="mb-0 small text-muted">
                <i class="fa fa-info-circle me-1"></i>
                <strong>Security Note:</strong> Only administrators can manage other users. Removing a user will immediately revoke their access to this panel.
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
