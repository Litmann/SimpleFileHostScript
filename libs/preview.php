<?php
require_once __DIR__ . '/core/config.php';
require_once ROOT_PATH . '/libs/core/functions.php';

// Datei-Parameter prüfen
if (!isset($_GET["file"])) {
    http_response_code(400);
    exit("❌ Keine Datei angegeben.");
}

// Bereinigen des Pfads
$file = urldecode($_GET["file"]);
$file = str_replace(['..', './', '\\'], '', $file);

// Upload-Verzeichnis aus den Settings holen (z. B. "uploads/")
$uploadDir = rtrim(getSetting($pdo ?? null, 'upload_dir', UPLOAD_DIR), '/') . '/';

// Absoluter Pfad zum Upload-Verzeichnis
$uploadRoot = realpath(ROOT_PATH . '/' . $uploadDir);
$absolutePath = realpath($uploadRoot . '/' . $file);

// Sicherheits-Check: Muss im Upload-Ordner liegen und existieren
if (!$absolutePath || strpos($absolutePath, $uploadRoot) !== 0 || !file_exists($absolutePath)) {
    http_response_code(403);
    exit("❌ Zugriff verweigert oder Datei nicht vorhanden.");
}

// Content-Type anhand der Dateiendung bestimmen
$ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
switch ($ext) {
    case "jpg":
    case "jpeg":
        $contentType = "image/jpeg";
        break;
    case "png":
        $contentType = "image/png";
        break;
    case "gif":
        $contentType = "image/gif";
        break;
    case "webp":
        $contentType = "image/webp";
        break;
    default:
        $contentType = "application/octet-stream";
        break;
}

// Datei ausgeben
header("Content-Type: " . $contentType);
header("Content-Length: " . filesize($absolutePath));
readfile($absolutePath);
exit;
