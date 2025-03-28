<?php
// libs/auth/change_password.php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/lang.php'; // Sprachdatei einbinden

// Sicherstellen, dass der Nutzer eingeloggt ist
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Prüfen, ob Admin
$isAdmin = ($_SESSION["role"] ?? '') === "admin";

// User-ID bestimmen
if ($isAdmin && isset($_POST["user_id"])) {
    // Admin ändert Passwort eines Nutzers
    $userId = intval($_POST["user_id"]);
} else {
    // User ändert eigenes Passwort
    $userId = $_SESSION["user_id"];
}

// Passwort-Daten aus Formular übernehmen
$newPassword = trim($_POST["new_password"] ?? '');
$confirmPassword = trim($_POST["confirm_password"] ?? '');

// Prüfen ob per POST aufgerufen
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $base_url = getSetting($pdo, 'base_url', BASE_URL);
    $redirectTarget = $isAdmin ? "libs/admin/admin_dashboard.php" : "libs/user/user_dashboard.php";
    header("Location: " . $base_url . $redirectTarget);
    exit;
}

// Passwort-Validierung
if ($userId <= 0) {
    $error = t('change_password_error_invalid_user'); // Fehlertext aus Sprachdatei
} elseif (strlen($newPassword) < 3) {
    $error = t('change_password_error_short_password'); // Fehlertext aus Sprachdatei
} elseif ($newPassword !== $confirmPassword) {
    $error = t('change_password_error_mismatch'); // Fehlertext aus Sprachdatei
} else {
    // Nutzer in DB prüfen
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = t('change_password_error_user_not_found'); // Fehlertext aus Sprachdatei
    } else {
        // Passwort hashen und aktualisieren
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        $success = t('change_password_success'); // Erfolgsmeldung aus Sprachdatei
    }
}

// Nachricht setzen
$_SESSION["msg"] = isset($error) ? $error : $success;

// base_url holen
$base_url = getSetting($pdo, 'base_url', BASE_URL);

// Weiterleitung ins passende Dashboard
$redirectTarget = $isAdmin ? "libs/admin/admin_dashboard.php" : "libs/user/user_dashboard.php";
header("Location: " . $base_url . $redirectTarget);
exit;
