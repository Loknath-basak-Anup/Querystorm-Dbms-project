<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupons.php';

require_role('buyer');
ensure_coupon_tables();

$buyerId = get_user_id() ?? 0;
$purchaseId = (int)($_GET['purchase_id'] ?? 0);
$token = $_GET['token'] ?? '';

if ($buyerId <= 0 || $purchaseId <= 0 || $token === '') {
    http_response_code(400);
    echo "Invalid download request.";
    exit;
}

$purchase = db_fetch(
    "SELECT purchase_id, invoice_path, download_token, downloaded_at
     FROM coupon_purchases
     WHERE purchase_id = ? AND buyer_id = ?",
    [$purchaseId, $buyerId]
);

if (!$purchase || $purchase['download_token'] !== $token) {
    http_response_code(404);
    echo "Invoice not found.";
    exit;
}

function generate_invoice_pdf(int $purchaseId, int $buyerId): ?string {
    $row = db_fetch(
        "SELECT cp.purchase_id, cp.price, c.code, c.discount_type, c.discount_value,
                c.min_purchase, u.full_name
         FROM coupon_purchases cp
         INNER JOIN coupons c ON c.coupon_id = cp.coupon_id
         INNER JOIN users u ON u.user_id = cp.buyer_id
         WHERE cp.purchase_id = ? AND cp.buyer_id = ?",
        [$purchaseId, $buyerId]
    );
    if (!$row) return null;

    $dir = __DIR__ . '/uploads/invoices';
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $filePath = $dir . '/coupon_invoice_' . $purchaseId . '.pdf';

    // ---------- helpers ----------
    $esc = function(string $s): string {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    };
    $money = function($v): string {
        return number_format((float)$v, 2) . " BDT";
    };

    // ---------- data ----------
    $invoiceNo = (string)$purchaseId;
    $buyerName = trim((string)($row['full_name'] ?? 'Buyer'));
    $couponCode = (string)$row['code'];
    $discountType = strtoupper((string)$row['discount_type']);
    $discountValue = number_format((float)$row['discount_value'], 2);
    $minPurchase = $money($row['min_purchase']);
    $paid = (float)$row['price'];
    $paidText = $money($paid);

    $generatedAt = date('Y-m-d H:i:s');

    // Page size: Letter (612 x 792)
    // Layout constants
    $pageW = 612; $pageH = 792;
    $margin = 42;

    // Card area
    $cardX = $margin;
    $cardY = 66;
    $cardW = $pageW - ($margin * 2);
    $cardH = $pageH - 120;

    // Header bar inside card
    $headerH = 64;
    $headerX = $cardX;
    $headerY = $cardY + $cardH - $headerH;

    // Inner padding
    $pad = 18;
    $innerX = $cardX + $pad;
    $innerTop = $headerY - 16; // just below header

    // ---------- PDF content stream ----------
    // PDF operators:
    // rg = set fill color, RG = stroke color, re = rectangle, f = fill, S = stroke
    // BT ... ET = text block; Tf font-size; Td move; Tj show text

    $content = "";

    // Background (deep navy)
    $content .= "0.03 0.05 0.10 rg\n0 0 {$pageW} {$pageH} re\nf\n";

    // Soft glow accents behind card (subtle)
    // (Just two translucent-like blocks; true transparency requires ExtGState—skipping for simplicity)
    $content .= "0.10 0.18 0.35 rg\n60 610 220 120 re\nf\n";
    $content .= "0.25 0.12 0.35 rg\n330 620 220 110 re\nf\n";

    // Card container
    $content .= "0.06 0.09 0.16 rg\n{$cardX} {$cardY} {$cardW} {$cardH} re\nf\n";
    // Card border
    $content .= "0.22 0.28 0.40 RG\n1 w\n{$cardX} {$cardY} {$cardW} {$cardH} re\nS\n";

    // Header bar (cyan -> purple vibe; we approximate with 2 blocks)
    $content .= "0.10 0.65 0.90 rg\n{$headerX} {$headerY} {$cardW} {$headerH} re\nf\n";
    $content .= "0.45 0.35 0.95 rg\n" . ($headerX + ($cardW * 0.55)) . " {$headerY} " . ($cardW * 0.45) . " {$headerH} re\nf\n";

    // Header title + brand
    $content .= "BT\n";
    $content .= "/F2 20 Tf\n1 1 1 rg\n"; // white text
    $content .= ($innerX) . " " . ($headerY + 40) . " Td\n";
    $content .= "(" . $esc("QuickMart Invoice") . ") Tj\n";

    $content .= "/F1 10 Tf\n";
    $content .= "0 -18 Td\n";
    $content .= "(" . $esc("Premium coupon purchase receipt • Keep this for your records") . ") Tj\n";

    // Right side meta inside header
    $metaX = $cardX + $cardW - 210;
    $metaY = $headerY + 40;
    $content .= "/F2 11 Tf\n";
    $content .= "1 1 1 rg\n";
    $content .= ($metaX) . " " . ($metaY) . " Td\n";
    $content .= "(" . $esc("Invoice #{$invoiceNo}") . ") Tj\n";
    $content .= "/F1 9 Tf\n";
    $content .= "0 -14 Td\n";
    $content .= "(" . $esc($generatedAt) . ") Tj\n";
    $content .= "ET\n";

    // Section heading helper style (accent)
    // Buyer box
    $box1X = $innerX;
    $box1Y = $innerTop - 74;
    $box1W = ($cardW - $pad*2) * 0.58;
    $box1H = 64;

    // Invoice details box
    $box2X = $innerX + $box1W + 12;
    $box2Y = $box1Y;
    $box2W = ($cardW - $pad*2) - $box1W - 12;
    $box2H = $box1H;

    // Boxes background
    $content .= "0.04 0.07 0.14 rg\n{$box1X} {$box1Y} {$box1W} {$box1H} re\nf\n";
    $content .= "0.04 0.07 0.14 rg\n{$box2X} {$box2Y} {$box2W} {$box2H} re\nf\n";
    // Box borders
    $content .= "0.20 0.26 0.38 RG\n1 w\n{$box1X} {$box1Y} {$box1W} {$box1H} re\nS\n";
    $content .= "0.20 0.26 0.38 RG\n1 w\n{$box2X} {$box2Y} {$box2W} {$box2H} re\nS\n";

    // Buyer box text
    $content .= "BT\n";
    $content .= "0.72 0.85 1 rg\n/F2 10 Tf\n";
    $content .= ($box1X + 12) . " " . ($box1Y + 46) . " Td\n";
    $content .= "(" . $esc("BILLED TO") . ") Tj\n";

    $content .= "1 1 1 rg\n/F2 13 Tf\n0 -16 Td\n";
    $content .= "(" . $esc($buyerName) . ") Tj\n";

    $content .= "0.70 0.78 0.92 rg\n/F1 9 Tf\n0 -14 Td\n";
    $content .= "(" . $esc("Coupon buyer account") . ") Tj\n";
    $content .= "ET\n";

    // Invoice details box text
    $content .= "BT\n";
    $content .= "0.90 0.85 1 rg\n/F2 10 Tf\n";
    $content .= ($box2X + 12) . " " . ($box2Y + 46) . " Td\n";
    $content .= "(" . $esc("DETAILS") . ") Tj\n";

    $content .= "1 1 1 rg\n/F1 9 Tf\n0 -16 Td\n";
    $content .= "(" . $esc("Coupon: {$couponCode}") . ") Tj\n";
    $content .= "0 -13 Td\n";
    $content .= "(" . $esc("Min Purchase: {$minPurchase}") . ") Tj\n";
    $content .= "ET\n";

    // Table heading bar
    $tableX = $innerX;
    $tableTop = $box1Y - 18;
    $tableW = $cardW - $pad*2;
    $rowH = 28;

    $headY = $tableTop - $rowH;
    $content .= "0.08 0.12 0.22 rg\n{$tableX} {$headY} {$tableW} {$rowH} re\nf\n";
    $content .= "0.22 0.28 0.40 RG\n{$tableX} {$headY} {$tableW} {$rowH} re\nS\n";

    // Table columns
    $col1 = $tableX + 12;            // Description
    $col2 = $tableX + $tableW - 180; // Qty
    $col3 = $tableX + $tableW - 120; // Unit
    $col4 = $tableX + $tableW - 50;  // Total (right aligned-ish)

    $content .= "BT\n";
    $content .= "0.75 0.86 1 rg\n/F2 10 Tf\n";
    $content .= ($col1) . " " . ($headY + 10) . " Td\n(" . $esc("Description") . ") Tj\n";
    $content .= ($col2 - $col1) . " 0 Td\n(" . $esc("Qty") . ") Tj\n";
    $content .= "60 0 Td\n(" . $esc("Unit") . ") Tj\n";
    $content .= "70 0 Td\n(" . $esc("Total") . ") Tj\n";
    $content .= "ET\n";

    // Table row 1 (zebra)
    $row1Y = $headY - $rowH;
    $content .= "0.04 0.07 0.14 rg\n{$tableX} {$row1Y} {$tableW} {$rowH} re\nf\n";
    $content .= "0.22 0.28 0.40 RG\n{$tableX} {$row1Y} {$tableW} {$rowH} re\nS\n";

    $desc = "Coupon Purchase ({$couponCode})";
    $qty = "1";
    $unit = $paidText;
    $total = $paidText;

    $content .= "BT\n";
    $content .= "1 1 1 rg\n/F1 10 Tf\n";
    $content .= ($col1) . " " . ($row1Y + 10) . " Td\n(" . $esc($desc) . ") Tj\n";
    $content .= ($col2 - $col1) . " 0 Td\n(" . $esc($qty) . ") Tj\n";
    $content .= "60 0 Td\n(" . $esc($unit) . ") Tj\n";
    $content .= "70 0 Td\n(" . $esc($total) . ") Tj\n";
    $content .= "ET\n";

    // Discount info block
    $infoY = $row1Y - 52;
    $infoH = 56;
    $content .= "0.05 0.08 0.15 rg\n{$tableX} {$infoY} {$tableW} {$infoH} re\nf\n";
    $content .= "0.22 0.28 0.40 RG\n{$tableX} {$infoY} {$tableW} {$infoH} re\nS\n";

    $discountLine = "Discount: {$discountValue} ({$discountType})";
    $content .= "BT\n";
    $content .= "0.70 0.92 0.80 rg\n/F2 10 Tf\n";
    $content .= ($tableX + 12) . " " . ($infoY + 36) . " Td\n(" . $esc("COUPON BENEFIT") . ") Tj\n";
    $content .= "1 1 1 rg\n/F1 10 Tf\n0 -16 Td\n(" . $esc($discountLine) . ") Tj\n";
    $content .= "0.70 0.78 0.92 rg\n/F1 9 Tf\n0 -14 Td\n(" . $esc("Note: Discount applies when you use this coupon on eligible products.") . ") Tj\n";
    $content .= "ET\n";

    // Totals box (right side)
    $totW = 240;
    $totH = 92;
    $totX = $tableX + ($tableW - $totW);
    $totY = $infoY - 115;

    $content .= "0.04 0.07 0.14 rg\n{$totX} {$totY} {$totW} {$totH} re\nf\n";
    $content .= "0.22 0.28 0.40 RG\n{$totX} {$totY} {$totW} {$totH} re\nS\n";

    // Total label bar
    $content .= "0.10 0.65 0.90 rg\n{$totX} " . ($totY + $totH - 30) . " {$totW} 30 re\nf\n";

    $content .= "BT\n";
    $content .= "1 1 1 rg\n/F2 11 Tf\n";
    $content .= ($totX + 12) . " " . ($totY + $totH - 20) . " Td\n(" . $esc("TOTAL SUMMARY") . ") Tj\n";
    $content .= "ET\n";

    $content .= "BT\n";
    $content .= "1 1 1 rg\n/F1 10 Tf\n";
    $content .= ($totX + 12) . " " . ($totY + 42) . " Td\n(" . $esc("Amount Paid") . ") Tj\n";
    $content .= "/F2 14 Tf\n";
    $content .= "0.02 0.06 0.10 rg\n"; // dark text on light-ish? (won't show on dark). So keep white:
    $content .= "1 1 1 rg\n";
    $content .= "0 -18 Td\n(" . $esc($paidText) . ") Tj\n";
    $content .= "ET\n";

    // Footer line + message
    $footY = $cardY + 26;
    $content .= "0.22 0.28 0.40 RG\n1 w\n{$cardX} " . ($footY + 30) . " " . ($cardX + $cardW) . " " . ($footY + 30) . " m\nS\n";

    $content .= "BT\n";
    $content .= "0.70 0.78 0.92 rg\n/F1 9 Tf\n";
    $content .= ($innerX) . " " . ($footY + 14) . " Td\n";
    $content .= "(" . $esc("Thank you for choosing QuickMart • Support: quickmart.local • This invoice is system-generated.") . ") Tj\n";
    $content .= "ET\n";

    // --------- build PDF objects ----------
    $stream = "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    $objects = [];

    // 1) Catalog
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";
    // 2) Pages
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj";
    // 3) Page with resources (two fonts)
    $objects[] =
        "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageW} {$pageH}] " .
        "/Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>\nendobj";
    // 4) Content stream
    $objects[] = "4 0 obj\n{$stream}\nendobj";
    // 5) Helvetica
    $objects[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj";
    // 6) Helvetica-Bold
    $objects[] = "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj";

    // Write objects + xref
    $offset = strlen($pdf);
    foreach ($objects as $obj) {
        $offsets[] = $offset;
        $pdf .= $obj . "\n";
        $offset = strlen($pdf);
    }

    $xref = "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    foreach ($offsets as $off) {
        $xref .= str_pad((string)$off, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }

    $pdf .= $xref;
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $offset . "\n%%EOF";

    file_put_contents($filePath, $pdf);

    // Store path
    db_execute(
        "UPDATE coupon_purchases SET invoice_path = ? WHERE purchase_id = ?",
        [$filePath, $purchaseId]
    );

    return $filePath;
}


$path = $purchase['invoice_path'] ?? '';
$hasPdf = $path !== '' && file_exists($path) && str_ends_with(strtolower($path), '.pdf');
if (!$hasPdf) {
    $path = generate_invoice_pdf($purchaseId, $buyerId) ?? '';
}
if ($path === '' || !file_exists($path)) {
    http_response_code(404);
    echo "Invoice file missing.";
    exit;
}

if (empty($purchase['downloaded_at'])) {
    db_execute(
        "UPDATE coupon_purchases SET downloaded_at = NOW() WHERE purchase_id = ?",
        [$purchaseId]
    );
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="coupon_invoice_' . $purchaseId . '.pdf"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
