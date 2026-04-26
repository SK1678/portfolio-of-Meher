<?php
require '../db.php';

// Fetch all settings for personal info
$res = $conn->query("SELECT * FROM homepage_settings");
$settings = [];
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Fetch Resume Items
$experience = $conn->query("SELECT * FROM about_items WHERE type='experience' ORDER BY sort_order ASC, id DESC");
$education = $conn->query("SELECT * FROM about_items WHERE type='education' ORDER BY sort_order ASC, id DESC");
$certifications = $conn->query("SELECT * FROM about_items WHERE type='certification' ORDER BY sort_order ASC, id DESC");
$skills = $conn->query("SELECT * FROM skills ORDER BY sort_order ASC, id DESC");

$bio_idx = 0; $exp_idx = 0; $edu_idx = 0; $skill_idx = 0; $cert_idx = 0;

$page_title = "About Page Settings";
$show_save = true;
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid px-4 py-4">
        <form id="settings-form" action="save_about" method="POST" enctype="multipart/form-data">
            
            <div class="card border-0 shadow-sm overflow-hidden mb-4">
                <div class="card-header bg-white border-bottom p-0">
                    <input type="hidden" name="active_tab" id="active_tab_input" value="personal">
                    <ul class="nav nav-tabs border-0 px-4 pt-3" id="aboutTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold border-0 pb-3" data-bs-toggle="tab" data-bs-target="#personal" type="button" onclick="document.getElementById('active_tab_input').value='personal'"><i class="fa fa-user me-2"></i> Personal Info</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold border-0 pb-3" data-bs-toggle="tab" data-bs-target="#experience" type="button" onclick="document.getElementById('active_tab_input').value='experience'"><i class="fa fa-briefcase me-2"></i> Experience</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold border-0 pb-3" data-bs-toggle="tab" data-bs-target="#education" type="button" onclick="document.getElementById('active_tab_input').value='education'"><i class="fa fa-graduation-cap me-2"></i> Education</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold border-0 pb-3" data-bs-toggle="tab" data-bs-target="#skills" type="button" onclick="document.getElementById('active_tab_input').value='skills'"><i class="fa fa-chart-line me-2"></i> Skills</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold border-0 pb-3" data-bs-toggle="tab" data-bs-target="#certs" type="button" onclick="document.getElementById('active_tab_input').value='certs'"><i class="fa fa-award me-2"></i> Awards & Certs</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content">
                        
                        <!-- Personal Info Tab -->
                        <div class="tab-pane fade show active" id="personal">
                            <div class="row g-4">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">About Profile Image</label>
                                    <div class="mb-4">
                                        <img src="../<?= $settings['about_image'] ?? 'assets/uploads/default_bg.jpg' ?>" class="img-thumbnail mb-2" style="max-height: 200px;">
                                        <input type="file" name="about_image" class="form-control">
                                        <input type="hidden" name="existing_about_image" value="<?= $settings['about_image'] ?? '' ?>">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Career Objective</label>
                                        <textarea name="career_objective" class="form-control shadow-sm" rows="5" placeholder="Enter your career objective or professional summary here..."><?= htmlspecialchars($settings['career_objective'] ?? '') ?></textarea>
                                        <small class="text-muted d-block mt-1">This will be displayed as a dedicated block above your Experience/Education section.</small>
                                    </div>
                                    <div class="p-3 bg-light rounded border border-info-subtle">
                                        <h6 class="fw-bold text-info-emphasis mb-2 small"><i class="fa fa-info-circle me-1"></i> Central Sync Info</h6>
                                        <p class="small text-muted mb-0">Your **CV File** and **Identity Data** are now managed in the <a href="settings_basic.php" class="fw-bold">Basic Info Setup</a> to ensure site-wide consistency.</p>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="form-label fw-bold mb-0">Biometric Details</label>
                                        <div class="btn-group shadow-sm rounded-pill">
                                            <button type="button" class="btn btn-outline-info btn-sm fw-bold" onclick="syncFromBasic()">
                                                <i class="fa fa-sync-alt me-1"></i> Sync from Basic
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-sm fw-bold" onclick="addBioField()">
                                                <i class="fa fa-plus me-1"></i> Add Field
                                            </button>
                                        </div>
                                    </div>
                                    <div id="bio-container">
                                        <?php 
                                        $bio_data = json_decode($settings['about_personal_info'] ?? '[]', true);
                                        // Fallback to defaults if empty
                                        if(empty($bio_data)) {
                                            $bio_data = [
                                                ['label' => 'First Name', 'value' => $settings['first_name'] ?? '', 'type' => 'text'],
                                                ['label' => 'Last Name', 'value' => $settings['last_name'] ?? '', 'type' => 'text'],
                                                ['label' => 'Nationality', 'value' => $settings['nationality'] ?? '', 'type' => 'text'],
                                                ['label' => 'Phone', 'value' => $settings['contact_phone'] ?? '', 'type' => 'text'],
                                                ['label' => 'Email', 'value' => $settings['contact_email'] ?? '', 'type' => 'text']
                                            ];
                                        }
                                        foreach($bio_data as $key => $item): 
                                            // Handle legacy format (Label => Value) vs New Format (List of Objects)
                                            if (is_array($item)) {
                                                $lbl = $item['label'] ?? '';
                                                $val = $item['value'] ?? '';
                                                $typ = $item['type'] ?? 'text';
                                            } else {
                                                $lbl = is_numeric($key) ? '' : $key;
                                                $val = $item;
                                                $typ = 'text';
                                            }
                                        ?>
                                            <div class="bio-row mb-2">
                                                <div class="input-group input-group-sm shadow-sm">
                                                    <span class="input-group-text bg-light move-btns">
                                                        <a href="javascript:void(0)" class="text-secondary me-1" onclick="moveRow(this, 'up')"><i class="fa fa-chevron-up" style="font-size: 0.6rem;"></i></a>
                                                        <a href="javascript:void(0)" class="text-secondary" onclick="moveRow(this, 'down')"><i class="fa fa-chevron-down" style="font-size: 0.6rem;"></i></a>
                                                    </span>
                                                    <input type="text" name="bio_label[]" class="form-control" placeholder="Label" value="<?= htmlspecialchars($lbl) ?>">
                                                    <input type="text" name="bio_value[]" class="form-control w-50" placeholder="Value" value="<?= htmlspecialchars($val) ?>">
                                                    <select name="bio_type[]" class="form-select" style="max-width: 100px;">
                                                        <option value="text" <?= $typ == 'text' ? 'selected' : '' ?>>Text</option>
                                                        <option value="date" <?= $typ == 'date' ? 'selected' : '' ?>>Date</option>
                                                        <option value="link" <?= $typ == 'link' ? 'selected' : '' ?>>Link</option>
                                                    </select>
                                                    <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.parentElement.remove()"><i class="fa fa-times"></i></button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Experience Tab -->
                        <div class="tab-pane fade" id="experience">
                            <div id="exp-container">
                                <?php while($item = $experience->fetch_assoc()): ?>
                                    <div class="btn-row p-3 mb-3 border rounded shadow-sm position-relative">
                                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="small fw-bold">Period</label>
                                                <input type="text" name="exp[period][]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['period']) ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="small fw-bold">Job Title</label>
                                                <input type="text" name="exp[title][]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['title']) ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="small fw-bold">Company</label>
                                                <input type="text" name="exp[org][]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['organization']) ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="small fw-bold">Description</label>
                                                <textarea name="exp[desc][]" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($item['description']) ?></textarea>
                                            </div>
                                            <!-- Multiple Attachments -->
                                            <div class="col-12 mt-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label class="small fw-bold text-muted"><i class="fa fa-link me-1"></i> Attachments & Links</label>
                                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="addAttachment(this, 'exp', <?= $exp_idx ?>)">
                                                        <i class="fa fa-plus-circle me-1"></i> Add Link/File
                                                    </button>
                                                </div>
                                                <div class="attachments-list" id="exp-attachments-<?= $exp_idx ?>">
                                                    <?php 
                                                    $item_id = $item['id'];
                                                    $atts = $conn->query("SELECT * FROM item_attachments WHERE item_id=$item_id");
                                                    $att_idx = 0;
                                                    while($att = $atts->fetch_assoc()):
                                                    ?>
                                                        <div class="attachment-row input-group input-group-sm mb-2 shadow-none border rounded p-1">
                                                            <input type="text" name="exp[attachments][<?= $exp_idx ?>][label][]" class="form-control border-0" placeholder="Label" value="<?= htmlspecialchars($att['label']) ?>">
                                                            <div class="input-group input-group-sm" style="flex: 2;">
                                                                <input type="text" name="exp[attachments][<?= $exp_idx ?>][url][]" class="form-control border-0" placeholder="URL or selected file" value="<?= htmlspecialchars($att['url']) ?>">
                                                                <input type="file" name="exp_files[<?= $exp_idx ?>][]" class="d-none" onchange="updateFileName(this)">
                                                                <button type="button" class="btn btn-light border-0 text-muted px-2" onclick="this.previousElementSibling.click()" title="Upload File"><i class="fa fa-upload"></i></button>
                                                            </div>
                                                            <div class="input-group-text border-0 bg-transparent">
                                                                <div class="form-check form-switch mb-0">
                                                                    <input type="hidden" name="exp[attachments][<?= $exp_idx ?>][is_protected][<?= $att_idx ?>]" value="0">
                                                                    <input class="form-check-input" type="checkbox" name="exp[attachments][<?= $exp_idx ?>][is_protected][<?= $att_idx ?>]" value="1" <?= $att['is_protected'] ? 'checked' : '' ?> onchange="togglePass(this)">
                                                                    <label class="small mb-0 ms-1"><i class="fa fa-lock" style="font-size: 0.7rem;"></i></label>
                                                                </div>
                                                            </div>
                                                            <input type="password" name="exp[attachments][<?= $exp_idx ?>][password][]" class="form-control border-0 <?= $att['is_protected'] ? '' : 'd-none' ?>" placeholder="Pass" value="<?= htmlspecialchars($att['password'] ?? '') ?>">
                                                            <button type="button" class="btn btn-link text-danger p-0 px-2" onclick="this.closest('.attachment-row').remove()"><i class="fa fa-times"></i></button>
                                                        </div>
                                                    <?php $att_idx++; endwhile; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php $exp_idx++; endwhile; ?>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill mt-2" onclick="addItem('exp')">
                                <i class="fa fa-plus me-1"></i> Add Experience
                            </button>
                        </div>

                        <!-- Education Tab -->
                        <div class="tab-pane fade" id="education">
                            <div id="edu-container">
                                <?php while($item = $education->fetch_assoc()): ?>
                                    <div class="btn-row p-3 mb-3 border rounded shadow-sm position-relative">
                                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="small fw-bold">Passing Year</label>
                                                <input type="text" name="edu[period][]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['period']) ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="small fw-bold">Duration</label>
                                                <input type="text" name="edu[duration][]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['duration'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="small fw-bold">Degree</label>
                                                <input type="text" name="edu[title][]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['title']) ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="small fw-bold">Institution</label>
                                                <input type="text" name="edu[org][]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['organization']) ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="small fw-bold">Description</label>
                                                <textarea name="edu[desc][]" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($item['description']) ?></textarea>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label class="small fw-bold text-muted"><i class="fa fa-link me-1"></i> Attachments & Links</label>
                                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="addAttachment(this, 'edu', <?= $edu_idx ?>)">
                                                        <i class="fa fa-plus-circle me-1"></i> Add Link/File
                                                    </button>
                                                </div>
                                                <div class="attachments-list" id="edu-attachments-<?= $edu_idx ?>">
                                                    <?php 
                                                    $item_id = $item['id'];
                                                    $atts = $conn->query("SELECT * FROM item_attachments WHERE item_id=$item_id");
                                                    while($att = $atts->fetch_assoc()):
                                                    ?>
                                                        <div class="attachment-row input-group input-group-sm mb-2 shadow-none border rounded p-1">
                                                            <input type="text" name="edu[attachments][<?= $edu_idx ?>][label][]" class="form-control border-0" placeholder="Label" value="<?= htmlspecialchars($att['label']) ?>">
                                                            <div class="input-group input-group-sm" style="flex: 2;">
                                                                <input type="text" name="edu[attachments][<?= $edu_idx ?>][url][]" class="form-control border-0" placeholder="URL or selected file" value="<?= htmlspecialchars($att['url']) ?>">
                                                                <input type="file" name="edu_files[<?= $edu_idx ?>][]" class="d-none" onchange="updateFileName(this)">
                                                                <button type="button" class="btn btn-light border-0 text-muted px-2" onclick="this.previousElementSibling.click()" title="Upload File"><i class="fa fa-upload"></i></button>
                                                            </div>
                                                            <div class="input-group-text border-0 bg-transparent">
                                                                <div class="form-check form-switch mb-0">
                                                                    <input type="hidden" name="edu[attachments][<?= $edu_idx ?>][is_protected][<?= $att_idx ?>]" value="0">
                                                                    <input class="form-check-input" type="checkbox" name="edu[attachments][<?= $edu_idx ?>][is_protected][<?= $att_idx ?>]" value="1" <?= $att['is_protected'] ? 'checked' : '' ?> onchange="togglePass(this)">
                                                                    <label class="small mb-0 ms-1"><i class="fa fa-lock" style="font-size: 0.7rem;"></i></label>
                                                                </div>
                                                            </div>
                                                            <input type="password" name="edu[attachments][<?= $edu_idx ?>][password][]" class="form-control border-0 <?= $att['is_protected'] ? '' : 'd-none' ?>" placeholder="Pass" value="<?= htmlspecialchars($att['password'] ?? '') ?>">
                                                            <button type="button" class="btn btn-link text-danger p-0 px-2" onclick="this.closest('.attachment-row').remove()"><i class="fa fa-times"></i></button>
                                                        </div>
                                                    <?php $att_idx++; endwhile; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php $edu_idx++; endwhile; ?>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill mt-2" onclick="addItem('edu')">
                                <i class="fa fa-plus me-1"></i> Add Education
                            </button>
                        </div>

                        <!-- Skills Tab -->
                        <div class="tab-pane fade" id="skills">
                            <div id="skill-container" class="row g-3">
                                <?php while($item = $skills->fetch_assoc()): ?>
                                    <div class="col-md-6 skill-row-wrapper">
                                        <div class="p-3 border rounded shadow-sm position-relative h-100">
                                            <button type="button" class="remove-btn" onclick="this.closest('.skill-row-wrapper').remove()"><i class="fa fa-times"></i></button>
                                            <div class="row g-2">
                                                <div class="col-8">
                                                    <label class="small fw-bold">Skill Name</label>
                                                    <input type="text" name="skill[name][]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['skill_name']) ?>">
                                                </div>
                                                <div class="col-4">
                                                    <label class="small fw-bold">Percent (%)</label>
                                                    <input type="number" name="skill[percent][]" class="form-control form-control-sm" value="<?= $item['percentage'] ?>" min="0" max="100">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill mt-3" onclick="addItem('skill')">
                                <i class="fa fa-plus me-1"></i> Add Skill
                            </button>
                        </div>

                        <!-- Certifications Tab -->
                        <div class="tab-pane fade" id="certs">
                            <div id="cert-container">
                                <?php 
                                $cert_idx = 0; // Reset just in case
                                while($item = $certifications->fetch_assoc()): 
                                ?>
                                    <div class="btn-row p-3 mb-3 border rounded shadow-sm position-relative">
                                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button>
                                        <div class="row g-3">
                                            <div class="col-md-2">
                                                <label class="small fw-bold">Year</label>
                                                <input type="text" name="cert[period][]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['period']) ?>">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="small fw-bold">Award / Certificate Title</label>
                                                <input type="text" name="cert[title][]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['title']) ?>">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="small fw-bold">Issuing Organization</label>
                                                <input type="text" name="cert[org][]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['organization']) ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="small fw-bold">Description (Optional)</label>
                                                <textarea name="cert[desc][]" class="form-control form-control-sm" rows="1"><?= htmlspecialchars($item['description']) ?></textarea>
                                            </div>
                                            <!-- Multiple Attachments -->
                                            <div class="col-12 mt-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label class="small fw-bold text-muted"><i class="fa fa-certificate me-1"></i> Certificate Files & Proof</label>
                                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="addAttachment(this, 'cert', <?= $cert_idx ?>)">
                                                        <i class="fa fa-plus-circle me-1"></i> Add Certificate
                                                    </button>
                                                </div>
                                                <div class="attachments-list" id="cert-attachments-<?= $cert_idx ?>">
                                                    <?php 
                                                    $item_id = $item['id'];
                                                    $atts = $conn->query("SELECT * FROM item_attachments WHERE item_id=$item_id");
                                                    $att_idx = 0;
                                                    while($att = $atts->fetch_assoc()):
                                                    ?>
                                                        <div class="attachment-row input-group input-group-sm mb-2 shadow-none border rounded p-1">
                                                            <input type="text" name="cert[attachments][<?= $cert_idx ?>][label][]" class="form-control border-0" placeholder="Label" value="<?= htmlspecialchars($att['label']) ?>">
                                                            <div class="input-group input-group-sm" style="flex: 2;">
                                                                <input type="text" name="cert[attachments][<?= $cert_idx ?>][url][]" class="form-control border-0" placeholder="URL or file" value="<?= htmlspecialchars($att['url']) ?>">
                                                                <input type="file" name="cert_files[<?= $cert_idx ?>][]" class="d-none" onchange="updateFileName(this)">
                                                                <button type="button" class="btn btn-light border-0 text-muted px-2" onclick="this.previousElementSibling.click()" title="Upload File"><i class="fa fa-upload"></i></button>
                                                            </div>
                                                            <div class="input-group-text border-0 bg-transparent">
                                                                <div class="form-check form-switch mb-0">
                                                                    <input type="hidden" name="cert[attachments][<?= $cert_idx ?>][is_protected][<?= $att_idx ?>]" value="0">
                                                                    <input class="form-check-input" type="checkbox" name="cert[attachments][<?= $cert_idx ?>][is_protected][<?= $att_idx ?>]" value="1" <?= $att['is_protected'] ? 'checked' : '' ?> onchange="togglePass(this)">
                                                                    <label class="small mb-0 ms-1"><i class="fa fa-lock" style="font-size: 0.7rem;"></i></label>
                                                                </div>
                                                            </div>
                                                            <input type="password" name="cert[attachments][<?= $cert_idx ?>][password][]" class="form-control border-0 <?= $att['is_protected'] ? '' : 'd-none' ?>" placeholder="Pass" value="<?= htmlspecialchars($att['password'] ?? '') ?>">
                                                            <button type="button" class="btn btn-link text-danger p-0 px-2" onclick="this.closest('.attachment-row').remove()"><i class="fa fa-times"></i></button>
                                                        </div>
                                                    <?php $att_idx++; endwhile; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php $cert_idx++; endwhile; ?>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill mt-3" onclick="addItem('cert')">
                                <i class="fa fa-plus me-1"></i> Add Certification / Award
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let eduIdx = <?= $edu_idx ?>;
let expIdx = <?= $exp_idx ?>;
let certIdx = <?= $cert_idx ?>;

function syncFromBasic() {
    if(!confirm("This will clear your current biometric details and re-sync them from the Basic Information Setup. Continue?")) return;
    
    const basicData = {
        "First Name": "<?= addslashes($settings['first_name'] ?? '') ?>",
        "Last Name": "<?= addslashes($settings['last_name'] ?? '') ?>",
        "Date of Birth": "<?= addslashes($settings['dob'] ?? '') ?>",
        "Gender": "<?= addslashes($settings['gender'] ?? '') ?>",
        "Nationality": "<?= addslashes($settings['nationality'] ?? '') ?>",
        "Religion": "<?= addslashes($settings['religion'] ?? '') ?>",
        "NID No": "<?= addslashes($settings['nid_no'] ?? '') ?>",
        "Passport No": "<?= addslashes($settings['passport_no'] ?? '') ?>",
        "DL No": "<?= addslashes($settings['dl_no'] ?? '') ?>",
        "Father's Name": "<?= addslashes($settings['father_name'] ?? '') ?>",
        "Mother's Name": "<?= addslashes($settings['mother_name'] ?? '') ?>",
        "Email": "<?= addslashes($settings['contact_email'] ?? '') ?>",
        "Alt Email": "<?= addslashes($settings['alt_email'] ?? '') ?>",
        "Phone": "<?= addslashes($settings['contact_phone'] ?? '') ?>",
        "Secondary Phone": "<?= addslashes($settings['secondary_phone'] ?? '') ?>",
        "Present Address": "<?= addslashes($settings['present_address'] ?? '') ?>",
        "Permanent Address": "<?= addslashes($settings['permanent_address'] ?? '') ?>"
    };

    const container = document.getElementById('bio-container');
    container.innerHTML = ''; // Clear current

    Object.entries(basicData).forEach(([label, value]) => {
        if(value && value !== "") {
            const div = document.createElement('div');
            div.className = 'bio-row mb-2';
            div.innerHTML = `
                <div class="input-group input-group-sm shadow-sm">
                    <span class="input-group-text bg-light move-btns">
                        <a href="javascript:void(0)" class="text-secondary me-1" onclick="moveRow(this, 'up')"><i class="fa fa-chevron-up" style="font-size: 0.6rem;"></i></a>
                        <a href="javascript:void(0)" class="text-secondary" onclick="moveRow(this, 'down')"><i class="fa fa-chevron-down" style="font-size: 0.6rem;"></i></a>
                    </span>
                    <input type="text" name="bio_label[]" class="form-control" placeholder="Label" value="${label}">
                    <input type="text" name="bio_value[]" class="form-control w-50" placeholder="Value" value="${value}">
                    <select name="bio_type[]" class="form-select" style="max-width: 100px;">
                        <option value="text">Text</option>
                        <option value="date" ${label.includes('Date') ? 'selected' : ''}>Date</option>
                        <option value="link">Link</option>
                    </select>
                    <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.parentElement.remove()"><i class="fa fa-times"></i></button>
                </div>
            `;
            container.appendChild(div);
        }
    });
}

function addBioField() {
    const container = document.getElementById('bio-container');
    const div = document.createElement('div');
    div.className = 'bio-row mb-2';
    div.innerHTML = `
        <div class="input-group input-group-sm shadow-sm">
            <span class="input-group-text bg-light move-btns">
                <a href="javascript:void(0)" class="text-secondary me-1" onclick="moveRow(this, 'up')"><i class="fa fa-chevron-up" style="font-size: 0.6rem;"></i></a>
                <a href="javascript:void(0)" class="text-secondary" onclick="moveRow(this, 'down')"><i class="fa fa-chevron-down" style="font-size: 0.6rem;"></i></a>
            </span>
            <input type="text" name="bio_label[]" class="form-control" placeholder="Label">
            <input type="text" name="bio_value[]" class="form-control w-50" placeholder="Value">
            <select name="bio_type[]" class="form-select" style="max-width: 100px;">
                <option value="text">Text</option>
                <option value="date">Date</option>
                <option value="link">Link</option>
            </select>
            <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.parentElement.remove()"><i class="fa fa-times"></i></button>
        </div>
    `;
    container.appendChild(div);
}

function moveRow(btn, direction) {
    const row = btn.closest('.bio-row');
    row.classList.add('highlight-row');
    
    if (direction === 'up') {
        const prev = row.previousElementSibling;
        if (prev) row.parentNode.insertBefore(row, prev);
    } else {
        const next = row.nextElementSibling;
        if (next) row.parentNode.insertBefore(next, row);
    }
    
    setTimeout(() => {
        row.classList.remove('highlight-row');
    }, 1000);
}

function addItem(type) {
    const container = document.getElementById(`${type}-container`);
    const div = document.createElement('div');
    
    let idx;
    if (type === 'edu') idx = eduIdx++;
    else if (type === 'exp') idx = expIdx++;
    else if (type === 'cert') idx = certIdx++;
    else idx = 0; // Default fallback
    
    if (type === 'skill') {
        div.className = 'col-md-6 skill-row-wrapper';
        div.innerHTML = `
            <div class="p-3 border rounded shadow-sm position-relative h-100">
                <button type="button" class="remove-btn" onclick="this.closest('.skill-row-wrapper').remove()"><i class="fa fa-times"></i></button>
                <div class="row g-2">
                    <div class="col-8">
                        <label class="small fw-bold">Skill Name</label>
                        <input type="text" name="skill[name][]" class="form-control form-control-sm" placeholder="e.g. JavaScript">
                    </div>
                    <div class="col-4">
                        <label class="small fw-bold">Percent (%)</label>
                        <input type="number" name="skill[percent][]" class="form-control form-control-sm" value="80" min="0" max="100">
                    </div>
                </div>
            </div>
        `;
    } else {
        div.className = 'btn-row p-3 mb-3 border rounded shadow-sm position-relative';
        if (type === 'edu') {
            div.innerHTML = `
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="small fw-bold">Passing Year</label>
                        <input type="text" name="edu[period][]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Duration</label>
                        <input type="text" name="edu[duration][]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Degree</label>
                        <input type="text" name="edu[title][]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Institution</label>
                        <input type="text" name="edu[org][]" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold">Description</label>
                        <textarea name="edu[desc][]" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="col-12 mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="small fw-bold text-muted"><i class="fa fa-link me-1"></i> Attachments & Links</label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="addAttachment(this, 'edu', ${idx})">
                                <i class="fa fa-plus-circle me-1"></i> Add Link/File
                            </button>
                        </div>
                        <div class="attachments-list" id="edu-attachments-${idx}"></div>
                    </div>
                </div>
            `;
        } else if (type === 'cert') {
            div.innerHTML = `
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="small fw-bold">Year</label>
                        <input type="text" name="cert[period][]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-5">
                        <label class="small fw-bold">Award / Certificate Title</label>
                        <input type="text" name="cert[title][]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-5">
                        <label class="small fw-bold">Issuing Organization</label>
                        <input type="text" name="cert[org][]" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold">Description (Optional)</label>
                        <textarea name="cert[desc][]" class="form-control form-control-sm" rows="1"></textarea>
                    </div>
                    <div class="col-12 mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="small fw-bold text-muted"><i class="fa fa-certificate me-1"></i> Certificate Files & Proof</label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="addAttachment(this, 'cert', ${idx})">
                                <i class="fa fa-plus-circle me-1"></i> Add Certificate
                            </button>
                        </div>
                        <div class="attachments-list" id="cert-attachments-${idx}"></div>
                    </div>
                </div>
            `;
        } else {
            div.innerHTML = `
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="small fw-bold">Period</label>
                        <input type="text" name="exp[period][]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Job Title</label>
                        <input type="text" name="exp[title][]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Company</label>
                        <input type="text" name="exp[org][]" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold">Description</label>
                        <textarea name="exp[desc][]" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="col-12 mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="small fw-bold text-muted"><i class="fa fa-link me-1"></i> Attachments & Links</label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="addAttachment(this, 'exp', ${idx})">
                                <i class="fa fa-plus-circle me-1"></i> Add Link/File
                            </button>
                        </div>
                        <div class="attachments-list" id="exp-attachments-${idx}"></div>
                    </div>
                </div>
            `;
        }
    }
    container.appendChild(div);
}

function addAttachment(btn, type, idx) {
    const list = document.getElementById(`${type}-attachments-${idx}`);
    const attIdx = list.children.length;
    const row = document.createElement('div');
    row.className = 'attachment-row input-group input-group-sm mb-2 shadow-none border rounded p-1';
    row.innerHTML = `
        <input type="text" name="${type}[attachments][${idx}][label][]" class="form-control border-0" placeholder="Label">
        <div class="input-group input-group-sm" style="flex: 2;">
            <input type="text" name="${type}[attachments][${idx}][url][]" class="form-control border-0" placeholder="URL or selected file">
            <input type="file" name="${type}_files[${idx}][]" class="d-none" onchange="updateFileName(this)">
            <button type="button" class="btn btn-light border-0 text-muted px-2" onclick="this.previousElementSibling.click()" title="Upload File"><i class="fa fa-upload"></i></button>
        </div>
        <div class="input-group-text border-0 bg-transparent">
            <div class="form-check form-switch mb-0">
                <input type="hidden" name="${type}[attachments][${idx}][is_protected][${attIdx}]" value="0">
                <input class="form-check-input" type="checkbox" name="${type}[attachments][${idx}][is_protected][${attIdx}]" value="1" onchange="togglePass(this)">
                <label class="small mb-0 ms-1"><i class="fa fa-lock" style="font-size: 0.7rem;"></i></label>
            </div>
        </div>
        <input type="password" name="${type}[attachments][${idx}][password][]" class="form-control border-0 d-none" placeholder="Pass">
        <button type="button" class="btn btn-link text-danger p-0 px-2" onclick="this.closest('.attachment-row').remove()"><i class="fa fa-times"></i></button>
    `;
    list.appendChild(row);
}

function updateFileName(input) {
    if (input.files && input.files[0]) {
        const urlInput = input.previousElementSibling;
        urlInput.value = 'Selected: ' + input.files[0].name;
    }
}

function togglePass(cb) {
    const passInput = cb.closest('.attachment-row').querySelector('input[type="password"]');
    if (cb.checked) {
        passInput.classList.remove('d-none');
    } else {
        passInput.classList.add('d-none');
        passInput.value = '';
    }
}

window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        const tabBtn = document.querySelector(`button[data-bs-target="#${tabParam}"]`);
        if (tabBtn) {
            bootstrap.Tab.getOrCreateInstance(tabBtn).show();
            document.getElementById('active_tab_input').value = tabParam;
        }
    }
});
</script>

<style>
    .nav-tabs .nav-link { color: var(--text-gray); transition: all 0.3s; }
    .nav-tabs .nav-link:hover { color: var(--primary-purple); }
    .nav-tabs .nav-link.active { color: var(--primary-purple); border-bottom: 3px solid var(--primary-purple) !important; background: transparent; }
    .w-40 { width: 120px !important; font-weight: 600; font-size: 0.75rem; background: #f8f9fa; }
    
    /* Dynamic Highlight Styles */
    .bio-row { transition: all 0.3s ease; }
    .highlight-row { 
        transform: scale(1.02); 
        box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        z-index: 10;
    }
    .highlight-row .input-group {
        border: 2px solid var(--primary-purple) !important;
        border-radius: 0.4rem;
    }
    .bio-row:focus-within .input-group {
        border: 1px solid var(--primary-purple) !important;
        box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.1);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
