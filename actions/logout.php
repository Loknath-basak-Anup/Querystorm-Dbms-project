<?php
require_once __DIR__ . "/../includes/session.php";
session_destroy();
session_start();
session_unset();
session_destroy();

$base = defined('BASE_URL') ? BASE_URL : '/Querystorm-Dbms-project-Loknath/Querystorm-Dbms-project-Loknath';
header("Location: {$base}/index.php");
exit;
exit;
