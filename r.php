<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/libs/core/config.php';
require_once __DIR__ . '/libs/core/db_connection.php';

if (!isset($_GET["c"])) {
    die("❌ Kein Code angegeben.");
}

$code = trim($_GET["c"]);

try {
    $stmt = $pdo->prepare("SELECT url FROM short_urls WHERE code = ?");
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && isset($row["url"])) {
        header("Location: " . $row["url"]);
        exit;
    } else {
        die("❌ Kurz-URL nicht gefunden.");
    }
} catch (PDOException $e) {
    die("❌ Datenbankfehler: " . $e->getMessage());
}
