<?php
require '../db.php';

// Fetch visitor logs with pagination
$limit = 20;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit;

$total_res = $conn->query("SELECT COUNT(*) FROM visitor_logs");
$total_rows = $total_res->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);

$logs_res = $conn->query("SELECT * FROM visitor_logs ORDER BY visited_at DESC LIMIT $offset, $limit");
$logs = [];
while ($row = $logs_res->fetch_assoc()) $logs[] = $row;

$page_title = "Visitor Insights";
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">Visitor Insights</h4>
                <p class="text-muted small mb-0">Detailed log of unique visitors and their interactions</p>
            </div>
            <div class="text-end">
                <div class="badge bg-primary rounded-pill px-3 py-2 shadow-sm">
                    Total Records: <?= number_format($total_rows) ?>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3">Timestamp</th>
                            <th class="py-3">IP Address</th>
                            <th class="py-3">Location</th>
                            <th class="py-3">Page Visited</th>
                            <th class="pe-4 py-3">Device / Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No visitor logs found yet.</td>
                            </tr>
                        <?php else: foreach ($logs as $log): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="small fw-bold"><?= date('M j, Y', strtotime($log['visited_at'])) ?></div>
                                    <div class="smaller text-muted"><?= date('h:i A', strtotime($log['visited_at'])) ?></div>
                                </td>
                                <td>
                                    <code class="text-primary fw-bold"><?= htmlspecialchars($log['ip_address']) ?></code>
                                </td>
                                <td id="loc-<?= $log['id'] ?>">
                                    <span class="text-muted smaller">Detecting...</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace smaller"><?= htmlspecialchars($log['page_url'] ?: '/') ?></span>
                                </td>
                                <td class="pe-4">
                                    <div class="text-truncate text-muted smaller" style="max-width: 250px;" title="<?= htmlspecialchars($log['user_agent']) ?>">
                                        <?= htmlspecialchars($log['user_agent']) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center align-items-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link rounded-pill px-4 me-3 border-0 shadow-sm fw-bold" href="?p=<?= $page - 1 ?>">
                            <i class="fa fa-chevron-left me-2"></i> Previous
                        </a>
                    </li>
                    <li class="page-item d-none d-md-block">
                        <span class="text-muted small mx-3">Page <strong><?= $page ?></strong> of <?= $total_pages ?></span>
                    </li>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link rounded-pill px-4 ms-3 border-0 shadow-sm fw-bold" href="?p=<?= $page + 1 ?>">
                            Next <i class="fa fa-chevron-right ms-2"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const logs = <?= json_encode($logs) ?>;
    
    logs.forEach(log => {
        const locCell = document.getElementById(`loc-${log.id}`);
        if (log.ip_address === '::1' || log.ip_address === '127.0.0.1') {
            locCell.innerHTML = '<span class="badge bg-dark text-white smaller">Localhost</span>';
            return;
        }

        fetch(`https://ipapi.co/${log.ip_address}/json/`)
            .then(res => res.json())
            .then(data => {
                if (data.city && data.country_name) {
                    locCell.innerHTML = `
                        <div class="d-flex align-items-center">
                            <img src="https://flagcdn.com/16x12/${data.country_code.toLowerCase()}.png" class="me-2 shadow-sm">
                            <span class="small fw-bold">${data.city}, ${data.country_name}</span>
                        </div>`;
                } else {
                    locCell.innerHTML = '<span class="text-muted smaller">Unknown</span>';
                }
            })
            .catch(() => {
                locCell.innerHTML = '<span class="text-muted smaller">N/A</span>';
            });
    });
});
</script>

<style>
    .smaller { font-size: 0.75rem; }
    .table thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #666; border-bottom: none; }
    .pagination .page-link { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; color: #333; }
    .pagination .page-item.active .page-link { background-color: var(--primary-theme); color: #fff; }
</style>
