<?php
require_once __DIR__ . "/../includes/session.php";
session_destroy();
header("Location: /QuickMart/index.php");
exit;
