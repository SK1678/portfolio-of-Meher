<?php
require '../db.php';

// Settings are already fetched in ../db.php


// Fetch all users for the Users tab
$users_res = $conn->query("SELECT id, username, full_name, role FROM users ORDER BY id ASC");
$users = [];
while ($r = $users_res->fetch_assoc()) $users[] = $r;

$page_title = "Site Settings";
$show_save = true;
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid px-4 py-4">
        <form id="settings-form" action="save_settings" method="POST" enctype="multipart/form-data">
            <!-- Hidden input to identify this is the global settings page -->
            <input type="hidden" name="is_global_settings" value="1">

            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-white border-bottom p-0">
                    <ul class="nav nav-tabs border-0 px-4 pt-3" id="settingsTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold border-0 pb-3" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button"><i class="fa fa-info-circle me-2"></i> General</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold border-0 pb-3" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button"><i class="fa fa-paint-brush me-2"></i> Appearance</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold border-0 pb-3" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button"><i class="fa fa-search me-2"></i> SEO</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold border-0 pb-3" id="mail-tab" data-bs-toggle="tab" data-bs-target="#mail" type="button"><i class="fa fa-envelope me-2"></i> Mail Configuration</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold border-0 pb-3" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button"><i class="fa fa-users me-2"></i> Users</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="settingsTabsContent">
                        
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Site Title</label>
                                    <input type="text" name="site_title" class="form-control" value="<?= htmlspecialchars($settings['site_title'] ?? 'My Portfolio') ?>" placeholder="e.g. Meher Kanti Sarkar | Portfolio">
                                    <small class="text-muted">Appears in the browser tab.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Favicon (.ico or .png)</label>
                                    <div class="input-group">
                                        <input type="file" name="favicon" class="form-control">
                                        <?php if(!empty($settings['favicon'])): ?>
                                            <span class="input-group-text bg-white"><img src="../<?= $settings['favicon'] ?>" width="20"></span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="existing_favicon" value="<?= $settings['favicon'] ?? '' ?>">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold text-danger">Enable Maintenance Mode</label>
                                    </div>
                                    <small class="text-muted">Hides the site from the public with a custom message.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Timezone</label>
                                    <select name="timezone" class="form-select">
                                        <option value="UTC" <?= ($settings['timezone'] ?? '') == 'UTC' ? 'selected' : '' ?>>UTC</option>
                                        <option value="Asia/Dhaka" <?= ($settings['timezone'] ?? '') == 'Asia/Dhaka' ? 'selected' : '' ?>>Asia/Dhaka</option>
                                        <!-- Add more as needed -->
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Appearance Settings -->
                        <div class="tab-pane fade" id="appearance">
                            <!-- Global Colors Section -->
                            <h6 class="fw-bold mb-3 text-primary"><i class="fa fa-palette me-2"></i> Global Colors</h6>
                            <div class="row g-4 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Primary Theme</label>
                                    <input type="color" name="theme_color" class="form-control form-control-color w-100" value="<?= $settings['theme_color'] ?? '#8e44ad' ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Secondary Theme</label>
                                    <input type="color" name="secondary_color" class="form-control form-control-color w-100" value="<?= $settings['secondary_color'] ?? '#ffcc00' ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Body Text Color</label>
                                    <input type="color" name="body_text_color" class="form-control form-control-color w-100" value="<?= $settings['body_text_color'] ?? '#ffffff' ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Heading Color</label>
                                    <input type="color" name="heading_color" class="form-control form-control-color w-100" value="<?= $settings['heading_color'] ?? '#ffffff' ?>">
                                </div>
                            </div>

                            <!-- Typography Section -->
                            <h6 class="fw-bold mb-3 text-primary"><i class="fa fa-font me-2"></i> Typography</h6>
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Heading Font (H1, H2, H3)</label>
                                    <select name="heading_font" class="form-select">
                                        <?php foreach(['Outfit', 'Inter', 'Poppins', 'Montserrat', 'Playfair Display', 'Roboto'] as $f): ?>
                                            <option value="<?= $f ?>" <?= ($settings['heading_font'] ?? 'Outfit') == $f ? 'selected' : '' ?>><?= $f ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Body Text Font</label>
                                    <select name="body_font" class="form-select">
                                        <?php foreach(['Outfit', 'Inter', 'Poppins', 'Montserrat', 'Open Sans', 'Roboto'] as $f): ?>
                                            <option value="<?= $f ?>" <?= ($settings['body_font'] ?? 'Outfit') == $f ? 'selected' : '' ?>><?= $f ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Component Styling Section -->
                            <h6 class="fw-bold mb-3 text-primary"><i class="fa fa-square me-2"></i> Component Styling</h6>
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Button Background</label>
                                    <input type="color" name="btn_bg_color" class="form-control form-control-color w-100" value="<?= $settings['btn_bg_color'] ?? '#8e44ad' ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Button Text Color</label>
                                    <input type="color" name="btn_text_color" class="form-control form-control-color w-100" value="<?= $settings['btn_text_color'] ?? '#ffffff' ?>">
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" name="dark_mode" value="1" <?= ($settings['dark_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold">Force Dark Theme</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Post & Blog Section (Moved) -->
                            <h6 class="fw-bold mb-3 text-primary"><i class="fa fa-file-alt me-2"></i> Post & Blog Styles</h6>
                            <div class="row g-4 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label">Post Hero Background</label>
                                    <input type="color" name="post_hero_bg" class="form-control form-control-color w-100" value="<?= htmlspecialchars($settings['post_hero_bg'] ?? '#333333') ?>">
                                    <small class="text-muted">Global header background for single post pages.</small>
                                </div>
                            </div>
                        </div>

                        <!-- SEO Settings -->
                        <div class="tab-pane fade" id="seo">
                            <div class="row g-4">
                                <div class="col-12">
                                    <label class="form-label">Meta Description</label>
                                    <textarea name="meta_description" class="form-control" rows="3"><?= htmlspecialchars($settings['meta_description'] ?? '') ?></textarea>
                                    <small class="text-muted">Brief summary of your site for search engines.</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Meta Keywords</label>
                                    <input type="text" name="meta_keywords" class="form-control" value="<?= htmlspecialchars($settings['meta_keywords'] ?? '') ?>">
                                    <small class="text-muted">Comma separated keywords (e.g. Portfolio, Developer, Designer).</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Google Analytics ID</label>
                                    <input type="text" name="google_analytics" class="form-control" value="<?= htmlspecialchars($settings['google_analytics'] ?? '') ?>" placeholder="UA-XXXXX-Y or G-XXXXXXX">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" name="index_site" value="1" <?= ($settings['index_site'] ?? '1') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold">Allow Search Engines to Index</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mail Configuration Settings -->
                        <div class="tab-pane fade" id="mail">
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fw-bold mb-0 text-primary"><i class="fa fa-server me-2"></i> SMTP Server Settings</h6>
                                        <button type="button" id="btn-test-mail" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="fa fa-paper-plane me-1"></i> Test Connection
                                        </button>
                                    </div>
                                    <p class="text-muted small mb-4">Configure your outgoing mail server for notifications and password resets.</p>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" placeholder="e.g. smtp.gmail.com">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">SMTP Port</label>
                                    <input type="text" name="smtp_port" class="form-control" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>" placeholder="587 or 465">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Encryption</label>
                                    <select name="smtp_enc" class="form-select">
                                        <option value="tls" <?= ($settings['smtp_enc'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS</option>
                                        <option value="ssl" <?= ($settings['smtp_enc'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                        <option value="none" <?= ($settings['smtp_enc'] ?? '') == 'none' ? 'selected' : '' ?>>None</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">SMTP Username</label>
                                    <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>" placeholder="Email or Username">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">SMTP Password</label>
                                    <input type="password" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>" placeholder="Server password">
                                </div>

                                <div class="col-md-6 mt-5 border-end pe-4">
                                    <h6 class="fw-bold mb-3 text-primary"><i class="fa fa-paper-plane me-2"></i> Sender Information</h6>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">From Email</label>
                                        <input type="email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars($settings['smtp_from_email'] ?? '') ?>" placeholder="noreply@yourdomain.com">
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label small fw-bold">From Name</label>
                                        <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($settings['smtp_from_name'] ?? 'Portfolio CMS') ?>" placeholder="e.g. Portfolio System">
                                    </div>
                                </div>

                                <div class="col-md-6 mt-5 ps-4">
                                    <h6 class="fw-bold mb-3 text-primary"><i class="fa fa-bell me-2"></i> Notification Settings</h6>
                                    <div class="mb-0">
                                        <label class="form-label small fw-bold">Admin Recipient Email</label>
                                        <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>" placeholder="admin@yourdomain.com">
                                        <small class="text-muted">This email will receive site status and notifications.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="users">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="fw-bold mb-0 text-primary"><i class="fa fa-users-cog me-2"></i> System Administrators</h6>
                                <button type="button" onclick="openUserModal()" class="btn btn-sm btn-primary rounded-pill px-3">
                                    <i class="fa fa-user-plus me-1"></i> Add New User
                                </button>
                            </div>

                            <div class="table-responsive border rounded overflow-hidden">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">User Profile</th>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th class="text-end pe-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary text-white d-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px;font-size:0.75rem;">
                                                        <?= substr($user['full_name'], 0, 1) ?>
                                                    </div>
                                                    <div class="ms-2 lh-1">
                                                        <span class="fw-bold d-block small"><?= htmlspecialchars($user['full_name']) ?></span>
                                                        <?php if($user['id'] == $_SESSION['user_id']): ?>
                                                            <span class="text-primary fw-bold" style="font-size:0.6rem; letter-spacing: 0.5px;">(CURRENTLY LOGGED IN)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><code class="small text-primary"><?= htmlspecialchars($user['username']) ?></code></td>
                                            <td>
                                                <span class="badge bg-light text-dark border small" style="font-size:0.7rem;">
                                                    <?= strtoupper($user['role']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <button type="button" 
                                                        onclick="openUserModal(<?= $user['id'] ?>, '<?= addslashes($user['full_name']) ?>', '<?= addslashes($user['username']) ?>', '<?= $user['role'] ?>')"
                                                        class="btn btn-sm btn-link text-primary p-0 me-2" title="Edit User">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="users.php?delete=<?= $user['id'] ?>&tab=users" class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('Are you sure you want to remove this user?')" title="Delete User">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
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
        </form>
    </div>
</div>

<!-- User Management Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-bottom px-4">
                <h5 class="modal-title fw-bold" id="userModalTitle">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="save_user" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="user_id_field">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="full_name" id="user_name_field" class="form-control" required placeholder="e.g. John Doe">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username</label>
                        <input type="text" name="username" id="user_username_field" class="form-control" required placeholder="Login handle">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Role</label>
                        <select name="role" id="user_role_field" class="form-select">
                            <option value="admin">Administrator</option>
                            <option value="editor">Editor</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold" id="pass_label">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="user_password_field" class="form-control" placeholder="Enter strong password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassField()">
                                <i class="fa fa-eye" id="pass_toggle_icon"></i>
                            </button>
                        </div>
                        <small class="text-muted mt-1 d-block" id="pass_note">Required for new users.</small>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light" style="border-bottom-left-radius:20px; border-bottom-right-radius:20px;">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .nav-tabs .nav-link { color: var(--text-gray); transition: all 0.3s; }
    .nav-tabs .nav-link:hover { color: var(--primary-purple); }
    .nav-tabs .nav-link.active { color: var(--primary-purple); border-bottom: 3px solid var(--primary-purple) !important; background: transparent; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openUserModal(id = 0, name = '', username = '', role = 'admin') {
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    const title = document.getElementById('userModalTitle');
    const idField = document.getElementById('user_id_field');
    const nameField = document.getElementById('user_name_field');
    const usernameField = document.getElementById('user_username_field');
    const roleField = document.getElementById('user_role_field');
    const passField = document.getElementById('user_password_field');
    const passNote = document.getElementById('pass_note');
    const passLabel = document.getElementById('pass_label');

    idField.value = id;
    nameField.value = name;
    usernameField.value = username;
    roleField.value = role;
    passField.value = '';

    if (id > 0) {
        title.textContent = 'Edit Admin User';
        passLabel.textContent = 'New Password';
        passNote.textContent = 'Leave blank to keep current password.';
        passField.required = false;
    } else {
        title.textContent = 'Create New Admin';
        passLabel.textContent = 'Password';
        passNote.textContent = 'Minimum 6 characters required.';
        passField.required = true;
    }

    modal.show();
}

function togglePassField() {
    const field = document.getElementById('user_password_field');
    const icon = document.getElementById('pass_toggle_icon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// SMTP Test Feature
document.getElementById('btn-test-mail')?.addEventListener('click', function() {
    const btn = this;
    const originalHtml = btn.innerHTML;
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Testing...';
    
    // Get current form data
    const formData = new FormData(document.getElementById('settings-form'));
    
    fetch('test_mail.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Success! SMTP Connection established.\n\n' + data.mail_attempt + '\nRecipient: ' + data.recipient);
        } else {
            alert('❌ SMTP Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Error: Could not connect to the test script.');
        console.error(error);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});

// Auto-switch to tab if returning from action
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab) {
        const tabEl = document.querySelector(`button[data-bs-target="#${activeTab}"]`);
        if (tabEl) {
            const tab = new bootstrap.Tab(tabEl);
            tab.show();
        }
    }
    
    if (window.location.search.includes('tab=users') || window.location.search.includes('status=deleted')) {
        const userTabBtn = document.getElementById('users-tab');
        if (userTabBtn) userTabBtn.click();
    }
});
</script>
</body>
</html>
