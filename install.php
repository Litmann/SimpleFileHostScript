<?php
// install.php

if (file_exists("install.lock")) {
    die("✅ Die Installation wurde bereits abgeschlossen.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 🗃️ Formulardaten einlesen
    $db_host     = trim($_POST["db_host"]);
    $db_name     = trim($_POST["db_name"]);
    $db_user     = trim($_POST["db_user"]);
    $db_pass     = trim($_POST["db_pass"]);

    $site_name   = trim($_POST["site_name"]);
    $base_url    = rtrim(trim($_POST["base_url"]), '/') . '/';
    $upload_dir  = trim($_POST["upload_dir"]) ?: 'uploads/';
    $max_file_size = floatval($_POST["max_file_size"]) * 1024 * 1024;
    $expiry_days   = intval($_POST["expiry_days"]);
    $disallowed_extensions = trim($_POST["disallowed_extensions"]);

    $admin_username = trim($_POST["admin_username"]);
    $admin_email    = trim($_POST["admin_email"]);
    $admin_password = trim($_POST["admin_password"]);

    // 🖼️ Logo (optional)
    $site_logo = '';
    if (!empty($_FILES["site_logo"]["name"]) && $_FILES["site_logo"]["error"] === UPLOAD_ERR_OK) {
        $logoDir = __DIR__ . "/libs/logo/";
        if (!is_dir($logoDir)) mkdir($logoDir, 0755, true);

        $logoFilename = uniqid() . "_" . basename($_FILES["site_logo"]["name"]);
        $targetPath = $logoDir . $logoFilename;

        if (move_uploaded_file($_FILES["site_logo"]["tmp_name"], $targetPath)) {
            $site_logo = "libs/logo/" . $logoFilename;
        }
    }

    // 🧾 Konfigurationsdatei schreiben
    $configPath = __DIR__ . "/libs/core/db_config.php";
    $configContent = "<?php\n";
    $configContent .= "\$host = \"" . addslashes($db_host) . "\";\n";
    $configContent .= "\$dbname = \"" . addslashes($db_name) . "\";\n";
    $configContent .= "\$username = \"" . addslashes($db_user) . "\";\n";
    $configContent .= "\$password = \"" . addslashes($db_pass) . "\";\n";
    $configContent .= "?>";

    if (file_put_contents($configPath, $configContent) === false) {
        die("❌ Fehler: Konnte Konfiguration nicht speichern.");
    }

    // 🔌 Verbindung testen
    require_once $configPath;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        die("❌ Fehler bei DB-Verbindung: " . $e->getMessage());
    }

    // 📂 Upload-Verzeichnis erstellen
    $uploadAbsPath = __DIR__ . "/" . $upload_dir;
    if (!is_dir($uploadAbsPath)) mkdir($uploadAbsPath, 0755, true);

    try {
        // 📑 Tabellen erstellen
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('user','admin') DEFAULT 'user'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS uploads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT DEFAULT NULL,
                file_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expiry_date DATETIME DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                setting VARCHAR(50) PRIMARY KEY,
                value TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS short_urls (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(10) NOT NULL UNIQUE,
                url TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // ⚙️ Grundeinstellungen
        $stmt = $pdo->prepare("REPLACE INTO settings (setting, value) VALUES (?, ?)");
        $stmt->execute(["site_name", $site_name]);
        $stmt->execute(["site_logo", $site_logo]);
        $stmt->execute(["base_url", $base_url]);
        $stmt->execute(["upload_dir", $upload_dir]);
        $stmt->execute(["max_file_size", $max_file_size]);
        $stmt->execute(["expiry_days", $expiry_days]);
        $stmt->execute(["disallowed_extensions", $disallowed_extensions]);

        // 👑 Admin-User
        $hashedPassword = password_hash($admin_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$admin_username, $admin_email, $hashedPassword]);

        // 🔒 Sperrdatei anlegen
        file_put_contents("install.lock", "Installation abgeschlossen: " . date("Y-m-d H:i:s"));

        header("Location: " . $base_url . "libs/auth/login.php?installed=1");
        exit;

    } catch (PDOException $e) {
        die("❌ Fehler bei der Installation: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Installation - FileHoster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #141e30, #243b55);
            color: white;
        }
        .container {
            max-width: 700px;
            margin: 50px auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🛠️ FileHoster Setup</h1>
        <form method="post" enctype="multipart/form-data">
            <h4>Datenbank</h4>
            <input type="text" name="db_host" class="form-control mb-2" placeholder="DB-Host" required>
            <input type="text" name="db_name" class="form-control mb-2" placeholder="DB-Name" required>
            <input type="text" name="db_user" class="form-control mb-2" placeholder="DB-Benutzer" required>
            <input type="password" name="db_pass" class="form-control mb-3" placeholder="DB-Passwort" required>

            <h4>Seiten-Einstellungen</h4>
            <input type="text" name="site_name" class="form-control mb-2" placeholder="Seitentitel" required>
            <input type="file" name="site_logo" class="form-control mb-2">
            <input type="text" name="base_url" class="form-control mb-2" placeholder="Base URL (z.B. https://deinhost.de/)" required>
            <input type="text" name="upload_dir" class="form-control mb-2" placeholder="Upload-Verzeichnis (z.B. uploads/)" required>
            <input type="number" name="max_file_size" class="form-control mb-2" step="0.1" placeholder="Maximale Dateigröße (MB)" required>
            <input type="number" name="expiry_days" class="form-control mb-2" placeholder="Ablaufdauer (Tage)" required>
            <input type="text" name="disallowed_extensions" class="form-control mb-3" placeholder="Verbotene Endungen (z. B. php, exe)" required>

            <h4>Admin-Konto</h4>
            <input type="text" name="admin_username" class="form-control mb-2" placeholder="Benutzername" required>
            <input type="email" name="admin_email" class="form-control mb-2" placeholder="E-Mail" required>
            <input type="password" name="admin_password" class="form-control mb-3" placeholder="Passwort" required>

            <button type="submit" class="btn btn-success w-100">🚀 Installation starten</button>
        </form>
    </div>
</body>
</html>
