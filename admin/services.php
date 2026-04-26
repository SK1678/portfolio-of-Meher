<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
require '../db.php';

// Handle Deletions
if (isset($_GET['delete_service'])) {
    $id = (int)$_GET['delete_service'];
    $conn->query("DELETE FROM services WHERE id=$id");
    header("Location: services.php?status=deleted&tab=services");
    exit;
}
if (isset($_GET['delete_counter'])) {
    $id = (int)$_GET['delete_counter'];
    $conn->query("DELETE FROM counters WHERE id=$id");
    header("Location: services.php?status=deleted&tab=counters");
    exit;
}

// Fetch Data
$services = $conn->query("SELECT * FROM services ORDER BY sort_order ASC, id DESC");
$counters = $conn->query("SELECT * FROM counters ORDER BY sort_order ASC, id DESC");

$page_title = "Manage Services & Counters";
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h4 class="fw-bold mb-0">Services & Achievements</h4>
                <p class="text-muted small">Manage your professional services and animated counters.</p>
            </div>
            <div class="col-auto">
                <button type="button" onclick="openCounterModal()" class="btn btn-outline-primary rounded-pill px-4 me-2">
                    <i class="fa fa-plus-circle me-1"></i> Add Counter
                </button>
                <button type="button" onclick="openServiceModal()" class="btn btn-primary rounded-pill px-4">
                    <i class="fa fa-plus-circle me-1"></i> Add Service
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs border-0 px-4 pt-3" id="serviceTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold border-0 pb-3" id="services-tab" data-bs-toggle="tab" data-bs-target="#services-pane" type="button"><i class="fa fa-concierge-bell me-2"></i> Our Services</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold border-0 pb-3" id="counters-tab" data-bs-toggle="tab" data-bs-target="#counters-pane" type="button"><i class="fa fa-tachometer-alt me-2"></i> Animated Counters</button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content">
                    
                    <!-- Services Management -->
                    <div class="tab-pane fade show active" id="services-pane">
                        <div class="row g-4">
                            <?php if ($services->num_rows > 0): ?>
                                <?php while($s = $services->fetch_assoc()): ?>
                                    <div class="col-md-6 col-xl-4">
                                        <div class="card h-100 border rounded-4 overflow-hidden service-admin-card">
                                            <div class="card-body p-4">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div class="icon-box bg-light text-primary d-flex align-items-center justify-content-center rounded-3" style="width:45px; height:45px;">
                                                        <i class="fa <?= $s['icon'] ?> fa-lg"></i>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick='openServiceModal(<?= json_encode($s) ?>)'><i class="fa fa-edit me-2 text-primary"></i> Edit</a></li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><a class="dropdown-item text-danger" href="services.php?delete_service=<?= $s['id'] ?>" onclick="return confirm('Delete this service?')"><i class="fa fa-trash me-2"></i> Delete</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <h6 class="fw-bold mb-2"><?= htmlspecialchars($s['title']) ?></h6>
                                                <p class="text-muted small mb-0 lh-sm"><?= htmlspecialchars(substr($s['description'], 0, 100)) ?>...</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-5">
                                    <div class="mb-3 text-muted opacity-25">
                                        <i class="fa fa-folder-open fa-4x"></i>
                                    </div>
                                    <p class="text-muted">No services added yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Counters Management -->
                    <div class="tab-pane fade" id="counters-pane">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Icon</th>
                                        <th>Counter Title</th>
                                        <th>Target Value</th>
                                        <th class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($counters->num_rows > 0): ?>
                                        <?php while($c = $counters->fetch_assoc()): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <div class="bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center rounded" style="width:35px; height:35px;">
                                                        <i class="fa <?= $c['icon'] ?>"></i>
                                                    </div>
                                                </td>
                                                <td><span class="fw-bold"><?= htmlspecialchars($c['title']) ?></span></td>
                                                <td><code class="text-primary fw-bold"><?= htmlspecialchars($c['value']) ?></code></td>
                                                <td class="text-end pe-3">
                                                    <button onclick='openCounterModal(<?= json_encode($c) ?>)' class="btn btn-sm btn-link text-primary"><i class="fa fa-edit"></i></button>
                                                    <a href="services.php?delete_counter=<?= $c['id'] ?>" class="btn btn-sm btn-link text-danger" onclick="return confirm('Delete counter?')"><i class="fa fa-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">No counters defined.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Service Modal -->
<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-bottom px-4">
                <h5 class="modal-title fw-bold" id="serviceModalTitle">Add New Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="save_service" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="service_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Service Title</label>
                        <input type="text" name="title" id="service_title" class="form-control" required placeholder="e.g. Interior Design">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Icon (FontAwesome class)</label>
                        <input type="text" name="icon" id="service_icon" class="form-control" placeholder="fa-paint-brush">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" id="service_desc" class="form-control" rows="3" required></textarea>
                    </div>

                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light" style="border-bottom-left-radius:20px; border-bottom-right-radius:20px;">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Counter Modal -->
<div class="modal fade" id="counterModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-bottom px-4">
                <h5 class="modal-title fw-bold" id="counterModalTitle">Add Counter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="save_counter" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="counter_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Counter Title</label>
                        <input type="text" name="title" id="counter_title" class="form-control" required placeholder="e.g. Happy Clients">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Target Value</label>
                            <input type="text" name="value" id="counter_value" class="form-control" required placeholder="e.g. 500+">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Icon Class</label>
                            <input type="text" name="icon" id="counter_icon" class="form-control" placeholder="fa-users">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light" style="border-bottom-left-radius:20px; border-bottom-right-radius:20px;">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Counter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .service-admin-card { transition: all 0.3s; }
    .service-admin-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    .nav-tabs .nav-link { color: #6c757d; transition: all 0.3s; }
    .nav-tabs .nav-link.active { color: #8e44ad; border-bottom: 3px solid #8e44ad !important; background: transparent; }
</style>

<script>
function openServiceModal(data = null) {
    const modal = new bootstrap.Modal(document.getElementById('serviceModal'));
    if (data) {
        document.getElementById('serviceModalTitle').innerText = 'Edit Service';
        document.getElementById('service_id').value = data.id;
        document.getElementById('service_title').value = data.title;
        document.getElementById('service_icon').value = data.icon;
        document.getElementById('service_desc').value = data.description;
    } else {
        document.getElementById('serviceModalTitle').innerText = 'Add New Service';
        document.getElementById('service_id').value = '';
        document.getElementById('service_title').value = '';
        document.getElementById('service_icon').value = 'fa-cog';
        document.getElementById('service_desc').value = '';
    }
    modal.show();
}

function openCounterModal(data = null) {
    const modal = new bootstrap.Modal(document.getElementById('counterModal'));
    if (data) {
        document.getElementById('counterModalTitle').innerText = 'Edit Counter';
        document.getElementById('counter_id').value = data.id;
        document.getElementById('counter_title').value = data.title;
        document.getElementById('counter_value').value = data.value;
        document.getElementById('counter_icon').value = data.icon;
    } else {
        document.getElementById('counterModalTitle').innerText = 'Add New Counter';
        document.getElementById('counter_id').value = '';
        document.getElementById('counter_title').value = '';
        document.getElementById('counter_value').value = '';
        document.getElementById('counter_icon').value = 'fa-check';
    }
    modal.show();
}

// Auto-switch to tab if needed
const urlParams = new URLSearchParams(window.location.search);
const activeTab = urlParams.get('tab');
if (activeTab === 'counters') {
    const tab = new bootstrap.Tab(document.getElementById('counters-tab'));
    tab.show();
}
</script>

<?php include 'includes/footer.php'; ?>
