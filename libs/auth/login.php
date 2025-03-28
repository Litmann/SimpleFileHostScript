<?php
// libs/auth/login.php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/lang.php'; // Sprachdatei einbinden

$base_url = rtrim(getSetting($pdo, 'base_url', BASE_URL), '/') . '/';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Benutzer aus DB holen
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {
        // Login erfolgreich => Session setzen
        $_SESSION["user_id"]  = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"]     = $user["role"];

        // Weiterleitung, je nach Rolle
        $redirect = $base_url . 'libs/' . ($user["role"] === "admin" ? 'admin/admin_dashboard.php' : 'user/user_dashboard.php');
        header("Location: $redirect");
        exit;
    } else {
        $error = t('login_error_credentials'); // Fehlertext aus der Sprachdatei
    }
}

include_once __DIR__ . '/../header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'de'; ?>"> <!-- Dynamische Spracheinstellung -->
<head>
    <meta charset="UTF-8">
    <title><?php echo t('login_title'); ?> - Dein Filehoster</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url); ?>libs/assets/style.css">
</head>
<body>
    <div class="container pt-5" style="max-width: 400px;">
        <h1 class="mb-4 text-center"><?php echo t('login_heading'); ?></h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-3">
                <label for="username" class="form-label"><?php echo t('login_label_username'); ?></label>
                <input type="text" class="form-control" name="username" id="username" required autocomplete="username">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label"><?php echo t('login_label_password'); ?></label>
                <input type="password" class="form-control" name="password" id="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-primary w-100" type="submit"><?php echo t('login_btn_submit'); ?></button>
        </form>

        <div class="text-center mt-3">
            <a href="<?php echo htmlspecialchars($base_url); ?>index.php"><?php echo t('login_link_home'); ?></a> |
            <a href="<?php echo htmlspecialchars($base_url); ?>libs/auth/register.php"><?php echo t('login_link_register'); ?></a>
        </div>
    </div>

    <?php include_once __DIR__ . '/../footer.php'; ?>
</body>
</html>
