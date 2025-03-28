<?php
require_once __DIR__ . '/libs/core/config.php';
require_once __DIR__ . '/libs/core/db_connection.php';
require_once __DIR__ . '/libs/header.php';

// Hole Settings für JS
$db_max_file_size = getSetting($pdo, 'max_file_size', MAX_FILE_SIZE);
$display_max_file_size = $db_max_file_size / (1024 * 1024);
$disallowed_ext = getSetting($pdo, 'disallowed_extensions', implode(',', DISALLOWED_EXTENSIONS));
$disallowedExtensionsArray = array_map('trim', explode(',', $disallowed_ext));
?>
<!-- Inline Script: Definition globaler Variablen -->
<script>
    window.BASE_URL = "<?php echo rtrim(BASE_URL, '/') . '/'; ?>";
    const maxFileSize = <?php echo $display_max_file_size; ?>;
    const disallowedExtensions = <?php echo json_encode($disallowedExtensionsArray); ?>;
</script>

<!-- Hauptcontainer (Index) -->
<div class="index-container">
    <h2><?php echo t('upload_title'); ?></h2>
    <label class="upload-box" id="drop-area">
        <?php echo t('upload_instruction'); ?>
        <input type="file" id="files" name="files[]" class="form-control" multiple>
    </label>

    <?php if (isset($_SESSION["user_id"])): ?>
    <!-- Checkbox für permanente Links -->
    <div class="d-flex justify-content-center mt-2">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="permanent" name="permanent" value="1">
            <label class="form-check-label" for="permanent"><?php echo t('permanent_available'); ?></label>
        </div>
    </div>
    <?php endif; ?>

    <div id="file-list" class="file-list"></div>
    <p id="error-message" class="error-message"></p>
    <button onclick="uploadFiles()" class="btn btn-primary mt-3"><?php echo t('start_upload'); ?></button>
    <progress id="progress-bar" value="0" max="100"></progress>
    <div id="links" class="mt-4"></div>
</div>

<?php include __DIR__ . '/libs/footer.php'; ?>
