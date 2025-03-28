<?php
// libs/admin/update_settings.php

// Debugging aktivieren (nur in der Entwicklungsphase!)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/lang.php'; // Sprachdatei einbinden

// -----------------------------
// Formulardaten einlesen
// -----------------------------
$site_name            = trim($_POST['site_name'] ?? '');
$baseUrl              = trim($_POST['base_url'] ?? BASE_URL);
$uploadDirInput       = trim($_POST['upload_dir'] ?? '');
$maxFileSizeMB        = floatval($_POST['max_file_size'] ?? 100);
$expiryDays           = intval($_POST['expiry_days'] ?? 24);
$disallowedExtensions = strtolower(trim($_POST['disallowed_extensions'] ?? 'exe,sql,bat,sh'));

// -----------------------------
// Upload-Verzeichnis (als relativer Pfad speichern!)
// -----------------------------
if ($uploadDirInput === '') {
    $upload_dir = 'uploads/';
} else {
    // Nur relativen Pfad abspeichern (z. B. "uploads/", "files/", etc.)
    $upload_dir = rtrim($uploadDirInput, '/') . '/';
}

// -----------------------------
// Datei-Upload für Logo (optional)
// -----------------------------
$site_logo = getSetting($pdo, 'site_logo', '');
if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
    $logoTmp = $_FILES['site_logo']['tmp_name'];
    $logoExt = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
    $allowedLogoExts = ['jpg','jpeg','png','gif'];

    if (in_array($logoExt, $allowedLogoExts)) {
        $logoDir = __DIR__ . '/../logo/';
        if (!is_dir($logoDir)) {
            mkdir($logoDir, 0755, true);
        }

        $uniqueLogoName = uniqid() . "_" . basename($_FILES['site_logo']['name']);
        $logoPath = $logoDir . $uniqueLogoName;

        if (move_uploaded_file($logoTmp, $logoPath)) {
            // Relativer Pfad für die Datenbank (wird später via base_url ausgeliefert)
            $site_logo = 'libs/logo/' . $uniqueLogoName;
        }
    }
}

// -----------------------------
// Daten in die Datenbank schreiben
// -----------------------------
$maxFileSizeBytes = $maxFileSizeMB * 1024 * 1024;

$settingsToUpdate = [
    'site_name'             => $site_name,
    'base_url'              => $baseUrl,
    'upload_dir'            => $upload_dir,
    'max_file_size'         => $maxFileSizeBytes,
    'expiry_days'           => $expiryDays,
    'disallowed_extensions' => $disallowedExtensions,
    'site_logo'             => $site_logo
];

foreach ($settingsToUpdate as $key => $value) {
    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE setting = ?");
    $stmt->execute([$value, $key]);
}

// -----------------------------
// Redirect zurück zum Dashboard (angepasster Pfad)
// -----------------------------
$base_url = getSetting($pdo, 'base_url', BASE_URL);
header("Location: " . $base_url . "libs/admin/admin_dashboard.php?msg=" . urlencode(t('settings_update_success')) . "#settings");
exit;
