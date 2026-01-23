<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";

$email = strtolower(trim($_POST["email"] ?? ""));
$password = trim($_POST["password"] ?? "");

if ($email === "" || $password === "") {
  $base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
  header("Location: {$base}/html/login.php?err=1");
  exit;
}

$stmt = $pdo->prepare("
  SELECT u.user_id, u.full_name, u.email, u.password, r.role_name
  FROM users u
  JOIN roles r ON r.role_id = u.role_id
  WHERE u.email=? AND (u.status='active' OR u.status IS NULL)
  LIMIT 1
");
$stmt->execute([$email]);
$u = $stmt->fetch();

if (!$u) {
  $base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
  header("Location: {$base}/html/login.php?err=1");
  exit;
}

$dbPass = (string)$u["password"];
$ok = false;

/* DB seed passwords plaintext, তাই direct match */
if ($dbPass === $password) $ok = true;

/* future: hash হলে password_verify */
if (!$ok && (str_starts_with($dbPass, '$2y$') || str_starts_with($dbPass, '$2a$') || str_starts_with($dbPass, '$2b$'))) {
  $ok = password_verify($password, $dbPass);
}

if (!$ok) {
  $base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
  header("Location: {$base}/html/login.php?err=1");
  exit;
}

$_SESSION["user_id"] = (int)$u["user_id"];
$_SESSION["role"] = $u["role_name"];
$_SESSION["full_name"] = $u["full_name"];
$_SESSION["email"] = $u["email"];

/* role অনুযায়ী redirect */
$base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
if ($u["role_name"] === "seller") {
  header("Location: {$base}/seller_dashboard/seller_dashboard.php");
} elseif ($u["role_name"] === "buyer") {
  header("Location: {$base}/buyer_dashboard/buyer_dashboard.php");
} else {
  header("Location: {$base}/index.php");
}
exit;

