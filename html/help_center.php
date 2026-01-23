<?php
require_once __DIR__ . '/../includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Help Center | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b1120;
            --card: #111827;
            --text: #e5e7eb;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --accent2: #f472b6;
            --border: rgba(148, 163, 184, 0.2);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Manrope", sans-serif;
            background: radial-gradient(circle at top, rgba(56,189,248,0.12), transparent 45%),
                        radial-gradient(circle at 80% 20%, rgba(244,114,182,0.18), transparent 45%),
                        var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        .wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 48px 18px 80px;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 24px;
        }
        .header a {
            color: var(--muted);
            text-decoration: none;
            font-weight: 600;
        }
        .hero {
            background: linear-gradient(135deg, rgba(56,189,248,0.12), rgba(244,114,182,0.12));
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
        }
        .hero h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }
        .hero p { margin: 0; color: var(--muted); }
        .grid {
            margin-top: 24px;
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px;
        }
        .card h3 {
            margin: 10px 0 6px;
            font-size: 18px;
        }
        .card p { margin: 0; color: var(--muted); font-size: 0.95rem; }
        .cta {
            margin-top: 22px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 700;
        }
        .btn.primary {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #0b1120;
        }
        .btn.ghost {
            border: 1px solid var(--border);
            color: var(--text);
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header">
            <div style="font-weight:700;">QuickMart Help Center</div>
            <a href="products_page.php"><i class="fas fa-arrow-left"></i> Back to Marketplace</a>
        </div>
        <section class="hero">
            <h1>How can we help?</h1>
            <p>Find quick answers, shipping details, and how to get in touch with our support team.</p>
            <div class="cta">
                <a class="btn primary" href="shipping.php"><i class="fas fa-truck"></i> Shipping Info</a>
                <a class="btn ghost" href="returns.php"><i class="fas fa-undo"></i> Returns & Refunds</a>
                <a class="btn ghost" href="privacy.php"><i class="fas fa-shield-alt"></i> Privacy Policy</a>
            </div>
        </section>

        <section class="grid">
            <div class="card">
                <i class="fas fa-box-open" style="color: var(--accent);"></i>
                <h3>Orders & Tracking</h3>
                <p>Track orders from your dashboard and stay updated on delivery progress.</p>
            </div>
            <div class="card">
                <i class="fas fa-wallet" style="color: var(--accent);"></i>
                <h3>Wallet & Payments</h3>
                <p>Understand wallet balance, coupon usage, and purchase history.</p>
            </div>
            <div class="card">
                <i class="fas fa-user-shield" style="color: var(--accent);"></i>
                <h3>Account Support</h3>
                <p>Update your profile and manage account security settings.</p>
            </div>
        </section>
    </div>
</body>
</html>
