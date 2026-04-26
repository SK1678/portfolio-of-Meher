<?php
require '../db.php';

// Settings are already fetched in ../db.php

$hero_buttons = json_decode($settings['hero_buttons'] ?? '[]', true);
$name_style   = json_decode($settings['hero_name_style'] ?? '{}', true);
$title_style  = json_decode($settings['hero_title_style'] ?? '{}', true);

$fonts = ['Outfit', 'Inter', 'Roboto', 'Playfair Display', 'Montserrat', 'Poppins', 'Open Sans'];
$page_title = "Homepage Settings";
$show_save = true;

include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid px-4 py-4">
        <form id="settings-form" action="save_settings" method="POST" enctype="multipart/form-data">
            
            <div class="row">
                <div class="col-md-8">
                    <!-- Name Style Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-4"><i class="fa fa-font me-2 text-primary"></i> Name Style</h5>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($settings['name'] ?? '') ?>">
                            </div>
                            <div class="style-group">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Size (rem)</label>
                                        <input type="number" step="0.1" name="name_style[size]" class="form-control" value="<?= $name_style['size'] ?? '5' ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Font</label>
                                        <select name="name_style[font]" class="form-select">
                                            <?php foreach($fonts as $f): ?>
                                                <option value="<?= $f ?>" <?= ($name_style['font'] ?? '') == $f ? 'selected' : '' ?>><?= $f ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Type</label>
                                        <select name="name_style[color_type]" class="form-select" onchange="toggleGradient(this, 'name')">
                                            <option value="default" <?= ($name_style['color_type'] ?? 'default') == 'default' ? 'selected' : '' ?>>Default Theme Color</option>
                                            <option value="plain" <?= ($name_style['color_type'] ?? '') == 'plain' ? 'selected' : '' ?>>Plain Color</option>
                                            <option value="gradient" <?= ($name_style['color_type'] ?? '') == 'gradient' ? 'selected' : '' ?>>Gradient</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mt-1 color-pickers" style="display: <?= ($name_style['color_type'] ?? 'default') == 'default' ? 'none' : 'flex' ?>;">
                                    <div class="col-md-6">
                                        <label class="form-label">Color 1</label>
                                        <input type="color" name="name_style[color_1]" class="form-control form-control-color w-100" value="<?= $name_style['color_1'] ?? '#ffffff' ?>">
                                    </div>
                                    <div class="col-md-6 color-2-field" style="display: <?= ($name_style['color_type'] ?? '') == 'gradient' ? 'block' : 'none' ?>;">
                                        <label class="form-label">Color 2</label>
                                        <input type="color" name="name_style[color_2]" class="form-control form-control-color w-100" value="<?= $name_style['color_2'] ?? '#ffcc00' ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Titles Style Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-4"><i class="fa fa-heading me-2 text-primary"></i> Titles Style</h5>
                            <div class="mb-3">
                                <label class="form-label">Typing Titles (Comma separated)</label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($settings['title'] ?? '') ?>">
                            </div>
                            <div class="style-group">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Size (rem)</label>
                                        <input type="number" step="0.1" name="title_style[size]" class="form-control" value="<?= $title_style['size'] ?? '1.5' ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Font</label>
                                        <select name="title_style[font]" class="form-select">
                                            <?php foreach($fonts as $f): ?>
                                                <option value="<?= $f ?>" <?= ($title_style['font'] ?? '') == $f ? 'selected' : '' ?>><?= $f ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Type</label>
                                        <select name="title_style[color_type]" class="form-select" onchange="toggleGradient(this, 'title')">
                                            <option value="default" <?= ($title_style['color_type'] ?? 'default') == 'default' ? 'selected' : '' ?>>Default Theme Color</option>
                                            <option value="plain" <?= ($title_style['color_type'] ?? '') == 'plain' ? 'selected' : '' ?>>Plain Color</option>
                                            <option value="gradient" <?= ($title_style['color_type'] ?? '') == 'gradient' ? 'selected' : '' ?>>Gradient</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mt-1 color-pickers" style="display: <?= ($title_style['color_type'] ?? 'default') == 'default' ? 'none' : 'flex' ?>;">
                                    <div class="col-md-6">
                                        <label class="form-label">Color 1</label>
                                        <input type="color" name="title_style[color_1]" class="form-control form-control-color w-100" value="<?= $title_style['color_1'] ?? '#dddddd' ?>">
                                    </div>
                                    <div class="col-md-6 color-2-field" style="display: <?= ($title_style['color_type'] ?? '') == 'gradient' ? 'block' : 'none' ?>;">
                                        <label class="form-label">Color 2</label>
                                        <input type="color" name="title_style[color_2]" class="form-control form-control-color w-100" value="<?= $title_style['color_2'] ?? '#ffffff' ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0"><i class="fa fa-mouse-pointer me-2 text-primary"></i> Action Buttons</h5>
                                <div class="btn-group shadow-sm rounded-pill">
                                    <button type="button" class="btn btn-outline-info btn-sm fw-bold px-3" onclick="addCVButton()">
                                        <i class="fa fa-sync-alt me-1"></i> Sync CV Button
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3" onclick="addButton()">
                                        <i class="fa fa-plus me-1"></i> Add Button
                                    </button>
                                </div>
                            </div>
                            <div id="buttons-container">
                                <?php foreach($hero_buttons as $index => $btn): ?>
                                    <div class="btn-row">
                                        <span class="remove-btn" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></span>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Text</label>
                                                <input type="text" name="buttons[<?= $index ?>][text]" class="form-control" value="<?= htmlspecialchars($btn['text']) ?>">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label">Link or File</label>
                                                <div class="input-group">
                                                    <input type="text" name="buttons[<?= $index ?>][link]" class="form-control" value="<?= htmlspecialchars($btn['link']) ?>">
                                                    <input type="file" name="button_files_<?= $index ?>" class="d-none" id="file_<?= $index ?>">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('file_<?= $index ?>').click()"><i class="fa fa-paperclip"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Color</label>
                                                <input type="color" name="buttons[<?= $index ?>][color]" class="form-control form-control-color w-100" value="<?= $btn['color'] ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label d-block text-center">Outline?</label>
                                                <div class="form-check form-switch d-flex justify-content-center pt-1">
                                                    <input type="hidden" name="buttons[<?= $index ?>][is_outline]" value="0">
                                                    <input class="form-check-input" type="checkbox" name="buttons[<?= $index ?>][is_outline]" value="1" <?= ($btn['is_outline'] ?? 0) == 1 ? 'checked' : '' ?>>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Background Settings -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-4"><i class="fa fa-image me-2 text-primary"></i> Background</h5>
                            <div class="mb-3">
                                <label class="form-label">Mode</label>
                                <select name="bg_type" class="form-select">
                                    <option value="image" <?= ($settings['bg_type'] ?? '') == 'image' ? 'selected' : '' ?>>Single Image</option>
                                    <option value="video" <?= ($settings['bg_type'] ?? '') == 'video' ? 'selected' : '' ?>>Background Video</option>
                                    <option value="slider" <?= ($settings['bg_type'] ?? '') == 'slider' ? 'selected' : '' ?>>Image Slider</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Upload New Media</label>
                                <input type="file" name="bg_files[]" class="form-control" multiple>
                                <small class="text-muted">You can select multiple files for slider.</small>
                            </div>


                            <div class="nav-label text-dark mt-4">Active Media</div>
                            <div class="media-preview-container">
                                <?php 
                                $media = explode(',', $settings['bg_media'] ?? '');
                                foreach($media as $m): if(empty($m)) continue; 
                                ?>
                                    <div class="media-wrapper">
                                        <input type="hidden" name="existing_media[]" value="<?= $m ?>">
                                        <?php if(strpos($m, '.mp4') !== false): ?>
                                            <div class="media-video-icon"><i class="fa fa-video"></i></div>
                                        <?php else: ?>
                                            <img src="../<?= $m ?>" class="media-item">
                                        <?php endif; ?>
                                        <div class="delete-media" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleGradient(select, type) {
        const row = select.closest('.style-group');
        const pickers = row.querySelector('.color-pickers');
        const color2Field = row.querySelector('.color-2-field');
        
        if (select.value === 'default') {
            pickers.style.display = 'none';
        } else {
            pickers.style.display = 'flex';
            color2Field.style.display = select.value === 'gradient' ? 'block' : 'none';
        }
    }

    let btnCount = <?= count($hero_buttons) ?>;
    
    function addCVButton() {
        const container = document.getElementById('buttons-container');
        const cvLink = "<?= addslashes($settings['cv_link'] ?? '#') ?>";
        const html = `
            <div class="btn-row" style="border: 2px dashed #0dcaf0; background: #f0faff;">
                <span class="remove-btn" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></span>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-info">Text (Auto-Synced)</label>
                        <input type="text" name="buttons[${btnCount}][text]" class="form-control" value="DOWNLOAD CV">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-info">Link (Auto-Synced)</label>
                        <div class="input-group">
                            <input type="text" name="buttons[${btnCount}][link]" class="form-control" value="${cvLink}">
                            <input type="file" name="button_files_${btnCount}" class="d-none" id="file_${btnCount}">
                            <button class="btn btn-outline-info" type="button" onclick="document.getElementById('file_${btnCount}').click()"><i class="fa fa-paperclip"></i></button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-info">Color</label>
                        <input type="color" name="buttons[${btnCount}][color]" class="form-control form-control-color w-100" value="#0dcaf0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block text-center fw-bold text-info">Outline?</label>
                        <div class="form-check form-switch d-flex justify-content-center pt-1">
                            <input type="hidden" name="buttons[${btnCount}][is_outline]" value="0">
                            <input class="form-check-input" type="checkbox" name="buttons[${btnCount}][is_outline]" value="1" checked>
                        </div>
                    </div>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
        btnCount++;
    }

    function addButton() {
        const container = document.getElementById('buttons-container');
        const html = `
            <div class="btn-row">
                <span class="remove-btn" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></span>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Text</label>
                        <input type="text" name="buttons[${btnCount}][text]" class="form-control" placeholder="Button Text">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Link or File</label>
                        <div class="input-group">
                            <input type="text" name="buttons[${btnCount}][link]" class="form-control" placeholder="URL or select file">
                            <input type="file" name="button_files_${btnCount}" class="d-none" id="file_${btnCount}">
                            <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('file_${btnCount}').click()"><i class="fa fa-paperclip"></i></button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Color</label>
                        <input type="color" name="buttons[${btnCount}][color]" class="form-control form-control-color w-100" value="#8e44ad">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block text-center">Outline?</label>
                        <div class="form-check form-switch d-flex justify-content-center pt-1">
                            <input type="hidden" name="buttons[${btnCount}][is_outline]" value="0">
                            <input class="form-check-input" type="checkbox" name="buttons[${btnCount}][is_outline]" value="1">
                        </div>
                    </div>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
        btnCount++;
    }
</script>
</body>
</html>
