<?php
// libs/admin/admin_dashboard.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/lang.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../index.php");
    exit;
}

$base_url = getSetting($pdo, 'base_url', BASE_URL);
$settings = [];
$stmtSettings = $pdo->query("SELECT setting, value FROM settings");
while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting']] = $row['value'];
}

$site_name = $settings['site_name'] ?? 'FileHoster';
$site_logo = $settings['site_logo'] ?? '';
$db_upload_dir = getSetting($pdo, 'upload_dir', 'uploads/');
$db_max_file_size = getSetting($pdo, 'max_file_size', MAX_FILE_SIZE);
$expiry_days = getSetting($pdo, 'expiry_days', EXPIRY_DAYS);
$disallowed_ext = getSetting($pdo, 'disallowed_extensions', implode(',', DISALLOWED_EXTENSIONS));
$disallowedExtensionsArray = array_map('trim', explode(',', $disallowed_ext));

$upload_dir = realpath(__DIR__ . '/../../' . ltrim($db_upload_dir, '/'));

$stmtUsers = $pdo->query("SELECT * FROM users ORDER BY id ASC");
$users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

$stmtUploads = $pdo->query("SELECT u.*, us.username FROM uploads u LEFT JOIN users us ON u.user_id = us.id ORDER BY u.created_at DESC");
$uploads = $stmtUploads->fetchAll(PDO::FETCH_ASSOC);

$phpVersion = phpversion();
$serverOs = php_uname();
$diskFreeGB = round(disk_free_space(__DIR__) / 1073741824, 2);
$diskTotalGB = round(disk_total_space(__DIR__) / 1073741824, 2);

include __DIR__ . '/../header.php';
?>

<?php if (!empty($_SESSION["msg"])): ?>
    <div id="sessionMsg" class="alert alert-info alert-dismissible fade show" role="alert"
         style="position: fixed; top: 80px; left: 50%; transform: translateX(-50%); z-index: 1050;">
        <?php echo htmlspecialchars($_SESSION["msg"]); unset($_SESSION["msg"]); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="admin-dashboard-container d-flex">
    <div class="sidebar col-3">
        <div class="list-group" id="dashboardTabs" role="tablist">
            <a class="list-group-item list-group-item-action active" data-bs-toggle="list" href="#users" role="tab"><?php echo t('admin_tab_users'); ?></a>
            <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#filemanager" role="tab"><?php echo t('admin_tab_filemanager'); ?></a>
            <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#settings" role="tab"><?php echo t('admin_tab_settings'); ?></a>
            <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#system" role="tab"><?php echo t('admin_tab_system'); ?></a>
        </div>
    </div>

    <div class="content-area col-9">
        <div class="tab-content" id="dashboardTabContent">

            <!-- Benutzer -->
            <div class="tab-pane fade show active" id="users" role="tabpanel">
                <h3><?php echo t('admin_tab_users'); ?></h3>
                <input type="text" id="searchUsers" class="form-control mb-3" placeholder="<?php echo t('search_users_placeholder'); ?>">
                <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#newUserModal"><?php echo t('admin_user_btn_add'); ?></button>

                <?php if (count($users) === 0): ?>
                    <p><?php echo t('no_users_found'); ?></p>
                <?php else: ?>
                    <table class="table table-dark table-striped" id="userTable">
                        <thead>
                            <tr>
                                <th><?php echo t('user_id'); ?></th>
                                <th><?php echo t('user_username'); ?></th>
                                <th><?php echo t('user_email'); ?></th>
                                <th><?php echo t('user_role'); ?></th>
                                <th><?php echo t('user_actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $usr): ?>
                            <tr>
                                <td><?php echo $usr["id"]; ?></td>
                                <td><?php echo htmlspecialchars($usr["username"]); ?></td>
                                <td><?php echo htmlspecialchars($usr["email"]); ?></td>
                                <td><?php echo $usr["role"]; ?></td>
                                <td>
                                    <a href="<?php echo $base_url; ?>libs/admin/change_role.php?id=<?php echo $usr["id"]; ?>&role=user" class="btn btn-sm btn-secondary"><?php echo t('admin_user_btn_to_user'); ?></a>
                                    <a href="<?php echo $base_url; ?>libs/admin/change_role.php?id=<?php echo $usr["id"]; ?>&role=admin" class="btn btn-sm btn-warning"><?php echo t('admin_user_btn_to_admin'); ?></a>
                                    <a href="<?php echo $base_url; ?>libs/admin/delete_user.php?id=<?php echo $usr["id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo t('admin_user_confirm_delete'); ?>');"><?php echo t('admin_user_btn_delete'); ?></a>
                                    <button class="btn btn-sm btn-info reset-password-btn" data-id="<?php echo $usr["id"]; ?>" data-username="<?php echo htmlspecialchars($usr["username"]); ?>">
                                        <i class="fas fa-key"></i> <?php echo t('admin_user_btn_reset'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- FileManager -->
            <div class="tab-pane fade" id="filemanager" role="tabpanel">
                <h3><?php echo t('admin_tab_filemanager'); ?></h3>
                <input type="text" id="searchFiles" class="form-control mb-3" placeholder="<?php echo t('search_files_placeholder'); ?>">
                <div class="mb-3">
                    <button id="selectAll" class="btn btn-info btn-sm"><?php echo t('admin_file_btn_all'); ?></button>
                    <button id="deleteSelected" class="btn btn-danger btn-sm"><?php echo t('admin_file_btn_delete'); ?></button>
                </div>

                <?php if (count($uploads) === 0): ?>
                    <p><?php echo t('admin_file_none_found'); ?></p>
                <?php else: ?>
                    <div class="row">
                        <?php
                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        foreach ($uploads as $file):
                            $ext = strtolower(pathinfo($file["file_name"], PATHINFO_EXTENSION));
                            $fileUrl = $base_url . 'libs/files/download.php?file=' . urlencode($file["file_path"]);
                        ?>
                        <div class="col-md-4 mb-3 file-card"
                             data-filename="<?php echo htmlspecialchars($file["file_name"]); ?>"
                             data-username="<?php echo htmlspecialchars($file["username"] ?? ''); ?>">
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
                                    <p class="card-text"><?php echo t('file_uploaded'); ?> <?php echo $file["created_at"]; ?></p>
                                    <p class="card-text"><?php echo t('file_expires'); ?> <?php echo $file["expiry_date"] ?? 'Permanent'; ?></p>
                                    <p class="card-text"><?php echo t('file_uploader'); ?> <?php echo $file["username"] ?? 'Anonym'; ?></p>
                                    <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-success btn-sm"><?php echo t('file_download'); ?></a>
                                    <button class="btn btn-danger btn-sm delete-file" data-id="<?php echo $file["id"]; ?>">
                                        <i class="fas fa-times-circle"></i> <?php echo t('file_delete'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Einstellungen -->
            <div class="tab-pane fade" id="settings" role="tabpanel">
                <h3><?php echo t('settings_title'); ?></h3>
                <form action="<?php echo $base_url; ?>libs/admin/update_settings.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="site_name" class="form-label"><?php echo t('settings_site_name'); ?></label>
                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="site_logo" class="form-label"><?php echo t('settings_upload_logo'); ?></label>
                        <input type="file" class="form-control" id="site_logo" name="site_logo">
                    </div>
                    <?php if (!empty($site_logo)): ?>
                        <div class="mb-3">
                            <p><?php echo t('settings_current_logo'); ?></p>
                            <img src="<?php echo htmlspecialchars($base_url . ltrim($site_logo, '/')); ?>" alt="Logo" style="max-height: 100px;">
                            <a href="<?php echo $base_url; ?>libs/admin/delete_logo.php" class="btn btn-danger btn-sm" onclick="return confirm('<?php echo t('admin_logo_confirm_delete'); ?>');"><?php echo t('admin_logo_delete'); ?></a>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="base_url" class="form-label"><?php echo t('settings_base_url'); ?></label>
                        <input type="text" class="form-control" id="base_url" name="base_url" value="<?php echo htmlspecialchars($base_url); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="upload_dir" class="form-label"><?php echo t('settings_upload_dir'); ?></label>
                        <input type="text" class="form-control" id="upload_dir" name="upload_dir" value="<?php echo htmlspecialchars($db_upload_dir); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="max_file_size" class="form-label"><?php echo t('settings_max_size'); ?></label>
                        <input type="number" step="0.1" class="form-control" id="max_file_size" name="max_file_size" value="<?php echo htmlspecialchars($db_max_file_size / (1024 * 1024)); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="expiry_days" class="form-label"><?php echo t('settings_expiry_days'); ?></label>
                        <input type="number" class="form-control" id="expiry_days" name="expiry_days" value="<?php echo htmlspecialchars($expiry_days); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="disallowed_extensions" class="form-label"><?php echo t('settings_disallowed_ext'); ?></label>
                        <input type="text" class="form-control" id="disallowed_extensions" name="disallowed_extensions" value="<?php echo htmlspecialchars($disallowed_ext); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php echo t('admin_settings_btn_save'); ?></button>
                </form>
            </div>

            <!-- System -->
            <div class="tab-pane fade" id="system" role="tabpanel">
                <h3><?php echo t('system_title'); ?></h3>
                <p><strong><?php echo t('system_php_version'); ?></strong> <?php echo $phpVersion; ?></p>
                <p><strong><?php echo t('system_os'); ?></strong> <?php echo $serverOs; ?></p>
                <p><strong><?php echo t('system_disk_free'); ?></strong> <?php echo $diskFreeGB; ?> GB</p>
                <p><strong><?php echo t('system_disk_total'); ?></strong> <?php echo $diskTotalGB; ?> GB</p>
            </div>

        </div>
    </div>
</div>

<!-- Neues Benutzer anlegen Modal (Dark Design) -->
<div class="modal fade" id="newUserModal" tabindex="-1" aria-labelledby="newUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="create_user.php" method="post">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title" id="newUserModalLabel"><?php echo t('modal_create_user_title'); ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php echo t('modal_button_close'); ?>"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="new_username" class="form-label"><?php echo t('modal_label_username'); ?></label>
            <input type="text" class="form-control bg-secondary text-white border-secondary" id="new_username" name="username" required>
          </div>
          <div class="mb-3">
            <label for="new_email" class="form-label"><?php echo t('modal_label_email'); ?></label>
            <input type="email" class="form-control bg-secondary text-white border-secondary" id="new_email" name="email" required>
          </div>
          <div class="mb-3">
            <label for="new_password" class="form-label"><?php echo t('modal_label_password'); ?></label>
            <input type="password" class="form-control bg-secondary text-white border-secondary" id="new_password" name="password" required>
          </div>
          <div class="mb-3">
            <label for="new_role" class="form-label"><?php echo t('modal_label_role'); ?></label>
            <select class="form-select bg-secondary text-white border-secondary" id="new_role" name="role" required>
              <option value="user" selected><?php echo t('create_user_option_user'); ?></option>
              <option value="admin"><?php echo t('create_user_option_admin'); ?></option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-top border-secondary">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal"><?php echo t('modal_button_close'); ?></button>
          <button type="submit" class="btn btn-primary"><?php echo t('modal_button_create'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Benutzer Passwort zurücksetzen Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="../auth/change_password.php" method="post">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title" id="resetPasswordModalLabel">
            <?php echo t('modal_pw_reset_title'); ?> <span id="modalUsername" class="text-info"></span>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php echo t('modal_button_close'); ?>"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="user_id" id="modalUserId">
          <div class="mb-3">
            <label for="reset_new_password" class="form-label"><?php echo t('modal_new_password'); ?></label>
            <input type="password" class="form-control bg-secondary text-white border-secondary" id="reset_new_password" name="new_password" required>
          </div>
          <div class="mb-3">
            <label for="reset_confirm_password" class="form-label"><?php echo t('modal_confirm_password'); ?></label>
            <input type="password" class="form-control bg-secondary text-white border-secondary" id="reset_confirm_password" name="confirm_password" required>
          </div>
        </div>
        <div class="modal-footer border-top border-secondary">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal"><?php echo t('modal_button_close'); ?></button>
          <button type="submit" class="btn btn-primary"><?php echo t('modal_pw_update'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
