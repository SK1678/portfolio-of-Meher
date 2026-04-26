<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require '../db.php';

// Fetch current settings
$res = $conn->query("SELECT * FROM homepage_settings");
$settings = [];
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$page_title = "Basic Information Setup";
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php
    $show_save = true;
    include 'includes/header.php';
    ?>

    <div class="container-fluid px-4 pt-1 pb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fw-bold">Basic Information Setup</h2>
            <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <div class="alert alert-success py-2 px-4 mb-0">Settings updated successfully!</div>
            <?php endif; ?>
        </div>

        <form action="save_settings" method="POST" class="settings-form" enctype="multipart/form-data"
            id="settings-form">
            <input type="hidden" name="is_basic_settings" value="1">

            <div class="row g-4">
                <!-- ... sections ... -->
                <!-- Primary Identity Section -->
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-primary mb-3 border-bottom pb-2">Primary Identity &
                                Documents</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">First Name</label>
                                    <input type="text" name="first_name" class="form-control"
                                        value="<?= htmlspecialchars($settings['first_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Last Name</label>
                                    <input type="text" name="last_name" class="form-control"
                                        value="<?= htmlspecialchars($settings['last_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-muted">Global Name (Display)</label>
                                    <input type="text" name="name" class="form-control"
                                        value="<?= htmlspecialchars($settings['name'] ?? '') ?>"
                                        placeholder="Full Display Name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Date of Birth</label>
                                    <input type="date" name="dob" class="form-control"
                                        value="<?= htmlspecialchars($settings['dob'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?= ($settings['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($settings['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= ($settings['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nationality</label>
                                    <input type="text" name="nationality" class="form-control"
                                        value="<?= htmlspecialchars($settings['nationality'] ?? 'Bangladeshi') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">CV Download Link / File</label>
                                    <div class="input-group">
                                        <input type="text" name="cv_link" class="form-control"
                                            value="<?= htmlspecialchars($settings['cv_link'] ?? '') ?>"
                                            placeholder="URL or uploaded file">
                                        <input type="file" name="cv_file" class="d-none" id="cv_file_input">
                                        <button class="btn btn-outline-secondary" type="button"
                                            onclick="document.getElementById('cv_file_input').click()">
                                            <i class="fa fa-paperclip"></i>
                                        </button>
                                    </div>
                                    <?php if (!empty($settings['cv_link']) && strpos($settings['cv_link'], 'assets/uploads/') !== false): ?>
                                        <small class="text-success"><i class="fa fa-check-circle me-1"></i> File
                                            Uploaded</small>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Family Background -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-primary mb-3 border-bottom pb-2">Family Background</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Father's Name</label>
                                    <input type="text" name="father_name" class="form-control"
                                        value="<?= htmlspecialchars($settings['father_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Mother's Name</label>
                                    <input type="text" name="mother_name" class="form-control"
                                        value="<?= htmlspecialchars($settings['mother_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Religion</label>
                                    <input type="text" name="religion" class="form-control"
                                        value="<?= htmlspecialchars($settings['religion'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">NID No</label>
                                    <input type="text" name="nid_no" class="form-control"
                                        value="<?= htmlspecialchars($settings['nid_no'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Passport No</label>
                                    <input type="text" name="passport_no" class="form-control"
                                        value="<?= htmlspecialchars($settings['passport_no'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">DL No (Driving License)</label>
                                    <input type="text" name="dl_no" class="form-control"
                                        value="<?= htmlspecialchars($settings['dl_no'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & Communication -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-primary mb-3 border-bottom pb-2">Contact & Communication
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Primary Email</label>
                                    <input type="email" name="contact_email" class="form-control"
                                        value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Alternative Email</label>
                                    <input type="email" name="alt_email" class="form-control"
                                        value="<?= htmlspecialchars($settings['alt_email'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Primary Phone No</label>
                                    <input type="text" name="contact_phone" class="form-control"
                                        value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Secondary Phone No</label>
                                    <input type="text" name="secondary_phone" class="form-control"
                                        value="<?= htmlspecialchars($settings['secondary_phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-primary mb-3 border-bottom pb-2">Residential Addresses
                            </h5>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Present Address</label>
                                <textarea name="present_address" class="form-control"
                                    rows="2"><?= htmlspecialchars($settings['present_address'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-bold">Permanent Address</label>
                                <textarea name="permanent_address" class="form-control"
                                    rows="2"><?= htmlspecialchars($settings['permanent_address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Social Presence -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-primary mb-3 border-bottom pb-2">Social & Professional
                                Links</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold"><i class="fab fa-github me-1"></i> GitHub
                                        URL</label>
                                    <input type="text" name="social_github" class="form-control"
                                        value="<?= htmlspecialchars($settings['social_github'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold"><i class="fab fa-linkedin me-1"></i>
                                        LinkedIn URL</label>
                                    <input type="text" name="social_linkedin" class="form-control"
                                        value="<?= htmlspecialchars($settings['social_linkedin'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold"><i class="fab fa-twitter me-1"></i> Twitter
                                        / X</label>
                                    <input type="text" name="social_twitter" class="form-control"
                                        value="<?= htmlspecialchars($settings['social_twitter'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold"><i class="fab fa-facebook me-1"></i>
                                        Facebook</label>
                                    <input type="text" name="social_facebook" class="form-control"
                                        value="<?= htmlspecialchars($settings['social_facebook'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold"><i class="fab fa-whatsapp me-1"></i>
                                        WhatsApp</label>
                                    <input type="text" name="social_whatsapp" class="form-control"
                                        value="<?= htmlspecialchars($settings['social_whatsapp'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold"><i class="fa-solid fa-at me-1"></i>
                                        Threads</label>
                                    <input type="text" name="social_threads" class="form-control"
                                        value="<?= htmlspecialchars($settings['social_threads'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold"><i class="fab fa-tiktok me-1"></i>
                                        TikTok</label>
                                    <input type="text" name="social_tiktok" class="form-control"
                                        value="<?= htmlspecialchars($settings['social_tiktok'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold"><i
                                            class="fab fa-facebook-messenger me-1"></i>
                                        Messenger</label>
                                    <input type="text" name="social_messenger" class="form-control"
                                        value="<?= htmlspecialchars($settings['social_messenger'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>