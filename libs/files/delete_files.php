<?php
// libs/files/delete_files.php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';

header('Content-Type: application/json');

// Nutzer-Session prüfen
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt."]);
    exit;
}

$userId  = $_SESSION["user_id"];
$isAdmin = ($_SESSION["role"] ?? '') === "admin";

$action = $_REQUEST["action"] ?? ''; // 'single' oder 'bulk'
$ids = [];

if ($action === 'single') {
    $fileId = intval($_GET["id"] ?? 0);
    if ($fileId <= 0) {
        echo json_encode(["success" => false, "message" => "Ungültige Datei-ID."]);
        exit;
    }
    $ids[] = $fileId;

} elseif ($action === 'bulk') {
    if (!isset($_POST["ids"]) || !is_array($_POST["ids"])) {
        echo json_encode(["success" => false, "message" => "Keine Dateien ausgewählt."]);
        exit;
    }
    $ids = array_map('intval', $_POST["ids"]);

} else {
    echo json_encode(["success" => false, "message" => "Ungültige Aktion."]);
    exit;
}

// Upload-Verzeichnis aus Settings
$uploadDirSetting = rtrim(getSetting($pdo, 'upload_dir', 'uploads'), '/');
$uploadRoot = realpath(ROOT_PATH . '/' . $uploadDirSetting);

$deletedCount = 0;

foreach ($ids as $id) {
    // Datei-Datensatz laden (je nach Rolle)
    if ($isAdmin) {
        $stmt = $pdo->prepare("SELECT * FROM uploads WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM uploads WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }

    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$file) continue;

    $relPath = ltrim($file["file_path"], '/\\');
    $absPath = realpath(ROOT_PATH . '/' . $relPath);

    if ($absPath && strpos($absPath, $uploadRoot) === 0 && file_exists($absPath)) {
        @unlink($absPath);
    }

    $stmtDel = $pdo->prepare("DELETE FROM uploads WHERE id = ?");
    $stmtDel->execute([$id]);

    $stmtShort = $pdo->prepare("DELETE FROM short_urls WHERE url LIKE ?");
    $stmtShort->execute(["%" . urlencode($file["file_path"]) . "%"]);

    $deletedCount++;
}

if ($deletedCount > 0) {
    echo json_encode(["success" => true, "message" => "$deletedCount Datei(en) gelöscht."]);
} else {
    echo json_encode(["success" => false, "message" => "Keine Dateien gelöscht oder keine Berechtigung."]);
}
exit;
