<?php
// libs/user/user_dashboard.php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/lang.php'; // Sprachdatei einbinden

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "libs/auth/login.php");
    exit;
}

$stmtUser = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION["user_id"]]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
$email = $userData ? $userData["email"] : 'Nicht gesetzt';

$stmt = $pdo->prepare("SELECT * FROM uploads WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION["user_id"]]);
$uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$base_url = getSetting($pdo, 'base_url', BASE_URL);

include ROOT_PATH . '/libs/header.php';
?>

<div class="user-dashboard-container">
    <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="account-tab" data-bs-toggle="tab" data-bs-target="#account" type="button" role="tab"><?php echo t('user_tab_account'); ?></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="filemanager-tab" data-bs-toggle="tab" data-bs-target="#filemanager" type="button" role="tab"><?php echo t('user_tab_filemanager'); ?></button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="dashboardTabContent">
        <div class="tab-pane fade show active" id="account" role="tabpanel">
            <div class="account-info">
                <p><strong><?php echo t('user_label_username'); ?>:</strong> <?php echo htmlspecialchars($_SESSION["username"]); ?></p>
                <p><strong><?php echo t('user_label_email'); ?>:</strong> <?php echo htmlspecialchars($email); ?></p>
            </div>

            <hr>

            <h4><?php echo t('user_change_password'); ?></h4>
            <form action="<?php echo $base_url; ?>libs/auth/change_password.php" method="post" class="password-form">
                <div class="mb-3">
                    <label for="new_password" class="form-label"><?php echo t('user_label_new_password'); ?></label>
                    <input type="password" class="form-control" name="new_password" id="new_password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label"><?php echo t('user_label_confirm_password'); ?></label>
                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo t('user_button_update_password'); ?></button>
            </form>

            <?php if (isset($_SESSION["msg"])): ?>
                <div class="alert alert-info mt-2"><?php echo htmlspecialchars($_SESSION["msg"]); ?></div>
                <?php unset($_SESSION["msg"]); ?>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="filemanager" role="tabpanel">
            <div class="mb-3">
                <input type="text" id="searchFiles" class="form-control" placeholder="<?php echo t('user_file_search_placeholder'); ?>">
            </div>
            <div class="mb-3">
                <button id="selectAll" class="btn btn-info btn-sm"><?php echo t('user_button_select_all'); ?></button>
                <button id="deleteSelected" class="btn btn-danger btn-sm"><?php echo t('user_button_delete_selected'); ?></button>
            </div>

            <?php if (count($uploads) === 0): ?>
                <p><?php echo t('user_no_uploads'); ?></p>
            <?php else: ?>
                <div class="row">
                    <?php
                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    foreach ($uploads as $file):
                        $ext = strtolower(pathinfo($file["file_name"], PATHINFO_EXTENSION));
                        $fileUrl = $base_url . 'libs/files/download.php?file=' . urlencode($file["file_path"]);
                    ?>
                    <div class="col-md-4 mb-3 file-card" data-filename="<?php echo htmlspecialchars($file["file_name"]); ?>">
                        <div class="card">
                            <div class="p-2">
                                <input type="checkbox" class="delete-checkbox" value="<?php echo $file["id"]; ?>">
                            </div>
                            <?php if (in_array($ext, $imageExtensions)): ?>
                                <img src="<?php echo htmlspecialchars($fileUrl); ?>" class="card-img-top" alt="">
                            <?php else: ?>
                                <div class="text-center pt-3"><i class="fas fa-file fa-3x"></i></div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title" title="<?php echo htmlspecialchars($file["file_name"]); ?>">
                                    <?php echo htmlspecialchars($file["file_name"]); ?>
                                </h5>
                                <p class="card-text"><?php echo t('user_file_uploaded'); ?> <?php echo $file["created_at"]; ?></p>
                                <p class="card-text"><?php echo t('user_file_expiry'); ?> <?php echo $file["expiry_date"] ?? t('permanent_available'); ?></p>
                                <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-success btn-sm"><?php echo t('user_file_download'); ?></a>
                                <button class="btn btn-danger btn-sm delete-file" data-id="<?php echo $file["id"]; ?>">
                                    <i class="fas fa-times-circle"></i> <?php echo t('user_file_delete'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/libs/footer.php'; ?>
