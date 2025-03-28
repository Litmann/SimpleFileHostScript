<?php
// libs/admin/change_role.php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/functions.php';

// Sicherstellen, dass Admin eingeloggt ist
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    $base_url = getSetting($pdo, 'base_url', BASE_URL);
    header("Location: " . $base_url . "index.php");
    exit;
}

$userId = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$newRole = isset($_GET["role"]) ? $_GET["role"] : "user";

// Rolle validieren
if (!in_array($newRole, ["user", "admin"])) {
    die("❌ Ungültige Rolle.");
}

// Rolle in der Datenbank aktualisieren
$stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->execute([$newRole, $userId]);

// Bestätigung setzen
$_SESSION["msg"] = "✅ Rolle wurde geändert.";

// Weiterleitung zum Admin-Dashboard
$base_url = getSetting($pdo, 'base_url', BASE_URL);
header("Location: " . $base_url . "libs/admin/admin_dashboard.php");
exit;
