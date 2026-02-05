<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/supabase_storage.php";

require_role('seller');

$userId = get_user_id();
if (!$userId) {
    header("Location: " . BASE_URL . "/html/login.php");
    exit;
}

$full_name = trim($_POST["full_name"] ?? "");
$first_name = trim($_POST["first_name"] ?? "");
$last_name = trim($_POST["last_name"] ?? "");
if ($full_name === '') {
    $full_name = trim(trim($first_name . " " . $last_name));
}

$phone = trim($_POST["phone"] ?? "");
$shop_name = trim($_POST["shop_name"] ?? "");
$shop_description = trim($_POST["shop_description"] ?? "");
$nid = trim($_POST["nid"] ?? "");
$date_of_birth = trim($_POST["dateOfBirth"] ?? "");
$business_type = trim($_POST["businessType"] ?? "");
$business_category = trim($_POST["businessCategory"] ?? "");
$tax_id = trim($_POST["taxId"] ?? "");
$business_license = trim($_POST["businessLicense"] ?? "");

$address_line1 = trim($_POST["address_line1"] ?? "");
$address_line2 = trim($_POST["address_line2"] ?? "");
$city = trim($_POST["city"] ?? "");
$state = trim($_POST["state"] ?? "");
$postal_code = trim($_POST["postal_code"] ?? "");
$country = trim($_POST["country"] ?? "");
$address_parts = array_filter([$address_line1, $address_line2, $city, $state, $postal_code, $country]);
$address = trim(implode(", ", $address_parts));

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

$idDocumentUrl = null;
$businessDocumentUrl = null;
try {
    $idDocumentUrl = upload_seller_doc('idDocument', 'seller-verification');
    $businessDocumentUrl = upload_seller_doc('businessDocument', 'seller-verification');
} catch (Exception $e) {
    error_log('Seller reapply document upload failed: ' . $e->getMessage());
}

try {
    $pdo->beginTransaction();

    db_query(
        "UPDATE users SET full_name = ?, phone = ?, status = 'active' WHERE user_id = ?",
        [$full_name, $phone, $userId]
    );

    db_query(
        "UPDATE seller_profiles SET shop_name = ?, shop_description = ?, verified = 0 WHERE seller_id = ?",
        [$shop_name !== '' ? $shop_name : 'My Shop', $shop_description ?: '', $userId]
    );

    try {
        db_query("ALTER TABLE seller_verification_requests ADD COLUMN id_document_url VARCHAR(500) NULL");
    } catch (Exception $e) {
    }
    try {
        db_query("ALTER TABLE seller_verification_requests ADD COLUMN business_document_url VARCHAR(500) NULL");
    } catch (Exception $e) {
    }
    try {
        db_query("ALTER TABLE seller_verification_requests ADD COLUMN decline_reason TEXT NULL");
    } catch (Exception $e) {
    }

    try {
        db_execute(
            "INSERT INTO seller_verification_requests (
                seller_id, nid, date_of_birth, business_type, business_category,
                tax_id, business_license, address, bank_name, account_name,
                account_number, routing_number, branch_name, id_document_url,
                business_document_url, decline_reason, status, created_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, 'pending', NOW())",
            [
                $userId,
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
    } catch (Exception $e) {
        db_query(
            "UPDATE seller_verification_requests SET
                nid = ?,
                date_of_birth = ?,
                business_type = ?,
                business_category = ?,
                tax_id = ?,
                business_license = ?,
                address = ?,
                bank_name = ?,
                account_name = ?,
                account_number = ?,
                routing_number = ?,
                branch_name = ?,
                id_document_url = COALESCE(?, id_document_url),
                business_document_url = COALESCE(?, business_document_url),
                decline_reason = NULL,
                status = 'pending',
                created_at = NOW()
             WHERE seller_id = ?",
            [
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
                $businessDocumentUrl,
                $userId
            ]
        );
    }

    $pdo->commit();
    header("Location: " . BASE_URL . "/seller_dashboard/verify_seller.php?reapply=done");
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: " . BASE_URL . "/seller_dashboard/verify_seller.php?reapply=error");
    exit;
}
