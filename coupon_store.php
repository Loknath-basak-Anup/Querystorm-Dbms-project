<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupons.php';

require_role('buyer');
ensure_coupon_tables();

$buyerId = get_user_id() ?? 0;

$coupons = db_fetch_all(
    "SELECT *
     FROM coupons
     WHERE is_active = 1 AND is_published = 1
       AND (starts_at IS NULL OR starts_at <= NOW())
       AND (ends_at IS NULL OR ends_at >= NOW())
     ORDER BY created_at DESC"
);

$purchases = db_fetch_all(
    "SELECT cp.purchase_id, cp.price, cp.download_token, cp.downloaded_at, cp.used_at, cp.created_at,
            c.code, c.title, c.discount_type, c.discount_value, c.min_purchase
     FROM coupon_purchases cp
     INNER JOIN coupons c ON c.coupon_id = cp.coupon_id
     WHERE cp.buyer_id = ?
     ORDER BY cp.created_at DESC",
    [$buyerId]
);

$walletRow = db_fetch(
    "SELECT COALESCE(SUM(
        CASE
            WHEN txn_type IN ('credit','deposit','topup','refund') THEN amount
            WHEN txn_type IN ('debit','purchase','withdraw') THEN -amount
            ELSE amount
        END
    ), 0) AS balance
     FROM wallet_transactions
     WHERE user_id = ?",
    [$buyerId]
);
$walletBalance = (float)($walletRow['balance'] ?? 0);
if ($walletBalance < 0) $walletBalance = 0;

$flashMsg = $_GET['msg'] ?? '';
$flashErr = $_GET['err'] ?? '';

function h(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function money($v): string { return number_format((float)$v, 2); }
function fmt_dt(?string $dt): string {
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $ts ? date('M d, Y · h:i A', $ts) : '—';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Coupon Store | QuickMart</title>

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

            /* Radii */
            --r-lg:18px;
            --r-xl:24px;

            /* Layout */
            --maxw:1200px;
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

        /* Sticky top bar */
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
            min-width: 180px;
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

        .container{ max-width:var(--maxw); margin:0 auto; padding:22px 18px 54px; }

        /* Hero */
        .hero{
            display:flex; align-items:flex-start; justify-content:space-between;
            gap:14px; flex-wrap:wrap;
            margin-top:8px;
        }
        .pill{
            display:inline-flex; align-items:center; gap:8px;
            padding:8px 12px; border-radius:999px;
            border:1px solid rgba(139,92,246,.20);
            background: rgba(255,255,255,.55);
            box-shadow: 0 12px 28px rgba(2,6,23,.06);
            font-weight:900; font-size:12px; color:var(--primary); letter-spacing:.35px;
            white-space: nowrap;
        }
        .hero h1{
            margin:12px 0 6px;
            font-size: clamp(28px, 3.8vw, 44px);
            line-height: 1.05;
            letter-spacing: -.6px;
            font-weight: 900;
        }
        .hero p{ margin: 6px 0 0; color:var(--muted); font-weight:700; max-width: 780px; }
        .hero-right{
            display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:flex-end;
        }

        /* Wallet card */
        .wallet{
            border-radius: var(--r-xl);
            border:1px solid rgba(15,23,42,.10);
            background: linear-gradient(180deg, var(--glass2), var(--glass));
            box-shadow: var(--sh-sm);
            padding: 12px 14px;
            display:flex; align-items:center; gap:12px;
        }
        .wallet .ico{
            width:44px; height:44px; border-radius:16px;
            display:grid; place-items:center;
            background: rgba(139,92,246,.12);
            border:1px solid rgba(139,92,246,.18);
            color: var(--primary);
        }
        .wallet .k{ color: var(--muted2); font-weight:900; color: var(--muted2); font-size:12px; letter-spacing:.25px; }
        .wallet .v{ font-weight: 900; font-size:20px; line-height:1.1; margin-top:2px; }

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

        /* Section cards */
        .section{
            margin-top: 16px;
            border-radius: var(--r-xl);
            border:1px solid rgba(15,23,42,.10);
            background: linear-gradient(180deg, var(--glass2), var(--glass));
            box-shadow: var(--sh-sm);
            overflow:hidden;
        }
        .section-head{
            padding:14px 14px 12px;
            border-bottom:1px solid rgba(15,23,42,.08);
            display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
        }
        .section-title{
            display:flex; align-items:center; gap:10px;
            font-weight: 900;
            letter-spacing: -.2px;
            font-size: 18px;
            margin:0;
        }
        .dot{
            width:10px; height:10px; border-radius:999px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 10px 22px rgba(139,92,246,.25);
        }
        .section-sub{
            color: var(--muted2);
            font-weight: 700;
            font-size: 13px;
            margin: 6px 0 0;
        }
        .section-body{ padding: 14px; }

        /* Grid */
        .grid{
            display:grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }
        @media(min-width: 720px){
            .grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media(min-width: 1100px){
            .grid{ grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        /* Coupon card (premium) */
        .coupon{
            position:relative;
            border-radius: var(--r-xl);
            border: 1px solid rgba(15,23,42,.10);
            background: rgba(255,255,255,.62);
            box-shadow: 0 10px 30px rgba(2,6,23,.08);
            overflow:hidden;
            padding: 14px;
            display:flex;
            flex-direction:column;
            gap: 10px;
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }
        .coupon::before{
            content:"";
            position:absolute; inset:0; z-index:0;
            background: radial-gradient(circle at 20% 0%, rgba(139,92,246,.20), transparent 45%),
                        radial-gradient(circle at 100% 30%, rgba(236,72,153,.16), transparent 48%);
            pointer-events:none;
        }
        .coupon:hover{
            transform: translateY(-3px);
            box-shadow: var(--sh-md);
            border-color: rgba(139,92,246,.20);
        }
        .coupon:active{ transform: translateY(-1px) scale(.995); }
        .coupon > *{ position:relative; z-index:1; }

        .coupon-top{
            display:flex; align-items:flex-start; justify-content:space-between; gap:10px; flex-wrap:wrap;
        }
        .code{
            font-weight: 900;
            letter-spacing: .35px;
            font-size: 16px;
            background: rgba(255,255,255,.62);
            border: 1px dashed rgba(139,92,246,.35);
            padding: 8px 10px;
            border-radius: 999px;
        }

        .badge{
            display:inline-flex; align-items:center; gap:8px;
            padding: 8px 10px;
            border-radius: 999px;
            border:1px solid rgba(15,23,42,.10);
            background: rgba(255,255,255,.55);
            color: var(--muted);
            font-weight: 900;
            font-size: 12px;
            white-space: nowrap;
        }
        .badge.ok{ color: var(--ok); background: var(--okbg); border-color: rgba(22,163,74,.22); }
        .badge.warn{ color: var(--warn); background: var(--warnbg); border-color: rgba(202,138,4,.22); }

        .title{ margin: 0; font-weight: 900; font-size: 18px; letter-spacing: -.2px; }
        .desc{ margin: 0; color: var(--muted); font-weight: 700; line-height: 1.55; }

        .kv{
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 2px;
        }
        .kv .box{
            border-radius: 16px;
            border:1px solid rgba(15,23,42,.10);
            background: rgba(255,255,255,.55);
            padding: 10px 10px;
        }
        .kv .k{
            color: var(--muted2);
            font-weight: 900;
            font-size: 12px;
            letter-spacing: .25px;
        }
        .kv .v{
            margin-top: 2px;
            font-weight: 900;
            color: var(--text);
        }

        .price-row{
            display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
            margin-top: 4px;
        }
        .price{
            font-weight: 900;
            font-size: 18px;
            letter-spacing: -.2px;
            color: var(--text);
        }
        .price small{ color: var(--muted2); font-size: 12px; font-weight: 900; }

        .btn{
            display:inline-flex; align-items:center; justify-content:center; gap:10px;
            width:100%;
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
            width:auto;
            background: rgba(255,255,255,.58);
            color: var(--text);
            border:1px solid rgba(15,23,42,.10);
            box-shadow: var(--sh-sm);
        }

        /* Purchase status */
        .status{
            display:flex; gap:8px; flex-wrap:wrap; align-items:center;
            color: var(--muted);
            font-weight: 800;
        }
        .status .dot{
            width:10px; height:10px; border-radius:999px; box-shadow:none;
            background: rgba(100,116,139,.35);
        }
        .status .dot.ok{ background: rgba(22,163,74,.55); }
        .status .dot.warn{ background: rgba(202,138,4,.55); }

        /* Empty state */
        .empty{
            border-radius: var(--r-xl);
            border: 1px dashed rgba(15,23,42,.18);
            background: rgba(255,255,255,.55);
            padding: 14px;
            color: var(--muted);
            font-weight: 800;
        }

        @media (max-width: 420px){
            .brand-sub{ display:none; }
            .kv{ grid-template-columns: 1fr; }
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
            <a class="brand" href="index.php" aria-label="QuickMart home">
                <div class="brand-badge">QM</div>
                <div>
                    <div class="brand-title">Coupon Store</div>
                    <span class="brand-sub">Save more · QuickMart</span>
                </div>
            </a>

            <nav class="nav" aria-label="Site navigation">
                <a href="index.php">Home</a>
                <a href="html/products_page.php">Products</a>
                <a class="active" href="cupon_store.php">Coupons</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="hero">
            <div>
                <span class="pill">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M21 10V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v3a2 2 0 0 1 0 4v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a2 2 0 0 1 0-4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        <path d="M13 5v14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-dasharray="2.5 3.5"/>
                    </svg>
                    Buy coupons, unlock savings
                </span>
                <h1>Premium discounts, instantly</h1>
                <p>Purchase a coupon from the store and use it on eligible products. Download the invoice once for your records.</p>
            </div>

            <div class="hero-right">
                <div class="wallet" title="Wallet balance">
                    <div class="ico" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M4 7h16v12H4V7z" stroke="currentColor" stroke-width="1.8" />
                            <path d="M20 10h-4a2 2 0 0 0 0 4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M4 7l2-3h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <div class="k">Wallet balance</div>
                        <div class="v"><?php echo money($walletBalance); ?> <span style="font-size:12px; font-weight:900; color:var(--muted2);">BDT</span></div>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($flashMsg !== ''): ?>
            <div class="flash success"><?php echo h($flashMsg); ?></div>
        <?php endif; ?>
        <?php if ($flashErr !== ''): ?>
            <div class="flash error"><?php echo h($flashErr); ?></div>
        <?php endif; ?>

        <!-- Available -->
        <section class="section" aria-label="Available coupons">
            <div class="section-head">
                <div>
                    <h2 class="section-title"><span class="dot"></span> Available Coupons</h2>
                    <p class="section-sub">Only active, published coupons within their valid time window are shown here.</p>
                </div>
                <div class="pill" style="color:var(--muted);">
                    Showing: <?php echo (int)count($coupons); ?>
                </div>
            </div>

            <div class="section-body">
                <?php if (empty($coupons)): ?>
                    <div class="empty">No coupons available right now. Please check again soon.</div>
                <?php else: ?>
                    <div class="grid">
                        <?php foreach ($coupons as $coupon): ?>
                            <?php
                                $type = (string)($coupon['discount_type'] ?? 'percent');
                                $typeLabel = ($type === 'fixed') ? 'Fixed' : 'Percent';
                                $discountValue = (float)($coupon['discount_value'] ?? 0);
                                $minPurchase = (float)($coupon['min_purchase'] ?? 0);
                                $title = trim((string)($coupon['title'] ?? ''));
                                if ($title === '') $title = 'Limited offer';
                                $desc = trim((string)($coupon['description'] ?? ''));
                                if ($desc === '') $desc = 'Save more on eligible products with this coupon.';
                            ?>
                            <article class="coupon">
                                <div class="coupon-top">
                                    <div class="code"><?php echo h($coupon['code']); ?></div>
                                    <div class="badge <?php echo $type === 'fixed' ? 'ok' : 'warn'; ?>">
                                        <?php echo h($typeLabel); ?>
                                    </div>
                                </div>

                                <h3 class="title"><?php echo h($title); ?></h3>
                                <p class="desc"><?php echo h($desc); ?></p>

                                <div class="kv" aria-label="Coupon details">
                                    <div class="box">
                                        <div class="k">Discount</div>
                                        <div class="v">
                                            <?php echo money($discountValue); ?>
                                            <span style="font-size:12px; font-weight:900; color:var(--muted2);">
                                                <?php echo $type === 'fixed' ? 'BDT' : '%'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="box">
                                        <div class="k">Min purchase</div>
                                        <div class="v"><?php echo money($minPurchase); ?> <span style="font-size:12px; font-weight:900; color:var(--muted2);">BDT</span></div>
                                    </div>
                                </div>

                                <div class="price-row">
                                    <div class="price">
                                        <?php echo money($coupon['price']); ?> <small>BDT</small>
                                    </div>
                                    <div class="badge ok" title="Active & published">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Ready
                                    </div>
                                </div>

                                <form method="POST" action="actions/buy_coupon.php">
                                    <input type="hidden" name="coupon_id" value="<?php echo (int)$coupon['coupon_id']; ?>">
                                    <button class="btn" type="submit">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M6 6h15l-1.5 9h-12L6 6z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                            <path d="M8 6l-1-3H3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM17 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                        Buy Coupon
                                    </button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- My coupons -->
        <section class="section" aria-label="My coupons">
            <div class="section-head">
                <div>
                    <h2 class="section-title"><span class="dot"></span> My Coupons</h2>
                    <p class="section-sub">Your purchased coupons and invoice download status.</p>
                </div>
                <div class="pill" style="color:var(--muted);">
                    Purchased: <?php echo (int)count($purchases); ?>
                </div>
            </div>

            <div class="section-body">
                <?php if (empty($purchases)): ?>
                    <div class="empty">You haven’t purchased any coupons yet. Grab one above to start saving.</div>
                <?php else: ?>
                    <div class="grid">
                        <?php foreach ($purchases as $purchase): ?>
                            <?php
                                $used = !empty($purchase['used_at']);
                                $downloaded = !empty($purchase['downloaded_at']);
                                $type = (string)($purchase['discount_type'] ?? 'percent');
                                $typeLabel = ($type === 'fixed') ? 'Fixed' : 'Percent';
                                $discountValue = (float)($purchase['discount_value'] ?? 0);
                                $minPurchase = (float)($purchase['min_purchase'] ?? 0);
                                $title = trim((string)($purchase['title'] ?? ''));
                                if ($title === '') $title = 'Purchased coupon';
                            ?>
                            <article class="coupon">
                                <div class="coupon-top">
                                    <div class="code"><?php echo h($purchase['code']); ?></div>
                                    <div class="badge <?php echo $used ? 'ok' : 'warn'; ?>">
                                        <?php echo $used ? 'Used' : 'Ready'; ?>
                                    </div>
                                </div>

                                <h3 class="title"><?php echo h($title); ?></h3>

                                <div class="status" aria-label="Purchase status">
                                    <span class="dot <?php echo $used ? 'ok' : 'warn'; ?>"></span>
                                    <span>Bought: <?php echo h(fmt_dt($purchase['created_at'] ?? null)); ?></span>
                                </div>

                                <div class="kv">
                                    <div class="box">
                                        <div class="k">Discount</div>
                                        <div class="v">
                                            <?php echo money($discountValue); ?>
                                            <span style="font-size:12px; font-weight:900; color:var(--muted2);">
                                                <?php echo $type === 'fixed' ? 'BDT' : '%'; ?>
                                            </span>
                                            <span style="display:block; margin-top:2px; font-size:12px; font-weight:900; color:var(--muted2);">
                                                Type: <?php echo h($typeLabel); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="box">
                                        <div class="k">Min purchase</div>
                                        <div class="v"><?php echo money($minPurchase); ?> <span style="font-size:12px; font-weight:900; color:var(--muted2);">BDT</span></div>
                                    </div>
                                </div>

                                <div class="price-row">
                                    <div class="price">
                                        Paid: <?php echo money($purchase['price']); ?> <small>BDT</small>
                                    </div>
                                    <div class="badge <?php echo $downloaded ? 'ok' : 'warn'; ?>">
                                        <?php echo $downloaded ? 'Invoice downloaded' : 'Invoice available'; ?>
                                    </div>
                                </div>

                                <div>
                                    <?php if (!$downloaded && !empty($purchase['download_token'])): ?>
                                        <a class="btn btn-ghost" style="width:100%; text-align:center;"
                                           href="download_coupon_invoice.php?purchase_id=<?php echo (int)$purchase['purchase_id']; ?>&token=<?php echo h($purchase['download_token']); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M12 3v10" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                                <path d="M8 11l4 4 4-4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M5 21h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                            </svg>
                                            Download Invoice (1x)
                                        </a>
                                    <?php else: ?>
                                        <div class="pill ok" style="justify-content:center; width:100%;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Invoice already downloaded
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
</body>
</html>
