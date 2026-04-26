<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
require '../db.php';

// Handle Actions
if (isset($_GET['action'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM contact_messages WHERE id=$id");
        header("Location: messages.php?msg=deleted");
        exit;
    } elseif ($_GET['action'] == 'read') {
        $conn->query("UPDATE contact_messages SET status='read' WHERE id=$id");
        header("Location: messages.php?msg=updated");
        exit;
    }
}

// Fetch Messages
$res = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = [];
while ($row = $res->fetch_assoc()) {
    $messages[] = $row;
}

$page_title = "Contact Messages";
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-4 px-4">
        <div class="content-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">Contact Messages</h2>
                <p class="text-muted small mb-0">View and manage messages from your portfolio contact form.</p>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Action completed successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4 py-3">Date</th>
                                        <th class="py-3">From</th>
                                        <th class="py-3">Subject</th>
                                        <th class="py-3">Status</th>
                                        <th class="text-end pe-4 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($messages)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <div class="text-muted">
                                                    <i class="fa fa-envelope-open fa-3x mb-3 opacity-25"></i>
                                                    <p>No messages yet.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: foreach ($messages as $m): ?>
                                        <tr class="<?= $m['status'] == 'unread' ? 'fw-bold bg-light bg-opacity-50' : '' ?>">
                                            <td class="ps-4 small text-muted">
                                                <?= date('d M Y, H:i', strtotime($m['created_at'])) ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                        <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="small"><?= htmlspecialchars($m['name']) ?></div>
                                                        <div class="text-muted smaller" style="font-size: 0.75rem;"><?= htmlspecialchars($m['email']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small">
                                                <?= htmlspecialchars($m['subject']) ?>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill <?= $m['status'] == 'unread' ? 'bg-primary' : 'bg-secondary bg-opacity-25 text-muted' ?>">
                                                    <?= strtoupper($m['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 me-2" data-bs-toggle="modal" data-bs-target="#msgModal<?= $m['id'] ?>">
                                                        View
                                                    </button>
                                                    <?php if ($m['status'] == 'unread'): ?>
                                                        <a href="messages.php?action=read&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2" title="Mark as Read">
                                                            <i class="fa fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="messages.php?action=delete&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Are you sure you want to delete this message?')">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                </div>

                                                <!-- Message Modal -->
                                                <div class="modal fade" id="msgModal<?= $m['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content border-0 rounded-4 shadow">
                                                            <div class="modal-header border-0 pb-0">
                                                                <h5 class="modal-title fw-bold">Message Details</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <div class="mb-3">
                                                                    <label class="text-muted smaller text-uppercase">From</label>
                                                                    <div class="fw-bold"><?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['email']) ?>)</div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="text-muted smaller text-uppercase">Subject</label>
                                                                    <div class="fw-bold"><?= htmlspecialchars($m['subject']) ?></div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="text-muted smaller text-uppercase">Date</label>
                                                                    <div class="text-muted small"><?= date('F j, Y, g:i a', strtotime($m['created_at'])) ?></div>
                                                                </div>
                                                                <hr class="opacity-10">
                                                                <div class="mb-0">
                                                                    <label class="text-muted smaller text-uppercase mb-2 d-block">Message</label>
                                                                    <div class="p-3 bg-light rounded-3 small" style="white-space: pre-wrap; line-height: 1.6;"><?= htmlspecialchars($m['message']) ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0">
                                                                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                                                                <?php if ($m['status'] == 'unread'): ?>
                                                                    <a href="messages.php?action=read&id=<?= $m['id'] ?>" class="btn btn-primary rounded-pill px-4">Mark as Read</a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
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
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
