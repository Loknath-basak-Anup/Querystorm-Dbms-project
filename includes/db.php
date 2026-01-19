<?php
declare(strict_types=1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_marketplace');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP has no password

// Define base URL for the application
define('BASE_URL', '/QuickMart');

try {
  $pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false
    ]
  );
} catch (Throwable $e) {
  http_response_code(500);
  echo "DB connection failed: " . $e->getMessage();
  exit;
}

/**
 * Execute a prepared query and return results
 */
function db_query(string $sql, array $params = []): PDOStatement {
  global $pdo;
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  return $stmt;
}

/**
 * Get a single row from query
 */
function db_fetch(string $sql, array $params = []) {
  $stmt = db_query($sql, $params);
  return $stmt->fetch();
}

/**
 * Get all rows from query
 */
function db_fetch_all(string $sql, array $params = []): array {
  $stmt = db_query($sql, $params);
  return $stmt->fetchAll();
}

/**
 * Execute an insert/update/delete query
 * Returns last insert ID for inserts
 */
function db_execute(string $sql, array $params = []): string {
  global $pdo;
  db_query($sql, $params);
  return $pdo->lastInsertId();
}
