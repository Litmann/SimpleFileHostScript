<?php
// libs/auth/register.php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/lang.php'; // Sprachdatei einbinden

$base_url = rtrim(getSetting($pdo, 'base_url', BASE_URL), '/') . '/';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $agreed   = isset($_POST["agree"]) ? true : false;

    // Validierung
    if (strlen($username) < 3 || strlen($password) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('create_user_error_validation');
    } elseif (!$agreed) {
        $error = t('create_user_error_agb');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $error = t('create_user_error_duplicate');
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$username, $email, $hashedPassword]);
            $success = t('create_user_success');
        }
    }
}

include_once __DIR__ . '/../header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'de'; ?>"> <!-- Dynamische Spracheinstellung -->
<head>
    <meta charset="UTF-8">
    <title><?php echo t('register_title'); ?> - Dein Filehoster</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url); ?>libs/assets/style.css">
</head>
<body>
    <div class="container" style="max-width: 500px; padding-top: 80px;">
        <h1 class="mb-4 text-center"><?php echo t('register_heading'); ?></h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label"><?php echo t('create_user_label_username'); ?></label>
                <input type="text" class="form-control" name="username" id="username" required autocomplete="username">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label"><?php echo t('create_user_label_email'); ?></label>
                <input type="email" class="form-control" name="email" id="email" required autocomplete="email">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label"><?php echo t('create_user_label_password'); ?></label>
                <input type="password" class="form-control" name="password" id="password" required autocomplete="new-password">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="agree" name="agree">
                <label class="form-check-label" for="agree">
                    <?php echo t('register_label_agree'); ?>
                    <a href="<?php echo htmlspecialchars($base_url); ?>libs/terms.php" target="_blank"><?php echo t('register_label_terms'); ?></a>
                </label>
            </div>
            <button class="btn btn-primary w-100" type="submit"><?php echo t('create_user_submit'); ?></button>
        </form>

        <div class="text-center mt-3">
            <a href="<?php echo htmlspecialchars($base_url); ?>index.php"><?php echo t('register_link_home'); ?></a> |
            <a href="<?php echo htmlspecialchars($base_url); ?>libs/auth/login.php"><?php echo t('register_link_login'); ?></a>
        </div>
    </div>

    <?php include_once __DIR__ . '/../footer.php'; ?>
</body>
</html>
