<?php
// libs/admin/delete_logo.php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/lang.php'; // Sprachdatei einbinden

// Nur Admin darf das Logo löschen
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? '') !== "admin") {
    $base_url = getSetting($pdo, 'base_url', BASE_URL);
    header("Location: " . $base_url . "index.php");
    exit;
}

$base_url = getSetting($pdo, 'base_url', BASE_URL);

// Aktuelles Logo aus der Datenbank holen
$stmt = $pdo->prepare("SELECT value FROM settings WHERE setting = ?");
$stmt->execute(['site_logo']);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$currentLogo = $result ? trim($result['value']) : '';

if (!empty($currentLogo)) {
    $absolutePath = realpath(__DIR__ . '/../' . ltrim($currentLogo, '/\\'));

    // Datei nur löschen, wenn sie im Projekt liegt
    if ($absolutePath && strpos($absolutePath, realpath(__DIR__ . '/../')) === 0 && file_exists($absolutePath)) {
        unlink($absolutePath);
    }
}

// Logo-Wert in der DB zurücksetzen
$stmt = $pdo->prepare("UPDATE settings SET value = '' WHERE setting = ?");
$stmt->execute(['site_logo']);

// Zurück ins Admin-Panel, direkt zum Tab „Einstellungen“
$base_url = getSetting($pdo, 'base_url', BASE_URL);
header("Location: " . $base_url . "libs/admin/admin_dashboard.php?msg=" . urlencode(t('logo_delete_success')) . "#settings");
exit;
