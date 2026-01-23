<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

$isReapply = isset($_GET['reapply']) && $_GET['reapply'] === '1';
$isBuyerApply = isset($_GET['apply']) && $_GET['apply'] === 'buyer';
$prefill = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'nid' => '',
    'dateOfBirth' => '',
    'shop_name' => '',
    'businessType' => '',
    'businessCategory' => '',
    'taxId' => '',
    'shop_description' => '',
    'businessLicense' => '',
    'address_line1' => '',
    'address_line2' => '',
    'city' => '',
    'state' => '',
    'postal_code' => '',
    'country' => 'BD',
    'bankName' => '',
    'accountName' => '',
    'accountNumber' => '',
    'routingNumber' => '',
    'branchName' => ''
];
$formAction = '../actions/register_action.php';
$pageTitle = 'Seller Signup | QuickMart';
$pageHeading = 'Create Your Seller Account';
$pageSubheading = 'Start your business on QuickMart and reach millions of customers.';
$badgeText = 'Seller Registration';

if ($isReapply) {
    require_role('seller');
    $userId = get_user_id() ?? 0;
    $formAction = '../actions/reapply_seller.php';
    $pageTitle = 'Seller Re-Apply | QuickMart';
    $pageHeading = 'Re-apply for Seller Verification';
    $pageSubheading = 'Review your details, update your documents, and submit again.';
    $badgeText = 'Seller Re-apply';

    $userRow = db_fetch("SELECT full_name, email, phone FROM users WHERE user_id = ?", [$userId]);
    $profileRow = db_fetch("SELECT shop_name, shop_description FROM seller_profiles WHERE seller_id = ?", [$userId]);
    $reqRow = db_fetch(
        "SELECT nid, date_of_birth, business_type, business_category, tax_id, business_license,
                address, bank_name, account_name, account_number, routing_number, branch_name
         FROM seller_verification_requests
         WHERE seller_id = ?
         ORDER BY created_at DESC
         LIMIT 1",
        [$userId]
    );

    $fullName = trim((string)($userRow['full_name'] ?? ''));
    if ($fullName !== '') {
        $parts = preg_split('/\\s+/', $fullName);
        $prefill['first_name'] = $parts[0] ?? '';
        $prefill['last_name'] = count($parts) > 1 ? trim(implode(' ', array_slice($parts, 1))) : '';
    }
    $prefill['email'] = (string)($userRow['email'] ?? '');
    $prefill['phone'] = (string)($userRow['phone'] ?? '');
    $prefill['shop_name'] = (string)($profileRow['shop_name'] ?? '');
    $prefill['shop_description'] = (string)($profileRow['shop_description'] ?? '');

    if ($reqRow) {
        $prefill['nid'] = (string)($reqRow['nid'] ?? '');
        $prefill['dateOfBirth'] = (string)($reqRow['date_of_birth'] ?? '');
        $prefill['businessType'] = (string)($reqRow['business_type'] ?? '');
        $prefill['businessCategory'] = (string)($reqRow['business_category'] ?? '');
        $prefill['taxId'] = (string)($reqRow['tax_id'] ?? '');
        $prefill['businessLicense'] = (string)($reqRow['business_license'] ?? '');
        $prefill['address_line1'] = (string)($reqRow['address'] ?? '');
        $prefill['bankName'] = (string)($reqRow['bank_name'] ?? '');
        $prefill['accountName'] = (string)($reqRow['account_name'] ?? '');
        $prefill['accountNumber'] = (string)($reqRow['account_number'] ?? '');
        $prefill['routingNumber'] = (string)($reqRow['routing_number'] ?? '');
        $prefill['branchName'] = (string)($reqRow['branch_name'] ?? '');
    }
}

if ($isBuyerApply) {
    require_role('buyer');
    $userId = get_user_id() ?? 0;
    $formAction = '../actions/apply_seller_from_buyer.php';
    $pageTitle = 'Seller Application | QuickMart';
    $pageHeading = 'Apply to Become a Seller';
    $pageSubheading = 'Submit your seller details for review and approval.';
    $badgeText = 'Seller Application';

    $userRow = db_fetch("SELECT full_name, email, phone FROM users WHERE user_id = ?", [$userId]);
    $buyerRow = db_fetch("SELECT address FROM buyer_profiles WHERE buyer_id = ?", [$userId]);

    $fullName = trim((string)($userRow['full_name'] ?? ''));
    if ($fullName !== '') {
        $parts = preg_split('/\\s+/', $fullName);
        $prefill['first_name'] = $parts[0] ?? '';
        $prefill['last_name'] = count($parts) > 1 ? trim(implode(' ', array_slice($parts, 1))) : '';
    }
    $prefill['email'] = (string)($userRow['email'] ?? '');
    $prefill['phone'] = (string)($userRow['phone'] ?? '');
    $prefill['address_line1'] = (string)($buyerRow['address'] ?? '');
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES);
}
function selected($value, $current): string {
    return ((string)$value === (string)$current) ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #14b8a6;
            --primary-dark: #0f766e;
            --primary-light: #5eead4;
            --accent-color: #f97316;
            --success-color: #22c55e;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --bg-dark: #07141f;
            --bg-darker: #040b12;
            --card-bg: #0b1824;
            --input-bg: #0f2030;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border-color: #1e2e3b;
            --border-light: #2a3f4d;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(circle at 15% 20%, rgba(20, 184, 166, 0.12), transparent 35%),
                        radial-gradient(circle at 80% 10%, rgba(249, 115, 22, 0.12), transparent 40%),
                        linear-gradient(160deg, var(--bg-darker) 0%, var(--bg-dark) 100%);
            min-height: 100vh;
            color: var(--text-primary);
            padding: 2rem 1rem;
        }

        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 45%, rgba(20, 184, 166, 0.08) 0%, transparent 48%),
                radial-gradient(circle at 80% 80%, rgba(249, 115, 22, 0.08) 0%, transparent 52%),
                linear-gradient(120deg, rgba(20, 184, 166, 0.06), transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .layout-grid {
            display: grid;
            grid-template-columns: minmax(280px, 1fr) minmax(320px, 1.2fr);
            gap: 2.5rem;
            align-items: start;
        }

        .hero-panel {
            position: relative;
            padding: 2.5rem;
            border-radius: 1.75rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: linear-gradient(140deg, rgba(15, 23, 42, 0.7), rgba(11, 24, 36, 0.95));
            overflow: hidden;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.45);
        }

        .hero-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(20, 184, 166, 0.28), transparent 45%);
            opacity: 0.8;
            pointer-events: none;
        }

        .hero-panel::after {
            content: "";
            position: absolute;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            right: -80px;
            bottom: -80px;
            background: rgba(249, 115, 22, 0.2);
            filter: blur(0);
            opacity: 0.6;
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            background: rgba(20, 184, 166, 0.15);
            color: var(--primary-light);
            border: 1px solid rgba(20, 184, 166, 0.35);
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .hero-title {
            font-size: clamp(2rem, 3vw, 2.8rem);
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 0.75rem;
        }

        .hero-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .hero-stat {
            background: rgba(15, 32, 48, 0.7);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 1rem;
            padding: 0.9rem 1rem;
        }

        .hero-stat strong {
            font-size: 1.2rem;
            display: block;
        }

        .hero-stat span {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .feature-list {
            display: grid;
            gap: 0.75rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .feature-item i {
            color: var(--primary-light);
            background: rgba(20, 184, 166, 0.16);
            border-radius: 10px;
            padding: 0.45rem;
        }

        .form-panel {
            display: grid;
            gap: 1.5rem;
        }

        .signup-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-container {
            margin-bottom: 1.5rem;
        }

        .logo-container img {
            width: 120px;
            height: auto;
            filter: drop-shadow(0 8px 18px rgba(20, 184, 166, 0.3));
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(20, 184, 166, 0.12);
            border: 2px solid rgba(20, 184, 166, 0.5);
            padding: 0.5rem 1.5rem;
            border-radius: 2rem;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .signup-card {
            background: var(--card-bg);
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.45);
            border: 1px solid rgba(148, 163, 184, 0.2);
            animation: riseIn 0.6s ease-out;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-light);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            font-size: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .input-group label .required {
            color: var(--danger-color);
            margin-left: 0.25rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1rem;
            pointer-events: none;
        }

        .input-wrapper input,
        .input-wrapper select,
        .input-wrapper textarea {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            background: var(--input-bg);
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        .input-wrapper textarea {
            min-height: 100px;
            resize: vertical;
        }

        .input-wrapper input:focus,
        .input-wrapper select:focus,
        .input-wrapper textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(20, 184, 166, 0.08);
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.18);
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            cursor: pointer;
            pointer-events: all;
            z-index: 10;
        }

        .toggle-password:hover {
            color: var(--primary-color);
        }

        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-bar {
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: var(--transition);
        }

        .strength-fill.weak {
            width: 33%;
            background: var(--danger-color);
        }

        .strength-fill.medium {
            width: 66%;
            background: var(--warning-color);
        }

        .strength-fill.strong {
            width: 100%;
            background: var(--success-color);
        }

        .strength-text {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .file-upload {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            background: var(--input-bg);
            border: 2px dashed var(--border-color);
            border-radius: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-upload:hover {
            border-color: var(--primary-color);
            background: rgba(20, 184, 166, 0.08);
        }

        .file-upload input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-icon {
            font-size: 2.5rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .file-upload-text {
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-align: center;
        }

        .file-upload-text strong {
            color: var(--primary-color);
        }

        .preview-btn {
            margin-top: 0.75rem;
            padding: 0.5rem 0.9rem;
            border-radius: 0.6rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(15, 23, 42, 0.65);
            color: var(--text-primary);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .preview-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .preview-modal {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1200;
            padding: 1.5rem;
        }

        .preview-modal.active {
            display: flex;
        }

        .preview-modal-content {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            width: min(620px, 100%);
            border: 1px solid var(--border-color);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.45);
        }

        .preview-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .preview-modal-header h3 {
            font-size: 1.1rem;
            margin: 0;
        }

        .preview-modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.4rem;
            cursor: pointer;
        }

        .preview-frame {
            width: 100%;
            max-height: 420px;
            border-radius: 0.75rem;
            border: 1px solid var(--border-color);
            object-fit: contain;
            background: rgba(15, 23, 42, 0.65);
        }

        .preview-placeholder {
            padding: 2rem;
            text-align: center;
            color: var(--text-secondary);
            border: 1px dashed var(--border-light);
            border-radius: 0.75rem;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            margin-top: 0.125rem;
        }

        .checkbox-group label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.5;
            cursor: pointer;
        }

        .checkbox-group a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .checkbox-group a:hover {
            text-decoration: underline;
        }

        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(20, 184, 166, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .form-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .form-footer p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-footer a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .info-box {
            background: rgba(20, 184, 166, 0.12);
            border: 1px solid rgba(20, 184, 166, 0.5);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-box-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-light);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        @keyframes riseIn {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .info-box-content {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
        }

        @media (max-width: 1024px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
            .signup-header {
                text-align: left;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem 0.5rem;
            }

            .signup-card {
                padding: 1.5rem;
                border-radius: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .section-title {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .logo-container img {
                width: 90px;
            }

            .signup-card {
                padding: 1.25rem;
            }

            .input-wrapper input,
            .input-wrapper select,
            .input-wrapper textarea {
                padding: 0.75rem 1rem 0.75rem 2.5rem;
                font-size: 0.875rem;
            }

            .btn-primary {
                padding: 0.875rem;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div id="sidebarContainer"></div>
    <div class="bg-pattern"></div>

    <div class="container">
        <div class="layout-grid">
            <section class="hero-panel">
                <div class="hero-content">
                    <div class="hero-badge">
                        <i class="fas fa-stars"></i>
                        <span>QuickMart Seller Program</span>
                    </div>
                    <h1 class="hero-title">Open a storefront that feels premium from day one.</h1>
                    <p class="hero-subtitle">Launch faster with built-in trust, instant exposure, and real-time insights. Your brand, your story, your customers.</p>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <strong>24h</strong>
                            <span>Average payout speed</span>
                        </div>
                        <div class="hero-stat">
                            <strong>5%</strong>
                            <span>Platform commission</span>
                        </div>
                        <div class="hero-stat">
                            <strong>1M+</strong>
                            <span>Monthly shoppers</span>
                        </div>
                    </div>
                    <div class="feature-list">
                        <div class="feature-item">
                            <i class="fas fa-shield-check"></i>
                            <span>Verified seller badge after approval</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Inventory + order analytics in one dashboard</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-hand-holding-dollar"></i>
                            <span>Secure, scheduled payouts to your bank</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="form-panel">
                <!-- Header -->
                <div class="signup-header">
                    <div class="logo-container">
                        <img src="../images/qmart_logo2.png" alt="QuickMart Logo" />
                    </div>
                    <div class="role-badge">
                        <i class="fas fa-store"></i>
                        <span><?php echo h($badgeText); ?></span>
                    </div>
                    <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo h($pageHeading); ?></h1>
                    <p style="color: var(--text-secondary);"><?php echo h($pageSubheading); ?></p>
                </div>

                <!-- Signup Form -->
                <div class="signup-card">
                    <form id="sellerSignupForm" action="<?php echo h($formAction); ?>" method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="role" value="seller" />
                <input type="hidden" id="fullName" name="full_name" />
                <!-- Personal Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user-circle"></i>
                        <span>Personal Information</span>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="firstName">First Name <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" id="firstName" name="first_name" placeholder="John" value="<?php echo h($prefill['first_name']); ?>" required />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="lastName">Last Name <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" id="lastName" name="last_name" placeholder="Doe" value="<?php echo h($prefill['last_name']); ?>" required />
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="email" name="email" placeholder="john.doe@example.com" value="<?php echo h($prefill['email']); ?>" <?php echo ($isReapply || $isBuyerApply) ? 'readonly' : ''; ?> required />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="tel" id="phone" name="phone" placeholder="+880 1234-567890" value="<?php echo h($prefill['phone']); ?>" required />
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="nid">National ID Number <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-id-card"></i>
                                <input type="text" id="nid" name="nid" placeholder="1234567890" value="<?php echo h($prefill['nid']); ?>" required />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="dateOfBirth">Date of Birth <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-calendar"></i>
                                <input type="date" id="dateOfBirth" name="dateOfBirth" value="<?php echo h($prefill['dateOfBirth']); ?>" required />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-briefcase"></i>
                        <span>Business Information</span>
                    </div>

                    <div class="info-box">
                        <div class="info-box-title">
                            <i class="fas fa-info-circle"></i>
                            <span>Business Verification</span>
                        </div>
                        <div class="info-box-content">
                            Your business information will be verified before you can start selling. Please provide accurate details.
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="businessName">Business/Shop Name <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-store-alt"></i>
                                <input type="text" id="businessName" name="shop_name" placeholder="Your Shop Name" value="<?php echo h($prefill['shop_name']); ?>" required />
                            </div>
                        </div>

                    <div class="input-group">
                        <label for="businessType">Business Type <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-building"></i>
                                <select id="businessType" name="businessType" required>
                                    <option value="">Select Business Type</option>
                                    <option value="individual" <?php echo selected('individual', $prefill['businessType']); ?>>Individual/Sole Proprietor</option>
                                    <option value="partnership" <?php echo selected('partnership', $prefill['businessType']); ?>>Partnership</option>
                                    <option value="company" <?php echo selected('company', $prefill['businessType']); ?>>Private Limited Company</option>
                                    <option value="corporation" <?php echo selected('corporation', $prefill['businessType']); ?>>Public Limited Company</option>
                                </select>
                            </div>
                        </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="businessCategory">Business Category <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-tags"></i>
                                <select id="businessCategory" name="businessCategory" required>
                                    <option value="">Select Category</option>
                                    <option value="electronics" <?php echo selected('electronics', $prefill['businessCategory']); ?>>Electronics</option>
                                    <option value="fashion" <?php echo selected('fashion', $prefill['businessCategory']); ?>>Fashion & Apparel</option>
                                    <option value="home" <?php echo selected('home', $prefill['businessCategory']); ?>>Home & Kitchen</option>
                                    <option value="beauty" <?php echo selected('beauty', $prefill['businessCategory']); ?>>Beauty & Personal Care</option>
                                    <option value="sports" <?php echo selected('sports', $prefill['businessCategory']); ?>>Sports & Outdoors</option>
                                    <option value="books" <?php echo selected('books', $prefill['businessCategory']); ?>>Books & Stationery</option>
                                    <option value="toys" <?php echo selected('toys', $prefill['businessCategory']); ?>>Toys & Games</option>
                                    <option value="groceries" <?php echo selected('groceries', $prefill['businessCategory']); ?>>Groceries</option>
                                    <option value="other" <?php echo selected('other', $prefill['businessCategory']); ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="taxId">Tax ID/TIN Number</label>
                            <div class="input-wrapper">
                                <i class="fas fa-receipt"></i>
                                <input type="text" id="taxId" name="taxId" placeholder="123-456-789" value="<?php echo h($prefill['taxId']); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="businessDescription">Business Description <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-align-left"></i>
                                <textarea id="businessDescription" name="shop_description" placeholder="Describe your business, products, and services..." required><?php echo h($prefill['shop_description']); ?></textarea>
                            </div>
                        </div>

                    <div class="input-group">
                        <label for="businessLicense">Business License/Trade License Number</label>
                        <div class="input-wrapper">
                            <i class="fas fa-certificate"></i>
                            <input type="text" id="businessLicense" name="businessLicense" placeholder="License number" value="<?php echo h($prefill['businessLicense']); ?>" />
                        </div>
                    </div>

                    <?php if ($isBuyerApply): ?>
                        <div class="input-group">
                            <label for="roleReason">Why do you want to become a seller? <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-comment-dots"></i>
                                <textarea id="roleReason" name="role_reason" placeholder="Tell us why you want to sell on QuickMart." required></textarea>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Business Address -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-map-marked-alt"></i>
                        <span>Business Address</span>
                    </div>

                    <div class="input-group">
                        <label for="addressLine1">Address Line 1 <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-home"></i>
                                <input type="text" id="addressLine1" name="address_line1" placeholder="Street address" value="<?php echo h($prefill['address_line1']); ?>" required />
                            </div>
                        </div>

                    <div class="input-group">
                        <label for="addressLine2">Address Line 2</label>
                            <div class="input-wrapper">
                                <i class="fas fa-building"></i>
                                <input type="text" id="addressLine2" name="address_line2" placeholder="Building, floor, etc." value="<?php echo h($prefill['address_line2']); ?>" />
                            </div>
                        </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="city">City <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-city"></i>
                                <input type="text" id="city" name="city" placeholder="Dhaka" value="<?php echo h($prefill['city']); ?>" required />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="state">State/Province</label>
                            <div class="input-wrapper">
                                <i class="fas fa-map"></i>
                                <input type="text" id="state" name="state" placeholder="Dhaka Division" value="<?php echo h($prefill['state']); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="postalCode">Postal Code <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-mail-bulk"></i>
                                <input type="text" id="postalCode" name="postal_code" placeholder="1000" value="<?php echo h($prefill['postal_code']); ?>" required />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="country">Country <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-globe"></i>
                                <select id="country" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="BD" <?php echo selected('BD', $prefill['country']); ?>>Bangladesh</option>
                                    <option value="IN" <?php echo selected('IN', $prefill['country']); ?>>India</option>
                                    <option value="PK" <?php echo selected('PK', $prefill['country']); ?>>Pakistan</option>
                                    <option value="US" <?php echo selected('US', $prefill['country']); ?>>United States</option>
                                    <option value="UK" <?php echo selected('UK', $prefill['country']); ?>>United Kingdom</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bank Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-university"></i>
                        <span>Bank Account Information</span>
                    </div>

                    <div class="info-box">
                        <div class="info-box-title">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure Payment Information</span>
                        </div>
                        <div class="info-box-content">
                            Your payment information is encrypted and secure. This is required to receive payments from customers.
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="bankName">Bank Name <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-landmark"></i>
                                <input type="text" id="bankName" name="bankName" placeholder="Bank name" value="<?php echo h($prefill['bankName']); ?>" required />
                            </div>
                        </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="accountName">Account Holder Name <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-user-tie"></i>
                                <input type="text" id="accountName" name="accountName" placeholder="Full name" value="<?php echo h($prefill['accountName']); ?>" required />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="accountNumber">Account Number <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-credit-card"></i>
                                <input type="text" id="accountNumber" name="accountNumber" placeholder="Account number" value="<?php echo h($prefill['accountNumber']); ?>" required />
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="routingNumber">Routing Number</label>
                            <div class="input-wrapper">
                                <i class="fas fa-hashtag"></i>
                                <input type="text" id="routingNumber" name="routingNumber" placeholder="Routing number" value="<?php echo h($prefill['routingNumber']); ?>" />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="branchName">Branch Name</label>
                            <div class="input-wrapper">
                                <i class="fas fa-code-branch"></i>
                                <input type="text" id="branchName" name="branchName" placeholder="Branch name" value="<?php echo h($prefill['branchName']); ?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Security -->
                <?php if (!$isReapply && !$isBuyerApply): ?>
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-shield-alt"></i>
                        <span>Account Security</span>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="password">Password <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password" name="password" placeholder="Create a strong password" required />
                                <i class="fas fa-eye toggle-password"></i>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill"></div>
                                </div>
                                <span class="strength-text">Password strength</span>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="confirmPassword">Confirm Password <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter password" required />
                                <i class="fas fa-eye toggle-password"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Document Upload -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-file-upload"></i>
                        <span>Document Upload</span>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="idDocument">Government ID (NID/Passport) <span class="required">*</span></label>
                            <div class="file-upload">
                                <input type="file" id="idDocument" name="idDocument" accept=".pdf,.jpg,.jpeg,.png" required />
                                <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                                <div class="file-upload-text">
                                    <strong>Click to upload</strong> or drag and drop<br>
                                    <small>PDF, JPG or PNG (Max 5MB)</small>
                                </div>
                            </div>
                            <button type="button" class="preview-btn" data-preview-target="idDocument" disabled>
                                <i class="fas fa-eye"></i> Preview
                            </button>
                        </div>

                        <div class="input-group">
                            <label for="businessDocument">Business License/Certificate</label>
                            <div class="file-upload">
                                <input type="file" id="businessDocument" name="businessDocument" accept=".pdf,.jpg,.jpeg,.png" />
                                <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                                <div class="file-upload-text">
                                    <strong>Click to upload</strong> or drag and drop<br>
                                    <small>PDF, JPG or PNG (Max 5MB)</small>
                                </div>
                            </div>
                            <button type="button" class="preview-btn" data-preview-target="businessDocument" disabled>
                                <i class="fas fa-eye"></i> Preview
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Terms & Conditions -->
                <div class="form-section">
                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required />
                        <label for="terms">
                            I agree to the <a href="#">Seller Terms & Conditions</a>, <a href="#">Privacy Policy</a>, and <a href="#">Commission Structure</a> of QuickMart
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-primary">
                    <span><?php echo $isReapply ? 'Submit Re-application' : 'Create Seller Account'; ?></span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <!-- Footer -->
            <div class="form-footer">
                <p>Already have an account? <a href="../html/login.php">Sign In</a></p>
            </div>
        </div>
            </section>
        </div>
    </div>

    <div class="preview-modal" id="previewModal">
        <div class="preview-modal-content">
            <div class="preview-modal-header">
                <h3 id="previewTitle">Document Preview</h3>
                <button class="preview-modal-close" id="previewClose" type="button">&times;</button>
            </div>
            <img id="previewImage" class="preview-frame" alt="Document preview" style="display:none;">
            <div id="previewPlaceholder" class="preview-placeholder">No file selected.</div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const input = this.previousElementSibling;
                if (!input) return;
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            });
        });

        // Password strength checker
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strengthFill = document.querySelector('.strength-fill');
                const strengthText = document.querySelector('.strength-text');
                if (!strengthFill || !strengthText) return;
                
                let strength = 0;
                if (password.length >= 8) strength++;
                if (password.match(/[a-z]+/)) strength++;
                if (password.match(/[A-Z]+/)) strength++;
                if (password.match(/[0-9]+/)) strength++;
                if (password.match(/[$@#&!]+/)) strength++;
                
                strengthFill.className = 'strength-fill';
                
                if (strength === 0) {
                    strengthFill.style.width = '0%';
                    strengthText.textContent = 'Password strength';
                    strengthText.style.color = 'var(--text-secondary)';
                } else if (strength <= 2) {
                    strengthFill.classList.add('weak');
                    strengthText.textContent = 'Weak password';
                    strengthText.style.color = 'var(--danger-color)';
                } else if (strength <= 4) {
                    strengthFill.classList.add('medium');
                    strengthText.textContent = 'Medium password';
                    strengthText.style.color = 'var(--warning-color)';
                } else {
                    strengthFill.classList.add('strong');
                    strengthText.textContent = 'Strong password';
                    strengthText.style.color = 'var(--success-color)';
                }
            });
        }

        // File upload display + enable preview
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const fileName = this.files[0]?.name;
                if (fileName) {
                    const textElement = this.nextElementSibling.nextElementSibling;
                    textElement.innerHTML = `<strong>${fileName}</strong><br><small>Click to change</small>`;
                }
                const previewBtn = this.closest('.input-group')?.querySelector('.preview-btn');
                if (previewBtn) previewBtn.disabled = !this.files[0];
            });
        });

        // Preview modal
        const previewModal = document.getElementById('previewModal');
        const previewImage = document.getElementById('previewImage');
        const previewPlaceholder = document.getElementById('previewPlaceholder');
        const previewTitle = document.getElementById('previewTitle');
        const previewClose = document.getElementById('previewClose');

        function openPreviewModal(title, file) {
            if (!previewModal || !previewImage || !previewPlaceholder || !previewTitle) return;
            previewTitle.textContent = title;
            const isImage = file && file.type.startsWith('image/');
            if (isImage) {
                const url = URL.createObjectURL(file);
                previewImage.src = url;
                previewImage.style.display = 'block';
                previewPlaceholder.style.display = 'none';
                previewImage.onload = () => URL.revokeObjectURL(url);
            } else {
                previewImage.style.display = 'none';
                previewPlaceholder.style.display = 'block';
                previewPlaceholder.textContent = file ? 'Preview is only available for images.' : 'No file selected.';
            }
            previewModal.classList.add('active');
        }

        function closePreviewModal() {
            if (!previewModal) return;
            previewModal.classList.remove('active');
        }

        document.querySelectorAll('.preview-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-preview-target');
                const input = targetId ? document.getElementById(targetId) : null;
                const file = input && input.files ? input.files[0] : null;
                openPreviewModal(btn.textContent.trim(), file);
            });
        });

        if (previewClose) previewClose.addEventListener('click', closePreviewModal);
        if (previewModal) {
            previewModal.addEventListener('click', (e) => {
                if (e.target === previewModal) closePreviewModal();
            });
        }

        // Form validation before submit (then allow normal POST to backend)
        document.getElementById('sellerSignupForm').addEventListener('submit', function(e) {
            const isReapply = <?php echo json_encode($isReapply); ?>;
            const terms = document.getElementById('terms').checked;

            if (!isReapply) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                if (password !== confirmPassword) {
                    e.preventDefault();
                    showNotification('Passwords do not match!', 'error');
                    return;
                }

                if (password.length < 8) {
                    e.preventDefault();
                    showNotification('Password must be at least 8 characters long!', 'error');
                    return;
                }
            }

            if (!terms) {
                e.preventDefault();
                showNotification('Please accept the Terms & Conditions!', 'error');
                return;
            }
            // If validation passes, let the form submit normally
        });

        // Notification system
        function showNotification(message, type = 'info') {
            const existingNotification = document.querySelector('.notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 2rem;
                right: 2rem;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 0.75rem;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
                z-index: 1000;
                animation: slideInRight 0.3s ease-out;
                font-size: 0.95rem;
                font-weight: 500;
                max-width: 400px;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { opacity: 0; transform: translateX(100px); }
                to { opacity: 1; transform: translateX(0); }
            }
            @keyframes slideOutRight {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(100px); }
            }
        `;
        document.head.appendChild(style);
    </script>
    <script src="../assets/js/products_page.js"></script>
</body>
</html>
