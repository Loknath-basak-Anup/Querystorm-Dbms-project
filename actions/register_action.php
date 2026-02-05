<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/supabase_storage.php";

// Get form data
$role = $_POST["role"] ?? "";

// Accept either combined or split names
$full_name = trim($_POST["full_name"] ?? "");
$first_name = trim($_POST["first_name"] ?? ($_POST["firstName"] ?? ""));
$last_name = trim($_POST["last_name"] ?? ($_POST["lastName"] ?? ""));
if (!$full_name) {
  $full_name = trim(trim($first_name . " " . $last_name));
}

$email = strtolower(trim($_POST["email"] ?? ""));
$phone = trim($_POST["phone"] ?? "");
$password = trim($_POST["password"] ?? "");
$confirm_password = trim($_POST["confirm_password"] ?? "");

// Shop-specific fields for sellers (allow alternative names)
$shop_name = trim($_POST["shop_name"] ?? ($_POST["businessName"] ?? ""));
$shop_description = trim($_POST["shop_description"] ?? ($_POST["businessDescription"] ?? ""));

// Seller verification / KYC fields (optional but stored for admin)
$nid = trim($_POST["nid"] ?? "");
$date_of_birth = trim($_POST["dateOfBirth"] ?? "");
$business_type = trim($_POST["businessType"] ?? "");
$business_category = trim($_POST["businessCategory"] ?? "");
$tax_id = trim($_POST["taxId"] ?? "");
$business_license = trim($_POST["businessLicense"] ?? "");

$idDocumentUrl = null;
$businessDocumentUrl = null;

function upload_seller_doc(string $fieldName, string $folder): ?string {
  if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
    return null;
  }
  $file = $_FILES[$fieldName];
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    return null;
  }
  $tmpName = $file['tmp_name'] ?? '';
  if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    return null;
  }
  $originalName = $file['name'] ?? 'document';
  $mimeType = $file['type'] ?? '';
  if ($mimeType === '' && function_exists('mime_content_type')) {
    $mimeType = mime_content_type($tmpName) ?: 'application/octet-stream';
  }
  if (!supabase_is_configured()) {
    return null;
  }
  return supabase_upload_image($tmpName, $originalName, $mimeType, $folder);
}

// Buyer address fields (support camelCase fallbacks)
$address_line1 = trim($_POST["address_line1"] ?? ($_POST["addressLine1"] ?? ""));
$address_line2 = trim($_POST["address_line2"] ?? ($_POST["addressLine2"] ?? ""));
$city = trim($_POST["city"] ?? "");
$state = trim($_POST["state"] ?? "");
$postal_code = trim($_POST["postal_code"] ?? ($_POST["postalCode"] ?? ""));
$country = trim($_POST["country"] ?? "");
$address_parts = array_filter([$address_line1, $address_line2, $city, $state, $postal_code, $country]);
$address = trim(implode(", ", $address_parts));

// Validate inputs
if (empty($role) || empty($full_name) || empty($email) || empty($password)) {
  header("Location: " . BASE_URL . "/html/login.php?err=missing");
  exit;
}

if ($password !== $confirm_password) {
  header("Location: " . BASE_URL . "/html/login.php?err=password_mismatch");
  exit;
}

// Check if email already exists
$existing = db_fetch("SELECT user_id FROM users WHERE email = ?", [$email]);
if ($existing) {
  header("Location: " . BASE_URL . "/html/login.php?err=email_exists");
  exit;
}

// Get role_id
$role_data = db_fetch("SELECT role_id FROM roles WHERE role_name = ?", [$role]);
if (!$role_data) {
  header("Location: " . BASE_URL . "/html/login.php?err=invalid_role");
  exit;
}
$role_id = $role_data['role_id'];

// Hash password for production (currently using plaintext for demo compatibility)
// $password_hash = password_hash($password, PASSWORD_BCRYPT);
$password_hash = $password; // Keep plaintext for demo compatibility

try {
  // Start transaction
  $pdo->beginTransaction();
  
  // Insert into users table
  $user_id = db_execute(
    "INSERT INTO users (role_id, full_name, email, phone, password, status, created_at) 
     VALUES (?, ?, ?, ?, ?, 'active', NOW())",
    [$role_id, $full_name, $email, $phone, $password_hash]
  );
  
  // Insert into role-specific profile table
  if ($role === 'buyer') {
    db_execute(
      "INSERT INTO buyer_profiles (buyer_id, address, created_at) VALUES (?, ?, NOW())",
      [$user_id, $address]
    );
  } elseif ($role === 'seller') {
    try {
      $idDocumentUrl = upload_seller_doc('idDocument', 'seller-verification');
      $businessDocumentUrl = upload_seller_doc('businessDocument', 'seller-verification');
    } catch (Exception $e) {
      error_log('Seller document upload failed: ' . $e->getMessage());
    }

    db_execute(
      "INSERT INTO seller_profiles (seller_id, shop_name, shop_description, verified, created_at) 
       VALUES (?, ?, ?, 0, NOW())",
      [$user_id, $shop_name ?: "My Shop", $shop_description ?: ""]
    );

    // Store full seller verification info for admin review
    // If this insert fails (e.g. old DB without this table/columns),
    // do NOT block seller registration – just log the problem.
    try {
      try {
        db_query("ALTER TABLE seller_verification_requests ADD COLUMN id_document_url VARCHAR(500) NULL");
      } catch (Exception $e) {
        // ignore if column exists
      }
      try {
        db_query("ALTER TABLE seller_verification_requests ADD COLUMN business_document_url VARCHAR(500) NULL");
      } catch (Exception $e) {
        // ignore if column exists
      }
      db_execute(
        "INSERT INTO seller_verification_requests (
            seller_id, nid, date_of_birth, business_type, business_category,
            tax_id, business_license, address, bank_name, account_name,
            account_number, routing_number, branch_name, id_document_url,
            business_document_url, status, created_at
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())",
        [
          $user_id,
          $nid !== '' ? $nid : null,
          $date_of_birth !== '' ? $date_of_birth : null,
          $business_type !== '' ? $business_type : null,
          $business_category !== '' ? $business_category : null,
          $tax_id !== '' ? $tax_id : null,
          $business_license !== '' ? $business_license : null,
          $address !== '' ? $address : null,
          trim($_POST["bankName"] ?? "") ?: null,
          trim($_POST["accountName"] ?? "") ?: null,
          trim($_POST["accountNumber"] ?? "") ?: null,
          trim($_POST["routingNumber"] ?? "") ?: null,
          trim($_POST["branchName"] ?? "") ?: null,
          $idDocumentUrl,
          $businessDocumentUrl
        ]
      );
    } catch (Exception $inner) {
      error_log("Seller verification insert failed for user {$user_id}: " . $inner->getMessage());
      // Continue without failing the whole registration
    }
  }
  
  // Commit transaction
  $pdo->commit();

  // Auto-login after successful registration
  $_SESSION["user_id"] = (int)$user_id;
  $_SESSION["role"] = $role;
  $_SESSION["full_name"] = $full_name;
  $_SESSION["email"] = $email;

  if ($role === 'seller') {
    $_SESSION["seller_welcome"] = true;
    header("Location: " . BASE_URL . "/seller_dashboard/seller_dashboard.php");
    exit;
  }

  // Redirect based on role
  if ($role === 'buyer') {
    header("Location: " . BASE_URL . "/buyer_dashboard/buyer_dashboard.php");
  } else {
    header("Location: " . BASE_URL . "/index.php");
  }
  exit;
  
} catch (Exception $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  error_log("Registration error: " . $e->getMessage());
  header("Location: " . BASE_URL . "/html/login.php?err=registration_failed");
  exit;
}
