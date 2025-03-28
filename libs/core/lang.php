<?php
// libs/core/lang.php

// Die Sprache festlegen: aus URL, Session oder Standard (deutsch)
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'de';
$lang = in_array($lang, ['de', 'en', 'fr', 'nl']) ? $lang : 'de';  // Hinzugefügt: fr und nl
$_SESSION['lang'] = $lang;

// Sprachdatei laden
$langFile = __DIR__ . '/../lang/' . $lang . '.php';
if (!file_exists($langFile)) {
    $langFile = __DIR__ . '/../lang/de.php';  // Standard auf Deutsch
}

// Sprachstrings laden
$langStrings = require $langFile;

// Funktion zur Übersetzung von Schlüsseln
function t($key) {
    global $langStrings;
    return $langStrings[$key] ?? $key;
}
