<?php
if (!function_exists('getSetting')) {
    function getSetting($pdo, $setting, $default) {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting = ?");
        $stmt->execute([$setting]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result && trim($result['value']) !== '') ? $result['value'] : $default;
    }
}
