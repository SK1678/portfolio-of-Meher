<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
require '../db.php';

$backup_dir = __DIR__ . '/backups/';
if (!is_dir($backup_dir)) mkdir($backup_dir, 0777, true);

// Helper function to export SQL
function exportDatabase($conn, $dbName, $filePath) {
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) $tables[] = $row[0];

    $sql = "-- Portfolio CMS Backup\n";
    $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $result = $conn->query("SHOW CREATE TABLE `$table` ");
        $row = $result->fetch_row();
        $sql .= "\n\n" . $row[1] . ";\n\n";

        $result = $conn->query("SELECT * FROM `$table` ");
        while ($row = $result->fetch_assoc()) {
            $keys = array_map(function($k) { return "`$k`"; }, array_keys($row));
            $values = array_map(function($v) use ($conn) { 
                return is_null($v) ? 'NULL' : "'" . $conn->real_escape_string($v) . "'"; 
            }, array_values($row));
            $sql .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
        }
    }
    $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
    return file_put_contents($filePath, $sql);
}

// Helper to import SQL
function importDatabase($conn, $filePath) {
    if (!file_exists($filePath)) return "File not found.";
    $sql = file_get_contents($filePath);
    
    try {
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        $queries = explode(";\n", $sql);
        foreach ($queries as $q) {
            $q = trim($q);
            if (!empty($q)) {
                if (!$conn->query($q)) {
                    throw new Exception($conn->error);
                }
            }
        }
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        return true;
    } catch (Exception $e) {
        return "Restore Error: " . $e->getMessage();
    }
}

// Handle Actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'create') {
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        if (exportDatabase($conn, $db, $backup_dir . $filename)) {
            header("Location: backup.php?msg=created");
            exit;
        }
    } elseif ($_GET['action'] == 'delete') {
        $file = basename($_GET['file']);
        if (file_exists($backup_dir . $file)) unlink($backup_dir . $file);
        header("Location: backup.php?msg=deleted");
        exit;
    } elseif ($_GET['action'] == 'restore') {
        $file = basename($_GET['file']);
        $result = importDatabase($conn, $backup_dir . $file);
        if ($result === true) {
            header("Location: backup.php?msg=restored");
        } else {
            $_SESSION['backup_error'] = $result;
            header("Location: backup.php?msg=error");
        }
        exit;
    } elseif ($_GET['action'] == 'download') {
        $file = basename($_GET['file']);
        if (file_exists($backup_dir . $file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $file . '"');
            readfile($backup_dir . $file);
            exit;
        }
    }
}

// Handle Upload
if (isset($_POST['upload_backup'])) {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == 0) {
        $filename = 'uploaded_' . date('Y-m-d_H-i-s') . '_' . basename($_FILES['backup_file']['name']);
        move_uploaded_file($_FILES['backup_file']['tmp_name'], $backup_dir . $filename);
        header("Location: backup.php?msg=uploaded");
        exit;
    }
}

// Get Backups
$backups = array_diff(scandir($backup_dir), array('.', '..'));
rsort($backups);

$page_title = "Backup & Migration";
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-4 px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">Backup & Data Migration</h2>
                <p class="text-muted small mb-0">Export your database or restore from a previous backup.</p>
            </div>
            <a href="backup.php?action=create" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="fa fa-database me-2"></i> Create New Backup
            </a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <?php 
            $alert_type = ($_GET['msg'] == 'error') ? 'alert-danger' : 'alert-success';
            $icon = ($_GET['msg'] == 'error') ? 'fa-exclamation-circle' : 'fa-check-circle';
            ?>
            <div class="alert <?= $alert_type ?> alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
                <i class="fa <?= $icon ?> me-2"></i>
                <?php 
                switch($_GET['msg']) {
                    case 'created': echo "Backup created successfully!"; break;
                    case 'deleted': echo "Backup deleted successfully!"; break;
                    case 'restored': echo "Database restored successfully!"; break;
                    case 'uploaded': echo "Backup file uploaded successfully!"; break;
                    case 'error': 
                        echo htmlspecialchars($_SESSION['backup_error'] ?? 'An unknown error occurred.'); 
                        unset($_SESSION['backup_error']);
                        break;
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Backup List -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-0">
                        <div class="p-4 border-bottom">
                            <h5 class="mb-0 fw-bold">Recent Backups</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4 py-3">Filename</th>
                                        <th class="py-3">Date</th>
                                        <th class="py-3">Size</th>
                                        <th class="text-end pe-4 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($backups)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <i class="fa fa-history fa-3x mb-3 opacity-25"></i>
                                                <p>No backups found.</p>
                                            </td>
                                        </tr>
                                    <?php else: foreach ($backups as $file): 
                                        $filePath = $backup_dir . $file;
                                        $size = round(filesize($filePath) / 1024, 2) . ' KB';
                                        $date = date('d M Y, H:i', filemtime($filePath));
                                    ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa fa-file-code text-primary me-3 opacity-50" style="font-size: 1.2rem;"></i>
                                                    <span class="small fw-semibold"><?= htmlspecialchars($file) ?></span>
                                                </div>
                                            </td>
                                            <td class="small text-muted"><?= $date ?></td>
                                            <td class="small text-muted"><?= $size ?></td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group">
                                                    <a href="backup.php?action=download&file=<?= urlencode($file) ?>" class="btn btn-sm btn-light border rounded-pill px-3 me-2" title="Download">
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                    <a href="backup.php?action=restore&file=<?= urlencode($file) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 me-2" onclick="return confirm('WARNING: This will overwrite your current database. Proceed?')" title="Restore">
                                                        <i class="fa fa-sync-alt"></i> Restore
                                                    </a>
                                                    <a href="backup.php?action=delete&file=<?= urlencode($file) ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Delete this backup file?')" title="Delete">
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

            <!-- Upload Backup -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Upload External Backup</h5>
                        <p class="text-muted small mb-4">You can upload a <code>.sql</code> backup file from another instance to migrate data.</p>
                        
                        <form action="backup.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-uppercase opacity-50">Select SQL File</label>
                                <input type="file" name="backup_file" class="form-control rounded-3" accept=".sql" required>
                            </div>
                            <button type="submit" name="upload_backup" class="btn btn-dark w-100 rounded-pill py-2 fw-bold">
                                <i class="fa fa-upload me-2"></i> Upload File
                            </button>
                        </form>

                        <div class="mt-5 p-3 bg-light rounded-4 border border-warning border-opacity-25">
                            <div class="d-flex gap-3">
                                <i class="fa fa-exclamation-triangle text-warning"></i>
                                <div class="small">
                                    <strong class="d-block mb-1">Migration Tip</strong>
                                    To migrate to a new server, download a backup from here, install the CMS on the new server, and upload the file using this form.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
