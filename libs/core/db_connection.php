<?php
// db_connection.php

// Lade die DB-Konfiguration (diese Datei wird vom Installationsskript erzeugt)
require_once "db_config.php";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Fehler: " . $e->getMessage());
}
?>
