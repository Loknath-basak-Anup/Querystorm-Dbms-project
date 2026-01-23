<?php
if (session_status() === PHP_SESSION_NONE) {
    $base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $base,
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/supabase_storage.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    $declineReason = trim($_POST['decline_reason'] ?? '');
    $declineNotes = trim($_POST['decline_notes'] ?? '');
    $finalReason = '';
    if ($declineReason !== '' && $declineNotes !== '') {
        $finalReason = $declineReason . ' - ' . $declineNotes;
    } elseif ($declineReason !== '') {
        $finalReason = $declineReason;
    } elseif ($declineNotes !== '') {
        $finalReason = $declineNotes;
    }
    if ($userId > 0 && in_array($action, ['approve', 'decline'], true)) {
        if ($action === 'approve') {
            db_query("UPDATE seller_profiles SET verified = 1 WHERE seller_id = ?", [$userId]);
            db_query("UPDATE users SET status = 'active' WHERE user_id = ?", [$userId]);
            try {
                db_query("UPDATE seller_verification_requests SET status = 'approved' WHERE seller_id = ?", [$userId]);
            } catch (Exception $e) {
            }
            $flash = 'Seller approved successfully.';
        } else {
            db_query("UPDATE seller_profiles SET verified = 0 WHERE seller_id = ?", [$userId]);
            db_query("UPDATE users SET status = 'blocked' WHERE user_id = ?", [$userId]);
            try {
                db_query("ALTER TABLE seller_verification_requests ADD COLUMN decline_reason TEXT NULL");
            } catch (Exception $e) {
            }
            try {
                db_query("UPDATE seller_verification_requests SET status = 'declined', decline_reason = ? WHERE seller_id = ?", [$finalReason !== '' ? $finalReason : null, $userId]);
            } catch (Exception $e) {
            }
            $flash = 'Seller declined and blocked.';
        }
    }
}

$requests = [];
try {
    $requests = db_fetch_all("
        SELECT
            u.user_id,
            u.full_name,
            u.email,
            u.phone,
            u.status,
            sp.shop_name,
            sp.shop_description,
            sp.created_at,
            svr.nid,
            svr.date_of_birth,
            svr.business_type,
            svr.business_category,
            svr.tax_id,
            svr.business_license,
            svr.address,
            svr.bank_name,
            svr.account_name,
            svr.account_number,
            svr.routing_number,
            svr.branch_name,
            svr.id_document_url,
            svr.business_document_url,
            svr.decline_reason,
            svr.status AS request_status
        FROM seller_profiles sp
        INNER JOIN users u ON u.user_id = sp.seller_id
        LEFT JOIN (
            SELECT r.*
            FROM seller_verification_requests r
            INNER JOIN (
                SELECT seller_id, MAX(created_at) AS max_created
                FROM seller_verification_requests
                GROUP BY seller_id
            ) latest ON latest.seller_id = r.seller_id AND latest.max_created = r.created_at
        ) svr ON svr.seller_id = sp.seller_id
        WHERE sp.verified = 0
          AND u.status <> 'blocked'
          AND (svr.status IS NULL OR svr.status = 'pending')
        ORDER BY sp.created_at DESC
    ");
} catch (Exception $e) {
    $requests = db_fetch_all("
        SELECT
            u.user_id,
            u.full_name,
            u.email,
            u.phone,
            u.status,
            sp.shop_name,
            sp.shop_description,
            sp.created_at
        FROM seller_profiles sp
        INNER JOIN users u ON u.user_id = sp.seller_id
        WHERE sp.verified = 0
          AND u.status <> 'blocked'
        ORDER BY sp.created_at DESC
    ");
}

function render_doc_link(?string $url, string $label): string {
    $trimmed = trim((string)$url);
    if ($trimmed === '') {
        return '<div class="doc-placeholder">No file uploaded</div>';
    }
    $lower = strtolower($trimmed);
    $isImage = preg_match('/\\.(jpg|jpeg|png|webp|gif)(\\?.*)?$/', $lower) === 1;
    if ($isImage) {
        return '<a href="' . htmlspecialchars($trimmed) . '" target="_blank" rel="noopener"><img src="' . htmlspecialchars($trimmed) . '" alt="' . htmlspecialchars($label) . '" class="doc-image"></a>';
    }
    return '<a href="' . htmlspecialchars($trimmed) . '" target="_blank" rel="noopener" class="doc-link"><i class="fa-solid fa-file-arrow-down"></i> ' . htmlspecialchars($label) . '</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Verification Center | QuickMart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg: #0b1220;
            --card: rgba(15, 23, 42, 0.85);
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --primary: #14b8a6;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(circle at 10% 10%, rgba(56, 189, 248, 0.2), transparent 40%),
                        radial-gradient(circle at 80% 0%, rgba(20, 184, 166, 0.2), transparent 40%),
                        linear-gradient(160deg, #060b16, var(--bg));
            color: var(--text);
            min-height: 100vh;
            padding: 32px 24px 48px;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }
        .header h1 {
            font-size: 2rem;
            margin: 0;
        }
        .back-link {
            color: var(--muted);
            text-decoration: none;
            font-weight: 600;
        }
        .flash {
            background: rgba(20, 184, 166, 0.18);
            border: 1px solid rgba(20, 184, 166, 0.5);
            color: #99f6e4;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
        }
        .grid {
            display: grid;
            gap: 20px;
        }
        .card {
            background: var(--card);
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            padding: 20px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.35);
        }
        .card h2 {
            margin: 0 0 10px;
            font-size: 1.2rem;
        }
        .meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            font-size: 0.95rem;
            color: var(--muted);
        }
        .meta strong { color: var(--text); }
        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }
        .doc-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }
        .doc-placeholder {
            background: rgba(148, 163, 184, 0.1);
            border: 1px dashed rgba(148, 163, 184, 0.4);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            color: var(--muted);
        }
        .doc-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
        .decline-box {
            margin-top: 16px;
            display: grid;
            gap: 10px;
            padding: 14px;
            border-radius: 14px;
            border: 1px solid rgba(239, 68, 68, 0.35);
            background: rgba(239, 68, 68, 0.08);
        }
        .decline-box label {
            font-size: 0.85rem;
            color: var(--muted);
        }
        .decline-box select,
        .decline-box textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: rgba(15, 23, 42, 0.6);
            color: var(--text);
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.9rem;
        }
        .decline-box textarea {
            min-height: 90px;
            resize: vertical;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .btn {
            border: none;
            border-radius: 12px;
            padding: 10px 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-approve { background: var(--primary); color: #0b1020; }
        .btn-decline { background: rgba(239, 68, 68, 0.2); color: #fecaca; border: 1px solid rgba(239, 68, 68, 0.4); }
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
            border: 1px dashed rgba(148, 163, 184, 0.3);
            border-radius: 16px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Seller Verification Center</h1>
        <a class="back-link" href="admin.php"><i class="fa-solid fa-arrow-left"></i> Back to Admin</a>
    </div>

    <?php if ($flash !== ''): ?>
        <div class="flash"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <div class="grid">
        <?php if (!$requests): ?>
            <div class="empty">No pending seller verifications right now.</div>
        <?php else: ?>
            <?php foreach ($requests as $req): ?>
                <div class="card">
                    <h2><?php echo htmlspecialchars($req['full_name'] ?? 'Seller'); ?> <span style="color:var(--muted); font-weight:400;">(<?php echo htmlspecialchars($req['email'] ?? ''); ?>)</span></h2>
                    <div class="meta">
                        <div><strong>Shop:</strong> <?php echo htmlspecialchars($req['shop_name'] ?? 'N/A'); ?></div>
                        <div><strong>Phone:</strong> <?php echo htmlspecialchars($req['phone'] ?? 'N/A'); ?></div>
                        <div><strong>Submitted:</strong> <?php echo isset($req['created_at']) ? date('M d, Y H:i', strtotime($req['created_at'])) : 'N/A'; ?></div>
                        <?php if (!empty($req['nid'])): ?><div><strong>NID:</strong> <?php echo htmlspecialchars($req['nid']); ?></div><?php endif; ?>
                        <?php if (!empty($req['business_type'])): ?><div><strong>Business Type:</strong> <?php echo htmlspecialchars($req['business_type']); ?></div><?php endif; ?>
                        <?php if (!empty($req['business_category'])): ?><div><strong>Category:</strong> <?php echo htmlspecialchars($req['business_category']); ?></div><?php endif; ?>
                        <?php if (!empty($req['tax_id'])): ?><div><strong>Tax ID:</strong> <?php echo htmlspecialchars($req['tax_id']); ?></div><?php endif; ?>
                        <?php if (!empty($req['business_license'])): ?><div><strong>License:</strong> <?php echo htmlspecialchars($req['business_license']); ?></div><?php endif; ?>
                        <?php if (!empty($req['address'])): ?><div><strong>Address:</strong> <?php echo htmlspecialchars($req['address']); ?></div><?php endif; ?>
                        <?php if (!empty($req['bank_name'])): ?><div><strong>Bank:</strong> <?php echo htmlspecialchars($req['bank_name']); ?></div><?php endif; ?>
                        <?php if (!empty($req['account_name'])): ?><div><strong>Account Name:</strong> <?php echo htmlspecialchars($req['account_name']); ?></div><?php endif; ?>
                        <?php if (!empty($req['account_number'])): ?><div><strong>Account No:</strong> <?php echo htmlspecialchars($req['account_number']); ?></div><?php endif; ?>
                        <?php if (!empty($req['routing_number'])): ?><div><strong>Routing:</strong> <?php echo htmlspecialchars($req['routing_number']); ?></div><?php endif; ?>
                        <?php if (!empty($req['branch_name'])): ?><div><strong>Branch:</strong> <?php echo htmlspecialchars($req['branch_name']); ?></div><?php endif; ?>
                    </div>
                    <div class="doc-grid">
                        <div>
                            <div style="font-weight:600; margin-bottom:6px;">ID Document</div>
                            <?php echo render_doc_link($req['id_document_url'] ?? '', 'View ID Document'); ?>
                        </div>
                        <div>
                            <div style="font-weight:600; margin-bottom:6px;">Business Document</div>
                            <?php echo render_doc_link($req['business_document_url'] ?? '', 'View Business Document'); ?>
                        </div>
                    </div>
                    <form method="post" class="decline-box" id="declineForm<?php echo (int)$req['user_id']; ?>">
                        <label for="declineReason<?php echo (int)$req['user_id']; ?>">Decline reason (optional)</label>
                        <select id="declineReason<?php echo (int)$req['user_id']; ?>" name="decline_reason">
                            <option value="">Select a reason</option>
                            <option value="Missing or unclear documents">Missing or unclear documents</option>
                            <option value="Information mismatch">Information mismatch</option>
                            <option value="Business details incomplete">Business details incomplete</option>
                            <option value="Suspicious activity detected">Suspicious activity detected</option>
                            <option value="Bank details invalid">Bank details invalid</option>
                        </select>
                        <label for="declineNotes<?php echo (int)$req['user_id']; ?>">Notes to seller (optional)</label>
                        <textarea id="declineNotes<?php echo (int)$req['user_id']; ?>" name="decline_notes" placeholder="Add a short explanation to help the seller fix their application."></textarea>
                        <input type="hidden" name="user_id" value="<?php echo (int)$req['user_id']; ?>">
                        <div class="actions" style="margin-top: 6px;">
                        <button class="btn btn-approve" type="submit" name="action" value="approve"><i class="fa-solid fa-check"></i> Approve</button>
                        <button class="btn btn-decline" type="submit" name="action" value="decline" onclick="return confirm('Decline and block this seller?');"><i class="fa-solid fa-ban"></i> Decline</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
