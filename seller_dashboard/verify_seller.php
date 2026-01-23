<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

require_role('seller');

$flash = '';
$sellerId = get_user_id() ?? 0;
$reapplyStatus = $_GET['reapply'] ?? '';
$applyStatus = $_GET['apply'] ?? '';
if ($reapplyStatus === 'done' || $applyStatus === 'done') {
    $flash = 'Re-application submitted. We will review your details again.';
} elseif ($reapplyStatus === 'error' || $applyStatus === 'error') {
    $flash = 'Could not reapply. Please try again later.';
}

$profile = db_fetch(
    "SELECT u.full_name, u.email, u.status, sp.shop_name, sp.shop_description, sp.verified, sp.created_at
     FROM users u
     INNER JOIN seller_profiles sp ON sp.seller_id = u.user_id
     WHERE u.user_id = ?
     LIMIT 1",
    [$sellerId]
);

$isVerified = (int)($profile['verified'] ?? 0) === 1;
$accountStatus = $profile['status'] ?? 'active';
$request = null;
$declineReason = null;
$requestStatus = $isVerified ? 'approved' : 'pending';
$submittedAt = $profile['created_at'] ?? null;

try {
        $request = db_fetch(
            "SELECT status, created_at, decline_reason
             FROM seller_verification_requests
             WHERE seller_id = ?
             ORDER BY created_at DESC
             LIMIT 1",
            [$sellerId]
        );
        $declineReason = $request['decline_reason'] ?? null;
    if ($request && !empty($request['status'])) {
        $requestStatus = $request['status'];
    }
    if ($request && !empty($request['created_at'])) {
        $submittedAt = $request['created_at'];
    }
} catch (Exception $e) {
    // Table may not exist in older databases
}

if ($accountStatus === 'blocked' && $requestStatus !== 'approved') {
    $requestStatus = 'declined';
}

$statusLabel = $requestStatus === 'approved' ? 'Verified' : ($requestStatus === 'declined' ? 'Declined' : 'Pending Review');
$statusTone = $requestStatus === 'approved' ? 'verified' : ($requestStatus === 'declined' ? 'declined' : 'pending');
$displayName = trim((string)($profile['shop_name'] ?? $profile['full_name'] ?? 'Seller'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Verification | QuickMart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg-dark: #060b12;
            --bg-mid: #0b1520;
            --card: rgba(13, 24, 36, 0.9);
            --text: #f8fafc;
            --muted: #94a3b8;
            --primary: #14b8a6;
            --accent: #f97316;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background:
                radial-gradient(circle at 20% 10%, rgba(20, 184, 166, 0.18), transparent 40%),
                radial-gradient(circle at 85% 30%, rgba(249, 115, 22, 0.12), transparent 45%),
                linear-gradient(160deg, var(--bg-dark), var(--bg-mid));
            color: var(--text);
            min-height: 100vh;
            padding: 2.5rem 1.5rem;
        }
        .shell {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(260px, 1fr) minmax(320px, 1.2fr);
            gap: 2rem;
        }
        .card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.4);
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 6px 14px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .status-pill.pending { background: rgba(245, 158, 11, 0.15); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.4); }
        .status-pill.verified { background: rgba(34, 197, 94, 0.18); color: #86efac; border: 1px solid rgba(34, 197, 94, 0.45); }
        .status-pill.declined { background: rgba(239, 68, 68, 0.18); color: #fecaca; border: 1px solid rgba(239, 68, 68, 0.4); }
        .title { font-size: 2rem; font-weight: 700; margin: 12px 0; }
        .muted { color: var(--muted); line-height: 1.6; }
        .meta-grid { display: grid; gap: 12px; margin-top: 18px; }
        .meta-item { display: flex; align-items: center; gap: 10px; color: var(--muted); font-size: 0.95rem; }
        .meta-item i { color: var(--primary); }
        .steps { display: grid; gap: 14px; margin-top: 16px; }
        .step {
            display: flex;
            gap: 12px;
            padding: 12px;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: rgba(8, 16, 24, 0.7);
        }
        .step-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: #0b1020;
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.8), rgba(20, 184, 166, 0.3));
        }
        .cta-row { display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
        .btn {
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary { background: var(--primary); color: #0b1020; }
        .btn-secondary { background: rgba(148, 163, 184, 0.15); color: var(--text); border: 1px solid rgba(148, 163, 184, 0.35); }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            border: 1px solid rgba(148, 163, 184, 0.3);
            color: var(--muted);
        }
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php if ($flash !== ''): ?>
        <div style="max-width:1100px; margin:0 auto 18px; padding:12px 16px; border-radius:14px; border:1px solid rgba(20,184,166,0.5); background:rgba(20,184,166,0.12); color:#99f6e4;">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>
    <div class="shell">
        <section class="card">
            <div class="badge">
                <i class="fas fa-shield-check"></i>
                Seller Verification Center
            </div>
            <h1 class="title"><?php echo htmlspecialchars($displayName); ?></h1>
            <p class="muted">Track your verification status and unlock selling tools once approved by QuickMart.</p>
            <div style="margin-top: 16px;">
                <span class="status-pill <?php echo htmlspecialchars($statusTone); ?>">
                    <i class="fas fa-circle"></i>
                    <?php echo htmlspecialchars($statusLabel); ?>
                </span>
            </div>
            <div class="meta-grid">
                <div class="meta-item"><i class="fas fa-envelope"></i><?php echo htmlspecialchars($profile['email'] ?? ''); ?></div>
                <div class="meta-item"><i class="fas fa-store"></i><?php echo htmlspecialchars($profile['shop_name'] ?? 'Shop details pending'); ?></div>
                <div class="meta-item"><i class="fas fa-calendar"></i>Submitted: <?php echo $submittedAt ? date('M d, Y H:i', strtotime($submittedAt)) : 'N/A'; ?></div>
            </div>
        </section>
        <section class="card">
            <h2 style="font-size:1.4rem; font-weight:700; margin-bottom:12px;">What happens next</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-icon"><i class="fas fa-file-circle-check"></i></div>
                    <div>
                        <div style="font-weight:600;">Verification review</div>
                        <div class="muted">Our team checks your details and documents for accuracy.</div>
                    </div>
                </div>
                <div class="step">
                    <div class="step-icon"><i class="fas fa-comment-dots"></i></div>
                    <div>
                        <div style="font-weight:600;">Notification update</div>
                        <div class="muted">You will receive a dashboard notification once approved.</div>
                    </div>
                </div>
                <div class="step">
                    <div class="step-icon"><i class="fas fa-bolt"></i></div>
                    <div>
                        <div style="font-weight:600;">Start selling</div>
                        <div class="muted">Publish products, track orders, and receive payouts.</div>
                    </div>
                </div>
            </div>

            <?php if ($requestStatus === 'declined'): ?>
                <div style="margin-top:16px; padding:14px; border-radius:14px; border:1px solid rgba(239,68,68,0.5); background:rgba(239,68,68,0.12); color:#fecaca;">
                    <strong>Verification declined.</strong>
                    <?php if (!empty($declineReason)): ?>
                        <div style="margin-top:6px; color:#fecaca;">Reason: <?php echo htmlspecialchars($declineReason); ?></div>
                    <?php else: ?>
                        <div style="margin-top:6px; color:#fecaca;">Please contact support or update your information.</div>
                    <?php endif; ?>
                    <a class="btn btn-primary" href="../seller/signup.php?reapply=1" style="margin-top:12px;">
                        <i class="fas fa-rotate-right"></i> Re-apply for verification
                    </a>
                </div>
            <?php endif; ?>

            <div class="cta-row">
                <a class="btn btn-secondary" href="seller_dashboard.php"><i class="fas fa-arrow-left"></i>Back to dashboard</a>
                <?php if ($isVerified): ?>
                    <a class="btn btn-primary" href="my_products.php"><i class="fas fa-box"></i>Manage products</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="seller_chat_to_buyer.php"><i class="fas fa-headset"></i>Contact support</a>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
