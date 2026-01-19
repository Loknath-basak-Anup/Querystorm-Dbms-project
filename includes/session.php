<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * Require user to be logged in, redirect to login if not
 */
function require_login(): void {
  if (empty($_SESSION["user_id"])) {
    header("Location: /QuickMart/html/login.php");
    exit;
  }
}

/**
 * Require user to have specific role
 */
function require_role(string $role): void {
  require_login();
  if (($_SESSION["role"] ?? "") !== $role) {
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
 * JSON response helper
 */
function json_out(array $data, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}
