<?php
// libs/files/download.php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/lang.php'; // Sprachdatei einbinden

// Datei-Parameter prüfen
if (!isset($_GET["file"])) {
    die(t('download_error_no_file')); // Mehrsprachige Fehlermeldung
}

$fileParam = urldecode($_GET["file"]);

// Pfad absichern
$fileParam = str_replace(['..', '\\', './'], '', $fileParam);

// Upload-Verzeichnis aus DB oder Fallback
$uploadDirRel = rtrim(getSetting($pdo, 'upload_dir', 'uploads'), '/');
$uploadRoot   = realpath(ROOT_PATH . '/' . $uploadDirRel);

// Absoluten Pfad zur Datei berechnen
$absolutePath = realpath(ROOT_PATH . '/' . $fileParam);

// Sicherheitscheck: Datei existiert, liegt im Upload-Verzeichnis
if (
    !$absolutePath ||
    strpos($absolutePath, $uploadRoot) !== 0 ||  // Zugriff außerhalb verhindern
    !file_exists($absolutePath)
) {
    die(t('download_error_invalid_path')); // Mehrsprachige Fehlermeldung
}

// Datenbankeintrag holen
$stmt = $pdo->prepare("SELECT * FROM uploads WHERE file_path = ? LIMIT 1");
$stmt->execute([$fileParam]);
$upload = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$upload) {
    die(t('download_error_not_found')); // Mehrsprachige Fehlermeldung
}

// Ablaufdatum prüfen
if ($upload["expiry_date"] !== null) {
    $expiryDate = strtotime($upload["expiry_date"]);
    if (time() > $expiryDate) {
        @unlink($absolutePath);

        $stmtDel = $pdo->prepare("DELETE FROM uploads WHERE id = ?");
        $stmtDel->execute([$upload["id"]]);

        $stmtShort = $pdo->prepare("DELETE FROM short_urls WHERE url LIKE ?");
        $stmtShort->execute(["%" . urlencode($upload["file_path"]) . "%"]);

        die(t('download_error_expired')); // Mehrsprachige Fehlermeldung
    }
}

// Datei ausliefern
header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . basename($absolutePath) . "\"");
header("Content-Length: " . filesize($absolutePath));
flush();
readfile($absolutePath);
exit;
