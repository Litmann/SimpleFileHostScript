<?php
require_once "config.php";
require_once "db_connection.php";

// Alle abgelaufenen Dateien suchen (expiry_date < NOW() oder, falls expiry_date NULL, werden nicht berücksichtigt)
$stmt = $pdo->prepare("SELECT * FROM uploads WHERE expiry_date IS NOT NULL AND expiry_date < NOW()");
$stmt->execute();
$expiredFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($expiredFiles as $file) {
    // Absoluten Pfad ermitteln – da file_path relativ zum Root ist und cron_delete.php im libs/ liegt
    $absolutePath = __DIR__ . "/../" . $file["file_path"];
    if (file_exists($absolutePath)) {
        unlink($absolutePath);
    }
    // DB-Eintrag aus der uploads-Tabelle löschen
    $stmtDel = $pdo->prepare("DELETE FROM uploads WHERE id = ?");
    $stmtDel->execute([$file["id"]]);

    // Zugehörigen Shortlink-Eintrag aus der short_urls-Tabelle löschen
    // Wir nutzen den in der uploads-Tabelle gespeicherten file_path (mit urlencode)
    $stmtShort = $pdo->prepare("DELETE FROM short_urls WHERE url LIKE ?");
    $stmtShort->execute(["%" . urlencode($file["file_path"]) . "%"]);
}

// Diese Datei kannst du dann per Cronjob aufrufen, z.B.
// 0 * * * * /usr/bin/php /path/to/cron_delete.php
?>
