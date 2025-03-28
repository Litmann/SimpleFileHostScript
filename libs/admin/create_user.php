<?php
// libs/admin/create_user.php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/lang.php'; // Sprachunterstützung

$base_url = getSetting($pdo, 'base_url', BASE_URL);

// Nur Admin darf einen neuen Benutzer anlegen
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: " . $base_url . "index.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? '');
    $email    = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');
    $role     = trim($_POST["role"] ?? '');

    if (strlen($username) < 3 || strlen($password) < 3) {
        $error = t("create_user_error_short");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t("create_user_error_email");
    } elseif (!in_array($role, ["user", "admin"])) {
        $error = t("create_user_error_role");
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $error = t("create_user_error_exists");
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $role]);
            $_SESSION["msg"] = t("create_user_success");
            header("Location: " . $base_url . "libs/admin/admin_dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION["lang"] ?? 'de'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("create_user_title"); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #141e30;
            color: white;
            font-family: Arial, sans-serif;
            padding-top: 80px;
        }
        .container {
            max-width: 500px;
            margin: auto;
            background: rgba(255, 255, 255, 0.08);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }
        .form-control,
        .form-select {
            background-color: #2a2a2a;
            color: white;
            border: 1px solid #444;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: #777;
            box-shadow: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo t("create_user_title"); ?></h1>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="mb-3">
                <label for="username" class="form-label"><?php echo t("create_user_label_username"); ?></label>
                <input type="text" class="form-control" name="username" id="username" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label"><?php echo t("create_user_label_email"); ?></label>
                <input type="email" class="form-control" name="email" id="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label"><?php echo t("create_user_label_password"); ?></label>
                <input type="password" class="form-control" name="password" id="password" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label"><?php echo t("create_user_label_role"); ?></label>
                <select class="form-select" name="role" id="role" required>
                    <option value="user"><?php echo t("create_user_option_user"); ?></option>
                    <option value="admin"><?php echo t("create_user_option_admin"); ?></option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100"><?php echo t("create_user_submit"); ?></button>
        </form>
    </div>
</body>
</html>
