<?php
// libs/files/upload.php
// Keine Leerzeile oder Whitespace vor diesem Tag!

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/lang.php'; // Sprachdatei einbinden

// JSON-Header setzen
if (!headers_sent()) {
    header('Content-Type: application/json');
}

// --- Hilfsfunktion: Setting aus DB oder Default ---
function getSetting($pdo, $setting, $default) {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting = ?");
    $stmt->execute([$setting]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return ($result && trim($result['value']) !== '') ? $result['value'] : $default;
}

// --- Hilfsfunktion: Eindeutiger Shortcode ---
function generateCode($pdo) {
    do {
        $code = substr(uniqid(), -6);
        $stmt = $pdo->prepare("SELECT id FROM short_urls WHERE code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    } while ($exists);
    return $code;
}

// === Einstellungen laden ===
$base_url     = getSetting($pdo, 'base_url', BASE_URL);
$upload_dir   = rtrim(getSetting($pdo, 'upload_dir', 'uploads'), '/');
$max_size     = getSetting($pdo, 'max_file_size', MAX_FILE_SIZE);
$expiry_days  = getSetting($pdo, 'expiry_days', EXPIRY_DAYS);
$disallowed   = getSetting($pdo, 'disallowed_extensions', implode(',', DISALLOWED_EXTENSIONS));
$disallowedExt = array_map('trim', explode(',', $disallowed));

// === Upload-Verzeichnis vorbereiten ===
$upload_dir_abs = realpath(ROOT_PATH . '/' . $upload_dir);
if ($upload_dir_abs === false) {
    $upload_dir_abs = ROOT_PATH . '/' . $upload_dir;
}
if (!is_dir($upload_dir_abs) && !mkdir($upload_dir_abs, 0755, true)) {
    echo json_encode(["success" => false, "message" => t('upload_dir_create_error')]); // Mehrsprachige Fehlermeldung
    exit;
}

// === Anfrage validieren ===
if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_FILES["files"])) {
    echo json_encode(["success" => false, "message" => t('upload_invalid_request')]); // Mehrsprachige Fehlermeldung
    exit;
}

$userId = $_SESSION["user_id"] ?? null;
$expiry = ($userId && isset($_POST["permanent"]) && $_POST["permanent"] == "1")
    ? null
    : date("Y-m-d H:i:s", time() + ($expiry_days * 86400));

$uploadedFiles = [];

// === Upload durchführen ===
foreach ($_FILES["files"]["name"] as $key => $originalName) {
    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (in_array($fileExtension, $disallowedExt)) {
        echo json_encode(["success" => false, "message" => sprintf(t('upload_extension_blocked'), $fileExtension)]); // Mehrsprachige Fehlermeldung
        exit;
    }

    $fileSize = $_FILES["files"]["size"][$key];
    if ($fileSize > $max_size) {
        echo json_encode(["success" => false, "message" => sprintf(t('upload_file_too_large'), $originalName)]); // Mehrsprachige Fehlermeldung
        exit;
    }

    $fileTmp = $_FILES["files"]["tmp_name"][$key];
    $uniqueName = uniqid() . '_' . basename($originalName);
    $absPath = $upload_dir_abs . DIRECTORY_SEPARATOR . $uniqueName;
    $relPath = $upload_dir . '/' . $uniqueName;

    if (!move_uploaded_file($fileTmp, $absPath)) {
        echo json_encode(["success" => false, "message" => sprintf(t('upload_store_error'), $originalName)]); // Mehrsprachige Fehlermeldung
        exit;
    }

    // === Upload-DB-Eintrag ===
    $stmt = $pdo->prepare("
        INSERT INTO uploads (user_id, file_name, file_path, expiry_date)
        VALUES (:user_id, :file_name, :file_path, :expiry_date)
    ");
    $stmt->execute([
        ":user_id"     => $userId,
        ":file_name"   => $originalName,
        ":file_path"   => $relPath,
        ":expiry_date" => $expiry
    ]);

    // === Shortlink erzeugen ===
    $downloadUrl = $base_url . "libs/files/download.php?file=" . urlencode($relPath);
    $code = generateCode($pdo);
    $stmtShort = $pdo->prepare("INSERT INTO short_urls (code, url) VALUES (?, ?)");
    $stmtShort->execute([$code, $downloadUrl]);
    $shortUrl = $base_url . "r.php?c=" . urlencode($code);

    $uploadedFiles[] = [
        "name"     => $originalName,
        "url"      => $downloadUrl,
        "shortUrl" => $shortUrl,
        "expiry"   => $expiry ?? t('permanent_available')
    ];
}

echo json_encode(["success" => true, "links" => $uploadedFiles]);
exit;
