<?php
// libs/admin/delete_user.php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/lang.php'; // Sprachdatei einbinden

// Nur Admins dürfen Benutzer löschen
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? '') !== "admin") {
    $base_url = getSetting($pdo, 'base_url', BASE_URL);
    header("Location: " . $base_url . "index.php");
    exit;
}

$userId = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

// Admin darf sich nicht selbst löschen
if ($userId === $_SESSION["user_id"]) {
    die(t('delete_user_error_self_delete')); // Fehlermeldung aus Sprachdatei
}

// Prüfen, ob der Benutzer existiert
$stmtCheck = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmtCheck->execute([$userId]);
if (!$stmtCheck->fetch()) {
    $base_url = getSetting($pdo, 'base_url', BASE_URL);
    header("Location: " . $base_url . "libs/admin/admin_dashboard.php?msg=" . urlencode(t('delete_user_error_not_found')));
    exit;
}

// Uploads dieses Benutzers holen
$stmtUploads = $pdo->prepare("SELECT * FROM uploads WHERE user_id = ?");
$stmtUploads->execute([$userId]);
$allUserUploads = $stmtUploads->fetchAll(PDO::FETCH_ASSOC);

// Dateien löschen (physisch)
foreach ($allUserUploads as $upload) {
    $absolutePath = realpath(__DIR__ . '/../' . ltrim($upload["file_path"], '/\\'));

    if ($absolutePath && strpos($absolutePath, realpath(__DIR__ . '/../')) === 0 && file_exists($absolutePath)) {
        unlink($absolutePath);
    }
}

// Upload- und Shortlink-Einträge aus der Datenbank löschen
$stmtDelUploads = $pdo->prepare("DELETE FROM uploads WHERE user_id = ?");
$stmtDelUploads->execute([$userId]);

$stmtDelShorts = $pdo->prepare("DELETE FROM short_urls WHERE url LIKE ?");
$stmtDelShorts->execute(["%" . urlencode("user_id={$userId}") . "%"]);

// Benutzer löschen
$stmtDelUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmtDelUser->execute([$userId]);

// Weiterleitung zurück zum Admin-Dashboard
$base_url = getSetting($pdo, 'base_url', BASE_URL);
header("Location: " . $base_url . "libs/admin/admin_dashboard.php?msg=" . urlencode(t('delete_user_success')));
exit;
