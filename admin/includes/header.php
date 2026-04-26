<div class="top-header">
    <div class="breadcrumb-custom">Portfolio CMS > <span><?= $page_title ?? 'Dashboard' ?></span></div>
    <div class="header-actions d-flex gap-2">
        <?php if (isset($show_save) && $show_save): ?>
            <button type="submit" form="settings-form" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm">
                <i class="fa fa-save me-2"></i> Save Changes
            </button>
        <?php endif; ?>
        <a href="../index.php" target="_blank" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
            <i class="fa fa-eye me-2"></i> Preview
        </a>
    </div>
</div>