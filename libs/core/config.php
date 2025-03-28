<?php
// libs/core/config.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔍 Basisverzeichnis ermitteln (da liegt deine index.php)
define('ROOT_PATH', realpath(dirname(__DIR__) . '/../')); // also: eine Ebene über /libs/
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

// 🌐 BASE_URL automatisch erkennen (funktioniert auch in Unterordnern)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseUrlAuto = rtrim($protocol . $host . $scriptDir, '/') . '/';

define('BASE_URL', $baseUrlAuto);

// 🛠 Weitere Defaults
define('EXPIRY_DAYS', 30);
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100 MB
define('DISALLOWED_EXTENSIONS', ['exe', 'sql', 'bat', 'sh']);
