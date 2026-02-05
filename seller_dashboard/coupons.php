<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/coupons.php';

require_role('seller');
ensure_coupon_tables();

$sellerId = get_user_id();
$requests = db_fetch_all(
    "SELECT csr.request_id, csr.status, csr.response_note, csr.responded_at,
            c.coupon_id, c.code, c.title, c.description, c.discount_type, c.discount_value,
            c.min_purchase, c.max_discount, c.price, c.starts_at, c.ends_at
     FROM coupon_seller_requests csr
     INNER JOIN coupons c ON c.coupon_id = csr.coupon_id
     WHERE csr.seller_id = ?
     ORDER BY csr.created_at DESC",
    [$sellerId]
);

$products = db_fetch_all(
    "SELECT product_id, name
     FROM products
     WHERE seller_id = ? AND status = 'active'
     ORDER BY name ASC",
    [$sellerId]
);

$flashMsg = $_GET['msg'] ?? '';
$flashErr = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Coupon Requests | QuickMart</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg0:#070a12;
            --bg1:#0b1220;
            --card:rgba(15, 23, 42, .62);
            --card2:rgba(2, 6, 23, .55);
            --border:rgba(148, 163, 184, .18);
            --border2:rgba(148, 163, 184, .25);
            --text:#e5e7eb;
            --muted:#94a3b8;

            --accent:#38bdf8;
            --accent2:#a78bfa;
            --good:#22c55e;
            --bad:#fb7185;
            --warn:#fbbf24;

            --shadow: 0 18px 55px rgba(0,0,0,.45);
            --radius: 18px;
        }

        *{box-sizing:border-box}
        html,body{height:100%}
        body{
            margin:0;
            font-family:'Poppins',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
            color:var(--text);
            background:
                radial-gradient(900px 500px at 15% 8%, rgba(56,189,248,.22), transparent 55%),
                radial-gradient(900px 500px at 85% 12%, rgba(167,139,250,.18), transparent 55%),
                radial-gradient(1000px 650px at 50% 95%, rgba(34,197,94,.10), transparent 60%),
                linear-gradient(180deg, var(--bg0), var(--bg1));
            overflow-x:hidden;
        }

        /* Subtle animated noise-ish overlay */
        .fx{
            position:fixed; inset:0;
            pointer-events:none;
            background:
                radial-gradient(circle at 20% 20%, rgba(255,255,255,.06), transparent 30%),
                radial-gradient(circle at 80% 30%, rgba(255,255,255,.04), transparent 35%),
                radial-gradient(circle at 40% 80%, rgba(255,255,255,.03), transparent 40%);
            mix-blend-mode:overlay;
            opacity:.35;
        }

        .container{
            max-width:1180px;
            margin:0 auto;
            padding:28px 18px 60px;
        }

        /* Top header */
        .topbar{
            display:flex;
            gap:14px;
            justify-content:space-between;
            align-items:flex-start;
            flex-wrap:wrap;
            margin-bottom:18px;
        }

        .brand{
            display:flex;
            gap:14px;
            align-items:center;
        }

        .logo{
            width:46px; height:46px;
            border-radius:14px;
            display:grid;
            place-items:center;
            background:
                linear-gradient(135deg, rgba(56,189,248,.95), rgba(167,139,250,.90));
            color:#020617;
            box-shadow: 0 16px 30px rgba(56,189,248,.18);
        }

        h1{
            margin:0;
            font-size:1.35rem;
            letter-spacing:.2px;
        }

        .subtitle{
            margin:.35rem 0 0;
            color:var(--muted);
            font-size:.92rem;
            line-height:1.35;
        }

        .actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            align-items:center;
            justify-content:flex-end;
        }

        .link-btn{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:10px 12px;
            border-radius:12px;
            text-decoration:none;
            color:var(--text);
            border:1px solid var(--border);
            background:rgba(2,6,23,.35);
            backdrop-filter: blur(10px);
            transition:.18s ease;
        }
        .link-btn:hover{
            transform:translateY(-1px);
            border-color:rgba(56,189,248,.35);
            box-shadow: 0 10px 28px rgba(0,0,0,.25);
        }

        /* Flash */
        .flash{
            display:flex;
            gap:10px;
            align-items:flex-start;
            padding:12px 14px;
            border-radius:14px;
            margin:12px 0 18px;
            border:1px solid transparent;
            background:rgba(2,6,23,.35);
            backdrop-filter: blur(10px);
        }
        .flash i{margin-top:2px}
        .flash.success{
            border-color:rgba(34,197,94,.35);
            background:linear-gradient(180deg, rgba(34,197,94,.10), rgba(2,6,23,.35));
            color:#bbf7d0;
        }
        .flash.error{
            border-color:rgba(251,113,133,.38);
            background:linear-gradient(180deg, rgba(251,113,133,.10), rgba(2,6,23,.35));
            color:#fecdd3;
        }

        /* Main card */
        .panel{
            background: var(--card);
            border:1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow:hidden;
            backdrop-filter: blur(14px);
        }
        .panel-head{
            padding:16px 18px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            border-bottom:1px solid rgba(148,163,184,.14);
            background:
                linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,0));
        }
        .panel-head h2{
            margin:0;
            font-size:1.05rem;
            letter-spacing:.2px;
        }
        .pill-mini{
            display:inline-flex;
            align-items:center;
            gap:8px;
            font-size:.82rem;
            color:var(--muted);
        }
        .dot{
            width:8px; height:8px;
            border-radius:999px;
            background:rgba(148,163,184,.45);
            box-shadow: 0 0 0 3px rgba(148,163,184,.12);
        }

        /* Request list */
        .list{
            padding: 10px 14px 14px;
        }

        .req{
            border:1px solid rgba(148,163,184,.14);
            background: linear-gradient(180deg, rgba(2,6,23,.42), rgba(2,6,23,.18));
            border-radius: 16px;
            padding: 14px;
            margin: 12px 0;
            position:relative;
            overflow:hidden;
        }
        .req::before{
            content:"";
            position:absolute;
            inset:-1px;
            background: radial-gradient(400px 140px at 20% 0%, rgba(56,189,248,.18), transparent 60%),
                        radial-gradient(400px 140px at 85% 0%, rgba(167,139,250,.14), transparent 60%);
            opacity:.9;
            pointer-events:none;
        }
        .req > *{ position:relative; z-index:1; }

        .req-top{
            display:flex;
            gap:12px;
            justify-content:space-between;
            align-items:flex-start;
            flex-wrap:wrap;
        }

        .code{
            display:flex;
            align-items:center;
            gap:10px;
        }

        .code-badge{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:8px 10px;
            border-radius:12px;
            background: rgba(56,189,248,.10);
            border: 1px solid rgba(56,189,248,.22);
            color:#c7f0ff;
            font-weight:700;
            letter-spacing:.4px;
            font-size:.86rem;
        }

        .title{
            margin:0;
            font-weight:700;
            font-size:1rem;
        }
        .sub{
            margin:4px 0 0;
            color:var(--muted);
            font-size:.88rem;
        }

        .status-pill{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:8px 10px;
            border-radius:999px;
            border:1px solid rgba(148,163,184,.22);
            color:var(--muted);
            font-size:.78rem;
            text-transform:capitalize;
            background: rgba(2,6,23,.35);
        }
        .status-pill i{font-size:.9rem}

        .pill-approved{
            border-color: rgba(34,197,94,.35);
            color:#bbf7d0;
            background: rgba(34,197,94,.08);
        }
        .pill-declined{
            border-color: rgba(251,113,133,.40);
            color:#fecdd3;
            background: rgba(251,113,133,.08);
        }
        .pill-pending{
            border-color: rgba(251,191,36,.40);
            color:#fde68a;
            background: rgba(251,191,36,.08);
        }

        .meta{
            margin-top:10px;
            display:flex;
            flex-wrap:wrap;
            gap:10px;
        }
        .chip{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:8px 10px;
            border-radius:999px;
            border:1px solid rgba(148,163,184,.18);
            background: rgba(15,23,42,.35);
            color: var(--muted);
            font-size:.82rem;
        }
        .chip strong{ color:var(--text); font-weight:700; }

        .desc{
            margin-top:10px;
            color:rgba(226,232,240,.92);
            font-size:.92rem;
            line-height:1.55;
            background: rgba(2,6,23,.22);
            border:1px dashed rgba(148,163,184,.20);
            padding:10px 12px;
            border-radius:14px;
        }

        /* Forms */
        .grid{
            display:grid;
            grid-template-columns: 1.2fr .8fr;
            gap:12px;
            margin-top:12px;
        }
        @media (max-width: 980px){
            .grid{ grid-template-columns: 1fr; }
        }

        .section{
            background: rgba(2,6,23,.22);
            border:1px solid rgba(148,163,184,.14);
            border-radius: 16px;
            padding: 12px;
        }
        .section h3{
            margin:0 0 10px;
            font-size:.92rem;
            color:rgba(226,232,240,.95);
            display:flex;
            align-items:center;
            gap:8px;
        }
        label{
            display:block;
            margin:0 0 6px;
            color: var(--muted);
            font-size:.82rem;
        }
        input, textarea, select{
            width:100%;
            padding:11px 12px;
            border-radius: 14px;
            border:1px solid rgba(148,163,184,.22);
            background: rgba(15,23,42,.55);
            color: var(--text);
            outline:none;
            transition: .15s ease;
        }
        input::placeholder, textarea::placeholder{ color: rgba(148,163,184,.75); }
        input:focus, textarea:focus, select:focus{
            border-color: rgba(56,189,248,.55);
            box-shadow: 0 0 0 4px rgba(56,189,248,.12);
        }
        textarea{ min-height: 92px; resize: vertical; }

        .toolbar{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            align-items:center;
            justify-content:space-between;
            padding:10px;
            border-radius:14px;
            border:1px solid rgba(148,163,184,.16);
            background: rgba(2,6,23,.30);
            position: sticky;
            top: 10px;
            z-index: 2;
            backdrop-filter: blur(12px);
        }

        .count{
            font-size:.82rem;
            color: var(--muted);
            display:flex;
            align-items:center;
            gap:8px;
        }

        .product-list{
            margin-top:10px;
            max-height: 240px;
            overflow:auto;
            border:1px solid rgba(148,163,184,.14);
            border-radius: 14px;
            padding:8px;
            background: rgba(15,23,42,.35);
        }
        .product-item{
            display:flex;
            align-items:center;
            gap:10px;
            padding:10px 10px;
            border-radius: 12px;
            border:1px solid transparent;
            transition:.12s ease;
            user-select:none;
        }
        .product-item:hover{
            background: rgba(56,189,248,.06);
            border-color: rgba(56,189,248,.12);
        }
        .product-item input{
            width:18px;
            height:18px;
            accent-color: var(--accent);
            margin:0;
        }
        .product-item span{
            color: rgba(226,232,240,.95);
            font-size:.92rem;
        }

        .btnrow{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:10px;
        }

        .btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            padding:11px 14px;
            border-radius: 14px;
            border:1px solid transparent;
            cursor:pointer;
            font-weight:800;
            letter-spacing:.2px;
            transition:.16s ease;
            text-decoration:none;
            user-select:none;
        }
        .btn:active{ transform: translateY(1px); }

        .btn-approve{
            color:#021018;
            background: linear-gradient(135deg, rgba(34,197,94,1), rgba(56,189,248,.95));
            box-shadow: 0 16px 30px rgba(34,197,94,.12);
        }
        .btn-approve:hover{ filter: brightness(1.04); }

        .btn-decline{
            color:#fecdd3;
            border-color: rgba(251,113,133,.50);
            background: rgba(251,113,133,.08);
        }
        .btn-decline:hover{
            border-color: rgba(251,113,133,.70);
            box-shadow: 0 14px 26px rgba(0,0,0,.25);
        }

        .btn-ghost{
            color: var(--text);
            border-color: rgba(148,163,184,.25);
            background: rgba(2,6,23,.25);
        }
        .btn-ghost:hover{ border-color: rgba(56,189,248,.30); }

        .small{
            font-size:.82rem;
            color: var(--muted);
            margin-top:10px;
            display:flex;
            gap:12px;
            flex-wrap:wrap;
        }
        .small span{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:6px 10px;
            border-radius:999px;
            border:1px solid rgba(148,163,184,.14);
            background: rgba(2,6,23,.18);
        }

        .empty{
            padding: 22px 14px;
            text-align:center;
            color: var(--muted);
        }
        .empty i{ font-size:1.5rem; opacity:.85; display:block; margin-bottom:8px; }
    </style>
</head>

<body>
<div class="fx"></div>

<div class="container">

    <div class="topbar">
        <div class="brand">
            <div class="logo"><i class="fa-solid fa-ticket"></i></div>
            <div>
                <h1>Coupon Requests</h1>
                <div class="subtitle">Approve coupon offers and choose which of your active products are eligible.</div>
            </div>
        </div>

        <div class="actions">
            <a class="link-btn" href="seller_dashboard.php">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if ($flashMsg !== ''): ?>
        <div class="flash success">
            <i class="fa-solid fa-circle-check"></i>
            <div><?php echo htmlspecialchars($flashMsg); ?></div>
        </div>
    <?php endif; ?>
    <?php if ($flashErr !== ''): ?>
        <div class="flash error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><?php echo htmlspecialchars($flashErr); ?></div>
        </div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-head">
            <h2><i class="fa-solid fa-inbox"></i> Pending & Previous Requests</h2>
            <div class="pill-mini"><span class="dot"></span> Latest first</div>
        </div>

        <div class="list">
            <?php if (empty($requests)): ?>
                <div class="empty">
                    <i class="fa-regular fa-face-smile"></i>
                    No coupon requests at the moment.
                </div>
            <?php else: ?>

                <?php foreach ($requests as $req): ?>
                    <?php
                        $pillClass = $req['status'] === 'approved'
                            ? 'pill-approved'
                            : ($req['status'] === 'declined' ? 'pill-declined' : 'pill-pending');

                        $statusIcon = $req['status'] === 'approved'
                            ? 'fa-circle-check'
                            : ($req['status'] === 'declined' ? 'fa-circle-xmark' : 'fa-clock');
                    ?>

                    <div class="req">
                        <div class="req-top">
                            <div>
                                <div class="code">
                                    <div class="code-badge">
                                        <i class="fa-solid fa-tag"></i>
                                        <?php echo htmlspecialchars($req['code']); ?>
                                    </div>
                                    <div>
                                        <p class="title"><?php echo htmlspecialchars($req['title'] ?: 'Untitled coupon'); ?></p>
                                        <p class="sub">Request ID: #<?php echo (int)$req['request_id']; ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="status-pill <?php echo $pillClass; ?>">
                                <i class="fa-solid <?php echo $statusIcon; ?>"></i>
                                <?php echo htmlspecialchars($req['status']); ?>
                            </div>
                        </div>

                        <div class="meta">
                            <div class="chip">
                                <i class="fa-solid fa-percent"></i>
                                <span>Type: <strong><?php echo htmlspecialchars($req['discount_type']); ?></strong></span>
                            </div>
                            <div class="chip">
                                <i class="fa-solid fa-bolt"></i>
                                <span>Value: <strong><?php echo number_format((float)$req['discount_value'], 2); ?></strong></span>
                            </div>
                            <div class="chip">
                                <i class="fa-solid fa-bag-shopping"></i>
                                <span>Min: <strong><?php echo number_format((float)$req['min_purchase'], 2); ?> BDT</strong></span>
                            </div>
                            <div class="chip">
                                <i class="fa-solid fa-coins"></i>
                                <span>Price: <strong><?php echo number_format((float)$req['price'], 2); ?> BDT</strong></span>
                            </div>
                        </div>

                        <?php if (!empty($req['description'])): ?>
                            <div class="desc"><?php echo htmlspecialchars($req['description']); ?></div>
                        <?php endif; ?>

                        <?php if ($req['status'] === 'pending'): ?>

                            <div class="grid">
                                <!-- Approve -->
                                <div class="section">
                                    <h3><i class="fa-solid fa-wand-magic-sparkles"></i> Approve & Apply Coupon</h3>

                                    <form method="POST" action="../actions/seller_coupon_response.php" class="approve-form">
                                        <input type="hidden" name="action" value="approve" />
                                        <input type="hidden" name="coupon_id" value="<?php echo (int)$req['coupon_id']; ?>" />

                                        <div class="toolbar">
                                            <div style="flex:1; min-width:220px;">
                                                <label style="margin:0 0 6px;">Filter products</label>
                                                <input type="text" class="product-filter" placeholder="Search your products…">
                                            </div>

                                            <div class="count">
                                                <i class="fa-solid fa-list-check"></i>
                                                <span><span class="selected-count">0</span> selected</span>
                                            </div>

                                            <button type="button" class="btn btn-ghost select-all-btn">
                                                <i class="fa-solid fa-check-double"></i> Select all
                                            </button>
                                            <button type="button" class="btn btn-ghost clear-all-btn">
                                                <i class="fa-solid fa-eraser"></i> Clear
                                            </button>
                                        </div>

                                        <div style="margin-top:10px;">
                                            <label>Select products for this coupon *</label>
                                            <div class="product-list">
                                                <?php foreach ($products as $product): ?>
                                                    <label class="product-item">
                                                        <input type="checkbox" class="product-check" name="product_ids[]" value="<?php echo (int)$product['product_id']; ?>">
                                                        <span><?php echo htmlspecialchars($product['name']); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div style="margin-top:10px;">
                                            <label>Note to Admin (optional)</label>
                                            <textarea name="response_note" placeholder="Short note for admin (optional)…"></textarea>
                                        </div>

                                        <div class="btnrow">
                                            <button type="submit" class="btn btn-approve">
                                                <i class="fa-solid fa-circle-check"></i> Approve & Apply
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Decline -->
                                <div class="section">
                                    <h3><i class="fa-solid fa-ban"></i> Decline Request</h3>

                                    <form method="POST" action="../actions/seller_coupon_response.php">
                                        <input type="hidden" name="action" value="decline" />
                                        <input type="hidden" name="coupon_id" value="<?php echo (int)$req['coupon_id']; ?>" />

                                        <div>
                                            <label>Decline reason (optional)</label>
                                            <input type="text" name="response_note" placeholder="e.g., Not suitable for my products">
                                        </div>

                                        <div class="btnrow" style="margin-top:12px;">
                                            <button type="submit" class="btn btn-decline">
                                                <i class="fa-solid fa-circle-xmark"></i> Decline
                                            </button>
                                        </div>

                                        <div class="small">
                                            <span><i class="fa-solid fa-shield-halved"></i> You can decline without a reason.</span>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        <?php else: ?>

                            <div class="small">
                                <?php if (!empty($req['response_note'])): ?>
                                    <span><i class="fa-solid fa-note-sticky"></i> Note: <?php echo htmlspecialchars($req['response_note']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($req['responded_at'])): ?>
                                    <span><i class="fa-solid fa-clock"></i> Responded: <?php echo htmlspecialchars($req['responded_at']); ?></span>
                                <?php endif; ?>
                            </div>

                        <?php endif; ?>
                    </div>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    // Per-request form behavior (filter + counts + select all/clear)
    document.querySelectorAll('.approve-form').forEach(form => {
        const filter = form.querySelector('.product-filter');
        const list = form.querySelector('.product-list');
        const checks = Array.from(form.querySelectorAll('.product-check'));
        const selectedCountEl = form.querySelector('.selected-count');
        const selectAllBtn = form.querySelector('.select-all-btn');
        const clearAllBtn = form.querySelector('.clear-all-btn');

        function updateCount(){
            const count = checks.filter(c => c.checked).length;
            selectedCountEl.textContent = String(count);
        }

        function applyFilter(){
            const q = (filter.value || '').toLowerCase().trim();
            list.querySelectorAll('.product-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(q) ? 'flex' : 'none';
            });
        }

        filter?.addEventListener('input', applyFilter);

        checks.forEach(c => c.addEventListener('change', updateCount));

        selectAllBtn?.addEventListener('click', () => {
            // select only visible rows (nice UX)
            list.querySelectorAll('.product-item').forEach(item => {
                if (item.style.display === 'none') return;
                const cb = item.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = true;
            });
            updateCount();
        });

        clearAllBtn?.addEventListener('click', () => {
            checks.forEach(c => c.checked = false);
            updateCount();
        });

        // Initial
        updateCount();
    });
})();
</script>

</body>
</html>
