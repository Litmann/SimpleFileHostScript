<?php
require_once "../core/config.php";

// Session zerstören
session_unset();
session_destroy();

header("Location: ../../index.php");
exit;
?>
