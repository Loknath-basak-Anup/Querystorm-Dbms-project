<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Require user to be logged in, redirect to login if not
 */
function require_login(): void {
  if (empty($_SESSION["user_id"])) {
    // Use BASE_URL so the app works from the actual folder path
    $base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
    header("Location: {$base}/html/login.php");
    exit;
  }
}

/**
 * Require user to have specific role
 */
function require_role(string $role): void {
  require_login();
  $requiredRole = strtolower(trim($role));
  $sessionRole = strtolower(trim((string)($_SESSION["role"] ?? "")));
  if ($sessionRole === '') {
    $userId = (int)($_SESSION["user_id"] ?? 0);
    if ($userId > 0) {
      $row = db_fetch(
        "SELECT r.role_name
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE u.user_id = ?",
        [$userId]
      );
      $sessionRole = strtolower(trim((string)($row['role_name'] ?? '')));
      if ($sessionRole !== '') {
        $_SESSION["role"] = $sessionRole;
      }
    }
  }
  if ($sessionRole !== $requiredRole) {
    http_response_code(403);
    die("Access denied. Required role: $role");
  }
}

/**
 * Check if user is logged in
 */
function is_logged_in(): bool {
  return !empty($_SESSION["user_id"]);
}

/**
 * Get current user ID
 */
function get_user_id(): ?int {
  return $_SESSION["user_id"] ?? null;
}

/**
 * Get current user role
 */
function get_user_role(): ?string {
  return $_SESSION["role"] ?? null;
}

/**
 * Check if current seller is verified
 */
function is_seller_verified(?int $userId = null): bool {
  $id = $userId ?? get_user_id();
  if (!$id) return false;
  if (get_user_role() !== 'seller') return false;
  require_once __DIR__ . '/db.php';
  $row = db_fetch("SELECT verified FROM seller_profiles WHERE seller_id = ?", [$id]);
  return (int)($row['verified'] ?? 0) === 1;
}

/**
 * Require seller verification before accessing a feature
 */
function require_verified_seller(): void {
  require_role('seller');
  if (!is_seller_verified()) {
    $base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
    header("Location: {$base}/seller_dashboard/verify_seller.php");
    exit;
  }
}

/**
 * JSON response helper
 */
function json_out(array $data, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

/**
 * Ensure session user still exists and is active
 */
function enforce_active_session(): void {
  if (empty($_SESSION["user_id"])) {
    return;
  }
  $userId = (int)$_SESSION["user_id"];
  $row = db_fetch(
    "SELECT u.status, r.role_name
     FROM users u
     LEFT JOIN roles r ON r.role_id = u.role_id
     WHERE u.user_id = ?",
    [$userId]
  );
  $status = $row['status'] ?? null;
  $dbRole = $row['role_name'] ?? null;
  $sessionRole = $_SESSION["role"] ?? null;
  if ($dbRole && $sessionRole && $dbRole !== $sessionRole) {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_destroy();
    }
    $base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
    header("Location: {$base}/html/login.php?err=role_changed");
    exit;
  }
  if (!$row || $status === 'blocked') {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_destroy();
    }
    $base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
    $redirect = "{$base}/html/login.php?err=banned";
    $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    if ($isAjax) {
      json_out(['error' => 'banned', 'redirect' => $redirect], 401);
    }
    header("Location: {$redirect}");
    exit;
  }
}

enforce_active_session();

