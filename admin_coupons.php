<?php
header('Location: /QuickMart/admin_folder/admin_coupons.php');
exit;
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
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupons.php';

$adminSessionOk = !empty($_SESSION['admin_logged_in']);
if (!$adminSessionOk) {
    require_role('admin');
}
ensure_coupon_tables();

$coupons = db_fetch_all("SELECT * FROM coupons ORDER BY created_at DESC");
$responses = db_fetch_all(
    "SELECT csr.request_id, csr.status, csr.responded_at, csr.response_note,
            c.code, c.title,
            u.full_name AS seller_name
     FROM coupon_seller_requests csr
     INNER JOIN coupons c ON c.coupon_id = csr.coupon_id
     INNER JOIN users u ON u.user_id = csr.seller_id
     ORDER BY csr.responded_at DESC, csr.created_at DESC
     LIMIT 50"
);

$sales = db_fetch_all(
    "SELECT cp.purchase_id, cp.price, cp.created_at,
            c.code,
            u.full_name AS buyer_name
     FROM coupon_purchases cp
     INNER JOIN coupons c ON c.coupon_id = cp.coupon_id
     INNER JOIN users u ON u.user_id = cp.buyer_id
     ORDER BY cp.created_at DESC
     LIMIT 30"
);

$couponStats = [];
foreach ($coupons as $coupon) {
    $stats = db_fetch(
        "SELECT
            SUM(status = 'pending') AS pending_count,
            SUM(status = 'approved') AS approved_count,
            SUM(status = 'declined') AS declined_count
         FROM coupon_seller_requests
         WHERE coupon_id = ?",
        [$coupon['coupon_id']]
    );
    $couponStats[$coupon['coupon_id']] = [
        'pending' => (int)($stats['pending_count'] ?? 0),
        'approved' => (int)($stats['approved_count'] ?? 0),
        'declined' => (int)($stats['declined_count'] ?? 0)
    ];
}

$flashMsg = $_GET['msg'] ?? '';
$flashErr = $_GET['err'] ?? '';

function h(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function fmt_dt(?string $dt): string {
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $ts ? date('M d, Y · h:i A', $ts) : '—';
}
function money($v): string { return number_format((float)$v, 2); }
function pill_class(string $status): string {
    return $status === 'approved' ? 'pill ok' : ($status === 'declined' ? 'pill bad' : 'pill warn');
}
function published_pill(bool $pub): string { return $pub ? 'pill ok' : 'pill warn'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Admin Coupons | QuickMart</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root{
            /* Brand */
            --primary:#8b5cf6;
            --secondary:#ec4899;

            /* Neutrals */
            --bg:#f8fafc;
            --text:#0f172a;
            --muted:#475569;
            --muted2:#64748b;
            --stroke:rgba(15,23,42,.10);

            /* Glass */
            --glass:rgba(255,255,255,.62);
            --glass2:rgba(255,255,255,.78);

            /* Status */
            --ok:#16a34a;
            --okbg:rgba(22,163,74,.10);
            --warn:#ca8a04;
            --warnbg:rgba(202,138,4,.12);
            --bad:#dc2626;
            --badbg:rgba(220,38,38,.10);

            /* Shadow */
            --sh-sm:0 10px 30px rgba(2,6,23,.08);
            --sh-md:0 18px 55px rgba(2,6,23,.12);

            /* Layout */
            --maxw:1200px;
            --r-lg:18px;
            --r-xl:24px;
        }

        *{box-sizing:border-box}
        html,body{height:100%}
        body{
            margin:0;
            font-family:"Baloo Da 2", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            color:var(--text);
            background: linear-gradient(135deg,
                rgba(59,130,246,.10) 0%,
                rgba(139,92,246,.10) 45%,
                rgba(236,72,153,.08) 100%);
        }

        /* Gradient canvas */
        .page{min-height:100vh; position:relative; overflow-x:clip}
        .bg{
            position:fixed; inset:0; z-index:-2;
            background: linear-gradient(135deg,
                rgba(59,130,246,.16) 0%,
                rgba(139,92,246,.16) 45%,
                rgba(236,72,153,.14) 100%);
        }
        .blob{
            position:absolute; z-index:-1; pointer-events:none;
            filter: blur(44px); opacity:.8; border-radius:999px;
            transform: translateZ(0);
        }
        .blob.one{ width:520px; height:520px; left:-220px; top:110px;
            background: radial-gradient(circle at 30% 30%, rgba(59,130,246,.45), rgba(139,92,246,.16) 60%, transparent 72%); }
        .blob.two{ width:560px; height:560px; right:-260px; top:240px;
            background: radial-gradient(circle at 40% 40%, rgba(139,92,246,.42), rgba(236,72,153,.16) 60%, transparent 72%); }
        .blob.three{ width:520px; height:520px; left:18%; bottom:-260px;
            background: radial-gradient(circle at 40% 40%, rgba(236,72,153,.40), rgba(59,130,246,.12) 60%, transparent 72%); }

        /* Topbar */
        .topbar{
            position:sticky; top:0; z-index:40;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            background: rgba(255,255,255,.62);
            border-bottom:1px solid var(--stroke);
        }
        .topbar-inner{
            max-width:var(--maxw);
            margin:0 auto;
            padding:14px 18px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
        }
        .brand{
            display:flex; align-items:center; gap:10px;
            text-decoration:none; color:inherit;
        }
        .brand-badge{
            width:42px; height:42px; border-radius:14px;
            background: linear-gradient(135deg, rgba(139,92,246,1), rgba(236,72,153,1));
            box-shadow: 0 18px 40px rgba(139,92,246,.22);
            display:grid; place-items:center;
            color:#fff; font-weight:900;
            letter-spacing:-.2px;
        }
        .brand-title{ font-weight:900; letter-spacing:-.3px; font-size:18px; line-height:1.05; }
        .brand-sub{ display:block; font-size:12px; color:var(--muted2); font-weight:700; margin-top:2px; }

        .nav{
            display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; align-items:center;
        }
        .nav a{
            text-decoration:none; color:var(--muted);
            font-weight:900; font-size:14px;
            padding:10px 12px; border-radius:999px;
            transition: transform .12s ease, background .12s ease, color .12s ease;
        }
        .nav a:hover{ background: rgba(139,92,246,.12); color:var(--text); transform: translateY(-1px); }
        .nav a:active{ transform: translateY(0) scale(.98); background: rgba(236,72,153,.12); }
        .nav a.active{
            color:var(--text);
            background: linear-gradient(135deg, rgba(139,92,246,.16), rgba(236,72,153,.14));
            border:1px solid rgba(139,92,246,.18);
        }

        /* Layout */
        .container{ max-width:var(--maxw); margin:0 auto; padding:22px 18px 54px; }
        .hero{
            display:flex; align-items:flex-start; justify-content:space-between;
            gap:14px; flex-wrap:wrap;
            margin-top:8px;
        }
        .hero-left{ min-width: 260px; flex: 1; }
        .pill{
            display:inline-flex; align-items:center; gap:8px;
            padding:8px 12px; border-radius:999px;
            border:1px solid rgba(139,92,246,.20);
            background: rgba(255,255,255,.55);
            box-shadow: 0 12px 28px rgba(2,6,23,.06);
            font-weight:900; font-size:12px; color:var(--primary); letter-spacing:.35px;
        }
        .hero h1{
            margin:12px 0 6px;
            font-size: clamp(28px, 3.8vw, 44px);
            line-height: 1.05;
            letter-spacing: -.6px;
            font-weight: 900;
        }
        .hero p{ margin: 6px 0 0; color:var(--muted); font-weight:700; }
        .hero-actions{
            display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:flex-end;
        }

        /* Buttons */
        .btn{
            display:inline-flex; align-items:center; justify-content:center; gap:10px;
            border-radius: 14px;
            padding: 12px 14px;
            border:1px solid rgba(139,92,246,.35);
            background: linear-gradient(135deg, rgba(139,92,246,1), rgba(236,72,153,1));
            color:#fff;
            font-weight:900;
            letter-spacing:.2px;
            cursor:pointer;
            text-decoration:none;
            box-shadow: 0 18px 40px rgba(139,92,246,.22);
            transition: transform .12s ease, filter .12s ease, box-shadow .12s ease;
        }
        .btn:hover{ filter:brightness(1.03); box-shadow:0 22px 54px rgba(236,72,153,.20); transform: translateY(-1px); }
        .btn:active{ transform: translateY(0) scale(.99); filter:brightness(.98); }
        .btn:focus{ outline:none; box-shadow:0 0 0 4px rgba(139,92,246,.20), 0 18px 40px rgba(139,92,246,.22); }

        .btn-ghost{
            background: rgba(255,255,255,.58);
            color: var(--text);
            border:1px solid rgba(15,23,42,.10);
            box-shadow: var(--sh-sm);
        }
        .btn-ghost:hover{ box-shadow: var(--sh-md); }

        /* Dashboard stats */
        .stats{
            margin-top:16px;
            display:grid;
            gap:14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        @media(min-width: 920px){
            .stats{ grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        .stat{
            border-radius: var(--r-xl);
            border: 1px solid rgba(15,23,42,.10);
            background: linear-gradient(180deg, var(--glass2), var(--glass));
            box-shadow: var(--sh-sm);
            padding: 14px;
            display:flex; gap:12px; align-items:center;
        }
        .stat .ico{
            width:44px; height:44px; border-radius:16px;
            display:grid; place-items:center;
            background: rgba(139,92,246,.12);
            border:1px solid rgba(139,92,246,.18);
            color: var(--primary);
            flex: 0 0 auto;
        }
        .stat .k{ color: var(--muted2); font-weight:900; font-size:12px; letter-spacing:.25px; }
        .stat .v{ font-weight: 900; font-size:20px; line-height:1.1; margin-top:2px; }

        /* Main grid */
        .grid{
            margin-top:18px;
            display:grid;
            gap:16px;
            grid-template-columns: 1fr;
            align-items:start;
        }
        @media(min-width: 920px){
            .grid{ grid-template-columns: 1.2fr .9fr; }
        }

        /* Cards */
        .card{
            border-radius: var(--r-xl);
            border:1px solid rgba(15,23,42,.10);
            background: linear-gradient(180deg, var(--glass2), var(--glass));
            box-shadow: var(--sh-sm);
            overflow:hidden;
        }
        .card:hover{ box-shadow: var(--sh-md); }
        .card-head{
            padding:14px 14px 12px;
            border-bottom:1px solid rgba(15,23,42,.08);
            display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
        }
        .card-title{
            display:flex; align-items:center; gap:10px;
            font-weight: 900;
            letter-spacing: -.2px;
            font-size: 18px;
            margin:0;
        }
        .card-title .dot{
            width:10px; height:10px; border-radius:999px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 10px 22px rgba(139,92,246,.25);
        }
        .card-sub{
            color: var(--muted2);
            font-weight: 700;
            font-size: 13px;
            margin: 6px 0 0;
        }
        .card-body{ padding: 14px; }

        /* Forms */
        .form-grid{
            display:grid;
            gap:12px;
            grid-template-columns: 1fr;
        }
        @media(min-width: 720px){
            .form-grid.cols-2{ grid-template-columns: repeat(2, minmax(0,1fr)); }
            .form-grid.cols-3{ grid-template-columns: repeat(3, minmax(0,1fr)); }
            .form-grid.cols-4{ grid-template-columns: repeat(4, minmax(0,1fr)); }
        }

        label{
            display:block;
            font-size: 12px;
            color: var(--muted2);
            font-weight: 900;
            letter-spacing: .25px;
            margin: 0 0 6px;
        }
        .field{ display:flex; flex-direction:column; gap:0; }
        input, select, textarea{
            width:100%;
            padding: 12px 12px;
            border-radius: 14px;
            border: 1px solid rgba(15,23,42,.12);
            background: rgba(255,255,255,.62);
            color: var(--text);
            font-weight: 800;
            outline: none;
            transition: box-shadow .12s ease, border-color .12s ease, transform .12s ease;
        }
        textarea{ min-height: 96px; resize: vertical; font-weight: 700; }
        input::placeholder, textarea::placeholder{ color: rgba(100,116,139,.75); font-weight: 700; }
        input:focus, select:focus, textarea:focus{
            border-color: rgba(139,92,246,.40);
            box-shadow: 0 0 0 4px rgba(139,92,246,.14);
        }

        .toggles{
            display:flex; gap:12px; flex-wrap:wrap; align-items:center; justify-content:space-between;
            margin-top: 12px;
        }
        .switch{
            display:inline-flex; align-items:center; gap:10px;
            padding: 10px 12px;
            border-radius: 14px;
            border:1px solid rgba(15,23,42,.10);
            background: rgba(255,255,255,.56);
            box-shadow: 0 10px 26px rgba(2,6,23,.06);
            font-weight: 900;
            color: var(--text);
        }
        .switch input{ width:auto; transform: translateY(1px); }

        /* Lists */
        .list{ display:grid; gap:12px; }
        .item{
            border-radius: var(--r-lg);
            border:1px solid rgba(15,23,42,.10);
            background: rgba(255,255,255,.60);
            padding: 12px;
            box-shadow: 0 10px 26px rgba(2,6,23,.06);
            transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
        }
        .item:hover{
            transform: translateY(-2px);
            box-shadow: 0 16px 40px rgba(2,6,23,.10);
            border-color: rgba(139,92,246,.22);
        }
        .item-top{
            display:flex; align-items:flex-start; justify-content:space-between;
            gap:12px; flex-wrap:wrap;
        }
        .item-title{
            margin:0;
            font-weight: 900;
            letter-spacing: -.2px;
            font-size: 16px;
        }
        .item-sub{ margin: 2px 0 0; color: var(--muted2); font-weight: 800; font-size: 13px; }
        .meta{
            margin-top: 10px;
            display:flex;
            gap: 8px;
            flex-wrap: wrap;
            color: var(--muted);
            font-weight: 800;
            font-size: 12.5px;
        }
        .chip{
            display:inline-flex; align-items:center; gap:8px;
            padding: 8px 10px;
            border-radius: 999px;
            border:1px solid rgba(15,23,42,.10);
            background: rgba(255,255,255,.55);
            white-space: nowrap;
        }

        /* Pills */
        .pill{
            display:inline-flex; align-items:center; gap:8px;
            padding: 8px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 900;
            border: 1px solid rgba(15,23,42,.10);
            background: rgba(255,255,255,.55);
            color: var(--muted);
            white-space: nowrap;
        }
        .pill.ok{ color: var(--ok); background: var(--okbg); border-color: rgba(22,163,74,.22); }
        .pill.warn{ color: var(--warn); background: var(--warnbg); border-color: rgba(202,138,4,.22); }
        .pill.bad{ color: var(--bad); background: var(--badbg); border-color: rgba(220,38,38,.22); }

        /* Scroll areas */
        .scroll{
            max-height: 520px;
            overflow:auto;
            padding-right: 6px;
        }
        .scroll::-webkit-scrollbar{ width: 10px; }
        .scroll::-webkit-scrollbar-thumb{
            background: rgba(100,116,139,.25);
            border-radius: 999px;
            border: 3px solid rgba(255,255,255,.55);
        }

        /* Flash */
        .flash{
            margin-top: 14px;
            border-radius: var(--r-xl);
            padding: 12px 14px;
            border: 1px solid rgba(15,23,42,.10);
            background: rgba(255,255,255,.62);
            box-shadow: var(--sh-sm);
            font-weight: 900;
        }
        .flash.success{ border-color: rgba(22,163,74,.22); background: rgba(22,163,74,.10); color: #166534; }
        .flash.error{ border-color: rgba(220,38,38,.22); background: rgba(220,38,38,.10); color: #991b1b; }

        /* Empty state */
        .empty{
            border-radius: var(--r-lg);
            border: 1px dashed rgba(15,23,42,.18);
            background: rgba(255,255,255,.55);
            padding: 14px;
            color: var(--muted);
            font-weight: 800;
        }

        /* Small */
        @media (max-width: 420px){
            .brand-sub{ display:none; }
        }
    </style>
</head>

<body>
<div class="page">
    <div class="bg"></div>
    <div class="blob one"></div>
    <div class="blob two"></div>
    <div class="blob three"></div>

    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="admin.php" aria-label="Back to admin dashboard">
                <div class="brand-badge">QM</div>
                <div>
                    <div class="brand-title">Coupon Center</div>
                    <span class="brand-sub">Admin tools · QuickMart</span>
                </div>
            </a>

            <nav class="nav" aria-label="Admin navigation">
                <a href="admin.php">Dashboard</a>
                <a href="admin_coupons.php" class="active">Coupons</a>
                <a href="index.php">Store</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="hero">
            <div class="hero-left">
                <span class="pill">
                    <!-- icon -->
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M21 10V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v3a2 2 0 0 1 0 4v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a2 2 0 0 1 0-4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        <path d="M13 5v14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-dasharray="2.5 3.5"/>
                    </svg>
                    Manage Coupons
                </span>
                <h1>Coupons that feel premium ✨</h1>
                <p>Create, publish, and track seller approvals & recent coupon sales in one clean dashboard.</p>
            </div>

            <div class="hero-actions">
                <a class="btn btn-ghost" href="admin.php">
                    <!-- back -->
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back to Admin
                </a>
                <a class="btn" href="#create">
                    <!-- plus -->
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                    </svg>
                    New Coupon
                </a>
            </div>
        </section>

        <?php
            $totalCoupons = count($coupons);
            $publishedCount = 0;
            foreach ($coupons as $c) { if (!empty($c['is_published'])) $publishedCount++; }
            $draftCount = $totalCoupons - $publishedCount;

            $totalResp = count($responses);
            $approvedResp = 0; $pendingResp = 0; $declinedResp = 0;
            foreach ($responses as $r) {
                if (($r['status'] ?? '') === 'approved') $approvedResp++;
                elseif (($r['status'] ?? '') === 'declined') $declinedResp++;
                else $pendingResp++;
            }
        ?>

        <section class="stats" aria-label="Summary stats">
            <div class="stat">
                <div class="ico" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M6 7h12v14H6V7z" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M9 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </div>
                <div>
                    <div class="k">Total coupons</div>
                    <div class="v"><?php echo (int)$totalCoupons; ?></div>
                </div>
            </div>

            <div class="stat">
                <div class="ico" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <div class="k">Published</div>
                    <div class="v"><?php echo (int)$publishedCount; ?></div>
                </div>
            </div>

            <div class="stat">
                <div class="ico" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2l2.8 6.9 7.2.6-5.5 4.7 1.7 7.1L12 17.9 5.8 21.3l1.7-7.1L2 9.5l7.2-.6L12 2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <div class="k">Seller responses</div>
                    <div class="v"><?php echo (int)$totalResp; ?></div>
                </div>
            </div>

            <div class="stat">
                <div class="ico" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M3 3v18h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M7 15l3-3 3 2 5-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <div class="k">Recent sales</div>
                    <div class="v"><?php echo (int)count($sales); ?></div>
                </div>
            </div>
        </section>

        <?php if ($flashMsg !== ''): ?>
            <div class="flash success"><?php echo h($flashMsg); ?></div>
        <?php endif; ?>
        <?php if ($flashErr !== ''): ?>
            <div class="flash error"><?php echo h($flashErr); ?></div>
        <?php endif; ?>

        <section class="grid">
            <!-- LEFT: Create + All Coupons -->
            <div style="display:grid; gap:16px;">
                <!-- Create -->
                <section id="create" class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title"><span class="dot"></span> Create Coupon</h2>
                            <p class="card-sub">Build a coupon and optionally publish it immediately.</p>
                        </div>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="actions/admin_coupon_action.php">
                            <input type="hidden" name="action" value="create" />

                            <div class="form-grid cols-2">
                                <div class="field">
                                    <label>Coupon Code *</label>
                                    <input type="text" name="code" placeholder="SAVE10" required />
                                </div>
                                <div class="field">
                                    <label>Title</label>
                                    <input type="text" name="title" placeholder="Summer Saver" />
                                </div>
                            </div>

                            <div class="form-grid" style="margin-top:12px;">
                                <div class="field">
                                    <label>Description</label>
                                    <textarea name="description" placeholder="Short description shown to sellers/buyers."></textarea>
                                </div>
                            </div>

                            <div class="form-grid cols-4" style="margin-top:12px;">
                                <div class="field">
                                    <label>Discount Type</label>
                                    <select name="discount_type">
                                        <option value="percent">Percent</option>
                                        <option value="fixed">Fixed</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label>Discount Value *</label>
                                    <input type="number" step="0.01" name="discount_value" required />
                                </div>
                                <div class="field">
                                    <label>Min Purchase</label>
                                    <input type="number" step="0.01" name="min_purchase" value="0" />
                                </div>
                                <div class="field">
                                    <label>Max Discount</label>
                                    <input type="number" step="0.01" name="max_discount" />
                                </div>
                            </div>

                            <div class="form-grid cols-3" style="margin-top:12px;">
                                <div class="field">
                                    <label>Coupon Price *</label>
                                    <input type="number" step="0.01" name="price" value="0" />
                                </div>
                                <div class="field">
                                    <label>Uses Per Buyer</label>
                                    <input type="number" step="1" min="1" name="usage_limit" value="1" />
                                </div>
                                <div class="field">
                                    <label>Starts At</label>
                                    <input type="datetime-local" name="starts_at" />
                                </div>
                                <div class="field">
                                    <label>Ends At</label>
                                    <input type="datetime-local" name="ends_at" />
                                </div>
                            </div>

                            <div class="toggles">
                                <label class="switch">
                                    <input type="checkbox" name="is_active" checked />
                                    Active
                                </label>
                                <label class="switch">
                                    <input type="checkbox" name="publish_now" />
                                    Publish Now
                                </label>
                                <button type="submit" class="btn">
                                    <!-- ticket -->
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M21 10V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v3a2 2 0 0 1 0 4v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a2 2 0 0 1 0-4z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        <path d="M13 5v14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-dasharray="2.5 3.5"/>
                                    </svg>
                                    Create Coupon
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- All coupons -->
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title"><span class="dot"></span> All Coupons</h2>
                            <p class="card-sub">Publish/unpublish and see seller approval stats at a glance.</p>
                        </div>
                        <div class="pill <?php echo $draftCount > 0 ? 'warn' : 'ok'; ?>">
                            <?php echo (int)$publishedCount; ?> published · <?php echo (int)$draftCount; ?> draft
                        </div>
                    </div>

                    <div class="card-body">
                        <?php if (empty($coupons)): ?>
                            <div class="empty">No coupons created yet.</div>
                        <?php else: ?>
                            <div class="list">
                                <?php foreach ($coupons as $coupon): ?>
                                    <?php
                                        $stats = $couponStats[$coupon['coupon_id']] ?? ['pending' => 0, 'approved' => 0, 'declined' => 0];
                                        $isPub = !empty($coupon['is_published']);
                                    ?>
                                    <div class="item">
                                        <div class="item-top">
                                            <div>
                                                <h3 class="item-title"><?php echo h($coupon['code']); ?></h3>
                                                <div class="item-sub"><?php echo h($coupon['title'] ?: 'No title'); ?></div>
                                            </div>

                                            <div class="<?php echo published_pill($isPub); ?>">
                                                <?php echo $isPub ? 'Published' : 'Draft'; ?>
                                            </div>
                                        </div>

                                        <div class="meta" aria-label="Coupon details">
                                            <span class="chip">Discount: <?php echo h($coupon['discount_type']); ?> · <?php echo money($coupon['discount_value']); ?></span>
                                            <span class="chip">Price: <?php echo money($coupon['price']); ?> BDT</span>
                                            <span class="chip">Min: <?php echo money($coupon['min_purchase']); ?> BDT</span>
                                            <span class="chip">Uses: <?php echo (int)($coupon['usage_limit'] ?? 1); ?></span>
                                        </div>

                                        <div class="meta" aria-label="Seller approvals">
                                            <span class="pill ok">Approved: <?php echo (int)$stats['approved']; ?></span>
                                            <span class="pill warn">Pending: <?php echo (int)$stats['pending']; ?></span>
                                            <span class="pill bad">Declined: <?php echo (int)$stats['declined']; ?></span>
                                        </div>

                                        <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
                                            <form method="POST" action="actions/admin_coupon_action.php">
                                                <input type="hidden" name="coupon_id" value="<?php echo (int)$coupon['coupon_id']; ?>" />
                                                <?php if (!$isPub): ?>
                                                    <input type="hidden" name="action" value="publish" />
                                                    <button type="submit" class="btn">Publish</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="action" value="unpublish" />
                                                    <button type="submit" class="btn btn-ghost">Unpublish</button>
                                                <?php endif; ?>
                                            </form>
                                            <form method="POST" action="actions/admin_coupon_action.php" onsubmit="return confirm('Delete this coupon and all related approvals?');">
                                                <input type="hidden" name="action" value="delete" />
                                                <input type="hidden" name="coupon_id" value="<?php echo (int)$coupon['coupon_id']; ?>" />
                                                <button type="submit" class="btn btn-ghost">Delete Coupon</button>
                                            </form>

                                            <div class="pill" title="Created at">
                                                Created: <?php echo h(fmt_dt($coupon['created_at'] ?? null)); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- RIGHT: Seller responses + Sales -->
            <div style="display:grid; gap:16px;">
                <!-- Responses -->
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title"><span class="dot"></span> Seller Responses</h2>
                            <p class="card-sub">Latest approvals/declines from sellers (last 50).</p>
                        </div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <span class="pill ok"><?php echo (int)$approvedResp; ?> approved</span>
                            <span class="pill warn"><?php echo (int)$pendingResp; ?> pending</span>
                            <span class="pill bad"><?php echo (int)$declinedResp; ?> declined</span>
                        </div>
                    </div>

                    <div class="card-body">
                        <?php if (empty($responses)): ?>
                            <div class="empty">No seller responses yet.</div>
                        <?php else: ?>
                            <div class="scroll list" aria-label="Seller response list">
                                <?php foreach ($responses as $resp): ?>
                                    <div class="item">
                                        <div class="item-top">
                                            <div>
                                                <h3 class="item-title"><?php echo h($resp['seller_name']); ?></h3>
                                                <div class="item-sub"><?php echo h(($resp['code'] ?? '') . ' · ' . ($resp['title'] ?: 'Untitled')); ?></div>
                                            </div>
                                            <div class="<?php echo pill_class((string)($resp['status'] ?? 'pending')); ?>">
                                                <?php echo h($resp['status'] ?? 'pending'); ?>
                                            </div>
                                        </div>

                                        <?php if (!empty($resp['response_note'])): ?>
                                            <div style="margin-top:10px; color:var(--muted); font-weight:800; line-height:1.5;">
                                                “<?php echo h($resp['response_note']); ?>”
                                            </div>
                                        <?php else: ?>
                                            <div style="margin-top:10px; color:var(--muted2); font-weight:800;">
                                                No note provided.
                                            </div>
                                        <?php endif; ?>

                                        <div class="meta">
                                            <span class="chip">Responded: <?php echo h(fmt_dt($resp['responded_at'] ?? null)); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Sales -->
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title"><span class="dot"></span> Recent Coupon Sales</h2>
                            <p class="card-sub">Latest purchases (last 30).</p>
                        </div>
                        <div class="pill ok"><?php echo (int)count($sales); ?> records</div>
                    </div>

                    <div class="card-body">
                        <?php if (empty($sales)): ?>
                            <div class="empty">No coupon purchases yet.</div>
                        <?php else: ?>
                            <div class="scroll list" aria-label="Coupon sales list">
                                <?php foreach ($sales as $sale): ?>
                                    <div class="item">
                                        <div class="item-top">
                                            <div>
                                                <h3 class="item-title"><?php echo h($sale['buyer_name']); ?></h3>
                                                <div class="item-sub">
                                                    <?php echo h($sale['code']); ?> · Purchase #<?php echo (int)$sale['purchase_id']; ?>
                                                </div>
                                            </div>
                                            <div class="pill ok"><?php echo money($sale['price']); ?> BDT</div>
                                        </div>

                                        <div class="meta">
                                            <span class="chip">Purchased: <?php echo h(fmt_dt($sale['created_at'] ?? null)); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </section>
    </main>
</div>
</body>
</html>
