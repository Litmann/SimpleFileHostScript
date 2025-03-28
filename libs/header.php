<?php
// libs/header.php

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/db_connection.php';
require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/core/lang.php'; // Sprachlogik

// Sprache setzen, falls nicht vorhanden
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['de', 'en', 'fr', 'nl'])) {  // Hier kannst du weitere Sprachen hinzufügen
        $_SESSION['lang'] = $lang;
    }
} elseif (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'de'; // Standard-Sprache
}

// Settings laden
$site_name = getSetting($pdo, 'site_name', 'FileHoster');
$site_logo = getSetting($pdo, 'site_logo', '');
$base_url  = rtrim(getSetting($pdo, 'base_url', BASE_URL), '/') . '/';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'de'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($site_name); ?> - <?php echo t('site_subtitle'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url . 'libs/assets/style.css'); ?>">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        const BASE_URL = "<?php echo $base_url; ?>";
		window.translations = <?php echo json_encode($langStrings); ?>;
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?php echo htmlspecialchars($base_url); ?>index.php">
        <?php if (!empty($site_logo)): ?>
            <img src="<?php echo htmlspecialchars($base_url . $site_logo); ?>" alt="Logo" style="max-height: 40px;">
        <?php endif; ?>
        <?php echo htmlspecialchars($site_name); ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="topNavbar">
      <ul class="navbar-nav">
        <?php if (isset($_SESSION["user_id"])): ?>
          <?php if ($_SESSION["role"] === "admin"): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>libs/admin/admin_dashboard.php"><?php echo t('navbar_admin_dashboard'); ?></a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>libs/user/user_dashboard.php"><?php echo t('navbar_user_dashboard'); ?></a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>libs/auth/logout.php"><?php echo t('navbar_logout'); ?></a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>libs/auth/login.php"><?php echo t('navbar_login'); ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>libs/auth/register.php"><?php echo t('navbar_register'); ?></a></li>
        <?php endif; ?>

        <!-- Sprachumschalter -->
        <li class="nav-item dropdown ms-3">
            <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                🌐 <?php echo strtoupper($_SESSION['lang'] ?? 'de'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                <li><a class="dropdown-item" href="?lang=de">🇩🇪 Deutsch</a></li>
                <li><a class="dropdown-item" href="?lang=en">🇬🇧 English</a></li>
                <li><a class="dropdown-item" href="?lang=fr">🇫🇷 Français</a></li>
                <li><a class="dropdown-item" href="?lang=nl">🇳🇱 Nederlands</a></li>
            </ul>
        </li>
      </ul>

      <?php if (isset($_SESSION["user_id"])): ?>
        <span class="navbar-text ms-3"><?php echo t('navbar_greeting'); ?>, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</span>
      <?php endif; ?>
    </div>
  </div>
</nav>
