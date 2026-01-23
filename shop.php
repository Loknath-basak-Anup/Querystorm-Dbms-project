<?php
require_once __DIR__ . '/includes/db.php';

$shops = [];
$loadError = null;

try {
    $shops = db_fetch_all(
        "SELECT
            s.shop_id, s.shop_name, s.shop_description, s.address, s.city, s.country,
            s.phone, s.email, s.logo_url, s.banner_url, s.verified, s.rating,
            s.created_at, s.updated_at,
            u.full_name AS owner_name, u.email AS owner_email, u.phone AS owner_phone
         FROM shops s
         LEFT JOIN users u ON s.seller_id = u.user_id
         ORDER BY s.shop_name"
    );
    $missingShops = db_fetch_all(
        "SELECT
            sp.seller_id AS shop_id,
            sp.shop_name,
            sp.shop_description,
            NULL AS address,
            NULL AS city,
            NULL AS country,
            NULL AS phone,
            NULL AS email,
            NULL AS logo_url,
            NULL AS banner_url,
            sp.verified AS verified,
            NULL AS rating,
            sp.created_at,
            sp.created_at AS updated_at,
            u.full_name AS owner_name,
            u.email AS owner_email,
            u.phone AS owner_phone
         FROM seller_profiles sp
         LEFT JOIN users u ON sp.seller_id = u.user_id
         WHERE sp.seller_id NOT IN (SELECT seller_id FROM shops)
         ORDER BY sp.shop_name"
    );
    if (!empty($missingShops)) {
        $shops = array_merge($shops, $missingShops);
    }
} catch (Throwable $e) {
    try {
        $shops = db_fetch_all(
            "SELECT
                sp.seller_id AS shop_id,
                sp.shop_name,
                sp.shop_description,
                NULL AS address,
                NULL AS city,
                NULL AS country,
                NULL AS phone,
                NULL AS email,
                NULL AS logo_url,
                NULL AS banner_url,
                sp.verified AS verified,
                NULL AS rating,
                sp.created_at,
                sp.created_at AS updated_at,
                u.full_name AS owner_name,
                u.email AS owner_email,
                u.phone AS owner_phone
             FROM seller_profiles sp
             INNER JOIN users u ON sp.seller_id = u.user_id
             ORDER BY sp.shop_name"
        );
    } catch (Throwable $inner) {
        $loadError = 'Unable to load shops. Please ensure the shops table is created in the database.';
    }
}

function h(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
function fmt_date(?string $date): string {
    if (!$date) return '—';
    $ts = strtotime($date);
    return $ts ? date('M d, Y', $ts) : '—';
}
function fmt_location(array $shop): string {
    $parts = [];
    foreach (['address','city','country'] as $k) {
        $v = trim((string)($shop[$k] ?? ''));
        if ($v !== '') $parts[] = $v;
    }
    return $parts ? implode(', ', $parts) : '—';
}
function num_or_dash($v): string {
    if ($v === null || $v === '') return '—';
    return (string)$v;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>QuickMart | Shop Directory</title>

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            /* Brand */
            --primary: #8b5cf6;
            --secondary: #ec4899;

            /* Neutrals (Light) */
            --bg: #f8fafc;
            --text: #0f172a;
            --muted: #475569;
            --muted-2: #64748b;
            --card: rgba(255,255,255,.62);
            --card-strong: rgba(255,255,255,.78);
            --stroke: rgba(15,23,42,.10);
            --glow-1: rgba(59,130,246,.16);
            --glow-2: rgba(139,92,246,.16);
            --glow-3: rgba(236,72,153,.14);

            /* Shadows */
            --shadow-sm: 0 10px 30px rgba(2,6,23,.08);
            --shadow-md: 0 18px 50px rgba(2,6,23,.12);
            --shadow-glow: 0 0 40px rgba(139,92,246,.15);

            /* Radii */
            --r-lg: 20px;
            --r-xl: 26px;

            /* Layout */
            --maxw: 1160px;
        }

        /* Dark Mode */
        .dark{
            --bg: #1a1a2e;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --muted-2: #cbd5e1;
            --card: rgba(45,45,70,.75);
            --card-strong: rgba(55,55,85,.90);
            --stroke: rgba(255,255,255,.15);
            --glow-1: rgba(59,130,246,.30);
            --glow-2: rgba(139,92,246,.30);
            --glow-3: rgba(236,72,153,.28);
            --shadow-sm: 0 10px 30px rgba(0,0,0,.40);
            --shadow-md: 0 18px 50px rgba(0,0,0,.50);
            --shadow-glow: 0 0 50px rgba(139,92,246,.30);
        }

        *{ box-sizing: border-box; }
        html, body { height: 100%; }
        html{ scroll-behavior: smooth; }
        body{
            margin: 0;
            font-family: "Baloo Da 2", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
            transition: background .35s cubic-bezier(0.4, 0, 0.2, 1), color .35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Premium gradient canvas */
        .page{
            min-height: 100vh;
            position: relative;
            overflow-x: clip;
            overflow-y: hidden;
            background: var(--bg);
        }
        .bg-gradient{
            position: absolute;
            inset: 0;
            z-index: -2;
            background: linear-gradient(135deg, var(--glow-1) 0%, var(--glow-2) 45%, var(--glow-3) 100%);
            transition: background .35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes blob-float{
            0%, 100%{ transform: translate(0, 0) scale(1); }
            33%{ transform: translate(30px, -30px) scale(1.05); }
            66%{ transform: translate(-20px, 20px) scale(.95); }
        }
        .blob{
            position: absolute;
            z-index: -1;
            filter: blur(44px);
            opacity: .75;
            transform: translateZ(0);
            border-radius: 999px;
            pointer-events: none;
            animation: blob-float 20s ease-in-out infinite;
            transition: opacity .35s ease;
        }
        .blob.one{
            width: 420px; height: 420px;
            left: -140px; top: 110px;
            background: radial-gradient(circle at 30% 30%, rgba(59,130,246,.45), rgba(139,92,246,.15) 60%, transparent 72%);
            animation-delay: 0s;
        }
        .blob.two{
            width: 520px; height: 520px;
            right: -220px; top: 240px;
            background: radial-gradient(circle at 40% 40%, rgba(139,92,246,.42), rgba(236,72,153,.16) 60%, transparent 72%);
            animation-delay: 3s;
        }
        .blob.three{
            width: 420px; height: 420px;
            left: 20%; bottom: -220px;
            background: radial-gradient(circle at 40% 40%, rgba(236,72,153,.40), rgba(59,130,246,.12) 60%, transparent 72%);
            animation-delay: 6s;
        }
        .dark .blob.one{ background: radial-gradient(circle at 30% 30%, rgba(59,130,246,.55), rgba(139,92,246,.25) 60%, transparent 72%); }
        .dark .blob.two{ background: radial-gradient(circle at 40% 40%, rgba(139,92,246,.52), rgba(236,72,153,.26) 60%, transparent 72%); }
        .dark .blob.three{ background: radial-gradient(circle at 40% 40%, rgba(236,72,153,.50), rgba(59,130,246,.22) 60%, transparent 72%); }

        /* Sticky top bar */
        .topbar{
            position: sticky;
            top: 0;
            z-index: 40;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            background: rgba(255, 255, 255, 0.7);
            border-bottom: 1px solid var(--stroke);
        }
        .dark .topbar{
            background: rgba(15, 23, 42, 0.6);
        }
        .topbar-inner{
            max-width: var(--maxw);
            margin: 0 auto;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }
        .brand{
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: inherit;
            min-width: 160px;
        }
        .brand img{
            width: 150px;
            height: 42px;
            object-fit: contain;
            border-radius: 12px;
        }
        .brand .title{
            font-weight: 800;
            letter-spacing: .2px;
            font-size: 18px;
            line-height: 1.1;
        }
        .brand .subtitle{
            display: block;
            font-size: 12px;
            color: var(--muted-2);
            margin-top: 2px;
            font-weight: 600;
        }

        .nav{
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .nav a{
            text-decoration: none;
            color: var(--muted);
            font-weight: 700;
            font-size: 14px;
            padding: 10px 12px;
            border-radius: 999px;
            transition: transform .12s ease, background .12s ease, color .12s ease;
        }
        .nav a:hover{
            background: rgba(139,92,246,.12);
            color: var(--text);
            transform: translateY(-1px);
        }
        .nav a:active{
            transform: translateY(0px) scale(.98);
            background: rgba(236,72,153,.12);
        }
        .nav a.active{
            color: var(--text);
            background: linear-gradient(135deg, rgba(139,92,246,.16), rgba(236,72,153,.14));
            border: 1px solid rgba(139,92,246,.18);
        }

        /* Theme Toggle */
        .theme-toggle{
            width: 42px; height: 42px;
            border: none;
            background: rgba(139,92,246,.12);
            border-radius: 12px;
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: all .2s ease;
            position: relative;
            overflow: hidden;
        }
        .theme-toggle:hover{
            background: rgba(139,92,246,.20);
            transform: translateY(-1px);
        }
        .theme-toggle:active{ transform: scale(.95); }
        .theme-toggle svg{
            width: 20px; height: 20px;
            transition: all .3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        .theme-toggle .sun{ color: #f59e0b; position: absolute; }
        .theme-toggle .moon{ color: #8b5cf6; position: absolute; }
        .theme-toggle .sun{ opacity: 1; transform: rotate(0deg) scale(1); }
        .theme-toggle .moon{ opacity: 0; transform: rotate(90deg) scale(0); }
        .dark .theme-toggle .sun{ opacity: 0; transform: rotate(-90deg) scale(0); }
        .dark .theme-toggle .moon{ opacity: 1; transform: rotate(0deg) scale(1); }

        /* Content */
        .container{
            max-width: var(--maxw);
            margin: 0 auto;
            padding: 26px 18px 54px;
        }

        .hero{
            text-align: center;
            padding: 26px 0 12px;
        }
        .pill{
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(139,92,246,.20);
            background: rgba(255,255,255,.55);
            box-shadow: 0 12px 28px rgba(2,6,23,.06);
            font-weight: 800;
            font-size: 12px;
            color: rgba(139,92,246,1);
            letter-spacing: .35px;
        }
        .hero h1{
            margin: 14px 0 6px;
            font-size: clamp(30px, 4vw, 46px);
            line-height: 1.05;
            letter-spacing: -.5px;
            font-weight: 900;
        }
        .hero p{
            margin: 10px auto 0;
            max-width: 760px;
            color: var(--muted);
            font-weight: 600;
            font-size: 16px;
        }

        /* Grid */
        .grid{
            margin-top: 22px;
            display: grid;
            gap: 18px;
            grid-template-columns: 1fr;
        }
        /* Tablet */
        @media (min-width: 720px){
            .grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        }
        /* Desktop */
        @media (min-width: 1100px){
            .grid{
                gap: 18px; }
        }

        /* Glass card */
        @keyframes card-appear{
            from{ opacity: 0; transform: translateY(20px); }
            to{ opacity: 1; transform: translateY(0); }
        }
        .card{
            position: relative;
            border-radius: var(--r-xl);
            overflow: hidden;
            border: 1px solid var(--stroke);
            background: linear-gradient(180deg, var(--card-strong), var(--card));
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: var(--shadow-sm);
            transition: all .25s cubic-bezier(0.4, 0, 0.2, 1);
            animation: card-appear .4s ease-out backwards;
            padding: 16px;
        }
        .card::before{
            content: '';
            position: absolute;
            border-radius: var(--r-xl);
            padding: 1px;
            background: linear-gradient(135deg, rgba(139,92,246,.3), transparent 50%, rgba(236,72,153,.3));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity .25s ease;
        }
        .card:hover{
            transform: translateY(-6px) scale(1.01);
            box-shadow: var(--shadow-md), var(--shadow-glow);
            border-color: rgba(139,92,246,.30);
        }
        .card:hover::before{ opacity: 1; }
        .card:active{
            transform: translateY(-2px) scale(.995);
        }
        .grid .card:nth-child(1){ animation-delay: .05s; }
        .grid .card:nth-child(2){ animation-delay: .1s; }
        .grid .card:nth-child(3){ animation-delay: .15s; }
        .grid .card:nth-child(4){ animation-delay: .2s; }
        .grid .card:nth-child(5){ animation-delay: .25s; }
        .grid .card:nth-child(6){ animation-delay: .3s; }

        .card-body{
            display: grid;
            grid-template-columns: 128px 1fr;
            gap: 16px;
            align-items: start;
        }
        @media (max-width: 520px){
            .card-body{ grid-template-columns: 1fr; }
        }
        .media{
            position: relative;
            width: 100%;
            height: 128px;
            border-radius: 18px;
            overflow: hidden;
            background: rgba(148,163,184,.25);
            border: 1px solid rgba(15,23,42,.10);
        }
        .media img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        .media .logo-badge{
            position: absolute;
            right: 8px;
            bottom: 8px;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255,255,255,.92);
            border: 2px solid rgba(255,255,255,.9);
            display: grid;
            place-items: center;
            overflow: hidden;
            box-shadow: 0 6px 14px rgba(2,6,23,.16);
        }
        .dark .media .logo-badge{
            background: rgba(45,45,70,.95);
            border-color: rgba(55,55,85,.95);
        }
        .media .logo-badge img{
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .name-row{
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 2px;
        }
        .details{
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .shop-name{
            margin: 0;
            font-size: 20px;
            line-height: 1.1;
            font-weight: 900;
            letter-spacing: -.2px;
        }
        .badge{
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 900;
            border-radius: 999px;
            border: 1px solid rgba(16,185,129,.24);
            background: rgba(16,185,129,.12);
            color: rgba(5,150,105,1);
        }
        .badge svg{ width: 14px; height: 14px; }

        .meta{
            margin-top: 3px;
            color: var(--muted-2);
            font-weight: 700;
            font-size: 13px;
        }

        .desc{
            margin: 10px 0 0;
            color: rgba(15,23,42,.78);
            font-weight: 600;
            font-size: 13px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 40px;
        }
        .dark .desc{
            color: rgba(241,245,249,.85);
        }

        .stats{
            margin-top: 12px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        @media (max-width: 520px){
            .stats{ grid-template-columns: repeat(2, 1fr); }
        }
        .stat{
            border-radius: 14px;
            background: rgba(255,255,255,.7);
            border: 1px solid rgba(15,23,42,.08);
            padding: 8px 10px;
            text-align: center;
        }
        .dark .stat{
            background: rgba(45,45,70,.65);
            border-color: rgba(255,255,255,.12);
        }
        .stat .k{
            font-size: 11px;
            color: var(--muted-2);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .3px;
        }
        .stat .v{
            margin-top: 2px;
            font-size: 18px;
            font-weight: 900;
            color: var(--text);
        }

        /* Footer row */
        .actions{
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        @media (max-width: 520px){
            .actions{ grid-template-columns: 1fr; }
        }
        .btn{
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid rgba(15,23,42,.12);
            background: rgba(15,23,42,.05);
            color: var(--text);
            font-weight: 900;
            letter-spacing: .2px;
            text-decoration: none;
            transition: transform .12s ease, filter .12s ease, box-shadow .12s ease, background .12s ease;
        }
        .btn.primary{
            border: 1px solid rgba(139,92,246,.35);
            background: linear-gradient(135deg, rgba(139,92,246,1), rgba(236,72,153,1));
            color: white;
            box-shadow: 0 18px 40px rgba(139,92,246,.22);
        }
        .btn:hover{
            filter: brightness(1.03);
            box-shadow: 0 10px 28px rgba(2,6,23,.12);
            transform: translateY(-1px);
        }
        .btn.primary:hover{
            box-shadow: 0 22px 54px rgba(236,72,153,.20);
        }
        .btn:active{
            transform: translateY(0px) scale(.99);
            filter: brightness(.98);
        }
        .btn:focus{
            outline: none;
            box-shadow: 0 0 0 4px rgba(139,92,246,.20), 0 18px 40px rgba(139,92,246,.22);
        }

        /* State cards (empty/error) */
        .state{
            margin: 18px auto 0;
            max-width: 820px;
            border-radius: var(--r-xl);
            border: 1px solid rgba(15,23,42,.10);
            background: linear-gradient(180deg, rgba(255,255,255,.72), rgba(255,255,255,.52));
            box-shadow: var(--shadow-sm);
            padding: 18px;
            text-align: left;
        }
        .state-inner{
            display: grid;
            grid-template-columns: 44px 1fr;
            gap: 12px;
            align-items: start;
        }
        .state .icon{
            width: 44px; height: 44px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: rgba(139,92,246,.12);
            border: 1px solid rgba(139,92,246,.18);
        }
        .state h3{
            margin: 0;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: -.2px;
        }
        .state p{
            margin: 6px 0 0;
            color: var(--muted);
            font-weight: 700;
            line-height: 1.55;
        }
        .state.error .icon{
            background: rgba(244,63,94,.10);
            border-color: rgba(244,63,94,.18);
        }
        .state.error h3{ color: rgba(190,18,60,1); }

        /* Footer */
        .footer{
            margin-top: 60px;
            border-top: 1px solid var(--stroke);
            background: linear-gradient(180deg, transparent, rgba(139,92,246,.05));
            backdrop-filter: blur(8px);
            padding: 40px 0 24px;
        }
        .dark .footer{
            background: linear-gradient(180deg, rgba(26,26,46,.40), rgba(139,92,246,.08));
        }
        .footer-grid{
            max-width: var(--maxw);
            margin: 0 auto;
            padding: 0 18px;
            display: grid;
            gap: 32px;
            grid-template-columns: 1fr;
        }
        @media (min-width: 640px){
            .footer-grid{ grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 900px){
            .footer-grid{ grid-template-columns: 2fr 1fr 1fr 1fr; }
        }
        .footer-col h3{
            margin: 0 0 12px;
            font-size: 15px;
            font-weight: 900;
            letter-spacing: .3px;
            color: var(--text);
        }
        .footer-col p{
            margin: 0;
            color: var(--muted);
            font-weight: 600;
            font-size: 14px;
            line-height: 1.6;
        }
        .footer-links{
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .footer-links a{
            color: var(--muted);
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: all .15s ease;
            display: inline-block;
        }
        .footer-links a:hover{
            color: var(--primary);
            transform: translateX(3px);
        }
        .footer-bottom{
            max-width: var(--maxw);
            margin: 32px auto 0;
            padding: 20px 18px 0;
            border-top: 1px solid var(--stroke);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: var(--muted-2);
            font-weight: 700;
        }
        .social-links{
            display: flex;
            gap: 12px;
        }
        .social-links a{
            width: 36px; height: 36px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: rgba(139,92,246,.10);
            color: var(--primary);
            text-decoration: none;
            transition: all .2s ease;
        }
        .social-links a:hover{
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        .social-links svg{ width: 18px; height: 18px; }

        /* Info Section */
        .info-section{
            margin: 40px 0;
            padding: 32px;
            border-radius: var(--r-xl);
            border: 1px solid var(--stroke);
            background: linear-gradient(135deg, var(--card-strong), var(--card));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .info-section h2{
            margin: 0 0 14px;
            font-size: 26px;
            font-weight: 900;
            letter-spacing: -.3px;
        }
        .info-section p{
            margin: 0;
            color: var(--muted);
            font-weight: 600;
            line-height: 1.65;
            font-size: 15px;
        }
        .features-grid{
            display: grid;
            gap: 16px;
            margin-top: 24px;
            grid-template-columns: 1fr;
        }
        @media (min-width: 640px){
            .features-grid{ grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 900px){
            .features-grid{ grid-template-columns: repeat(3, 1fr); }
        }
        .feature{
            display: flex;
            gap: 12px;
            align-items: start;
        }
        .feature-icon{
            width: 40px; height: 40px;
            flex-shrink: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(139,92,246,.15), rgba(236,72,153,.12));
            display: grid;
            place-items: center;
            color: var(--primary);
        }
        .feature-icon svg{ width: 20px; height: 20px; }
        .feature-text strong{
            display: block;
            font-weight: 900;
            font-size: 14px;
            margin-bottom: 3px;
            color: var(--text);
        }
        .feature-text span{
            font-size: 13px;
            color: var(--muted);
            font-weight: 600;
        }

        /* Small tweaks */
        @media (max-width: 420px){
            .detail{ grid-template-columns: 96px 1fr; }
            .brand .subtitle{ display:none; }
        }
    </style>
    <script>
        // Dark mode toggle
        function toggleTheme(){
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
        // Load saved theme
        (function(){
            const saved = localStorage.getItem('theme');
            if(saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)){
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>

<body>
<div class="page">
    <div class="bg-gradient"></div>
    <div class="blob one"></div>
    <div class="blob two"></div>
    <div class="blob three"></div>

    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="index.php" aria-label="QuickMart Home">
                <img src="images/qmart_logo2.png" alt="QuickMart">
            </a>

            <nav class="nav" aria-label="Primary navigation">
                <a href="index.php">Home</a>
                <a href="html/products_page.php">Products</a>
                <a class="active" href="shop.php">Shops</a>
                <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
                    <svg class="sun" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <svg class="moon" viewBox="0 0 24 24" fill="none">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="hero">
            <span class="pill">
                <!-- sparkle -->
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 2l1.2 6.1L19 9.5l-5.2 2.6L12 18l-1.8-5.9L5 9.5l5.8-1.4L12 2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
                Shop Directory
            </span>
            <h1>Discover premium shops you’ll love</h1>
            <p>
                Browse seller shops, check ratings, and contact owners directly. Verified shops are clearly marked so you can shop with confidence.
            </p>
        </section>

        <?php if ($loadError): ?>
            <section class="state error" role="alert">
                <div class="state-inner">
                    <div class="icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M12 9v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M12 17h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            <path d="M10.3 3.8l-8.4 14.5A2 2 0 0 0 3.6 21h16.8a2 2 0 0 0 1.7-2.7L13.7 3.8a2 2 0 0 0-3.4 0z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h3><?php echo h($loadError); ?></h3>
                        <p>Tip: run the SQL patch to create the <b>shops</b> table (see <b>smart_marketplace.sql</b> in your project), then reload this page.</p>
                    </div>
                </div>
            </section>

        <?php elseif (count($shops) === 0): ?>
            <section class="state" aria-live="polite">
                <div class="state-inner">
                    <div class="icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M7 7h10v14H7V7z" stroke="currentColor" stroke-width="1.8" />
                            <path d="M9 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <path d="M9 12h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <h3>No shops yet</h3>
                        <p>Once sellers create shops, they’ll appear here automatically. Check back soon.</p>
                    </div>
                </div>
            </section>

        <?php else: ?>
            <section class="grid" aria-label="Shops">
                <?php foreach ($shops as $shop):
                    $logo = $shop['logo_url'] ?: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=300&auto=format&fit=crop';
                    $banner = $shop['banner_url'] ?: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=1200&auto=format&fit=crop';
                    $phone = $shop['phone'] ?: $shop['owner_phone'];
                    $email = $shop['email'] ?: $shop['owner_email'];
                    $locationText = fmt_location($shop);
                    $mapQuery = $locationText !== 'â€”' ? urlencode($locationText) : '';

                    $ratingVal = $shop['rating'];
                    $ratingText = ($ratingVal !== null && $ratingVal !== '') ? number_format((float)$ratingVal, 2) . ' / 5.00' : '—';
                    $shopDesc = trim((string)($shop['shop_description'] ?? ''));
                    if ($shopDesc === '') $shopDesc = 'A curated shop on QuickMart — explore products, deals, and new arrivals.';
                ?>
                    <article class="card">
                        <div class="card-body">
                            <div class="media" aria-label="Shop banner">
                                <img src="<?php echo h($banner); ?>" alt="<?php echo h($shop['shop_name']); ?> banner">
                                <div class="logo-badge" aria-label="Shop logo">
                                    <img src="<?php echo h($logo); ?>" alt="<?php echo h($shop['shop_name']); ?> logo">
                                </div>
                            </div>

                            <div class="details">
                                <div class="name-row">
                                    <h2 class="shop-name"><?php echo h($shop['shop_name']); ?></h2>

                                    <?php if ((int)$shop['verified'] === 1): ?>
                                        <span class="badge" title="Verified shop">
                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Verified
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="meta">Owner: <?php echo h($shop['owner_name']); ?></div>
                                <p class="desc"><?php echo h($shopDesc); ?></p>

                                <div class="stats">
                                    <div class="stat">
                                        <div class="k">Rating</div>
                                        <div class="v"><?php echo h(($ratingVal !== null && $ratingVal !== '') ? number_format((float)$ratingVal, 1) : '???'); ?></div>
                                    </div>
                                    <div class="stat">
                                        <div class="k">Location</div>
                                        <div class="v"><?php echo h(($shop['city'] ?? '') !== '' ? $shop['city'] : '???'); ?></div>
                                    </div>
                                    <div class="stat">
                                        <div class="k">Updated</div>
                                        <div class="v"><?php echo h(fmt_date($shop['updated_at'] ?? null)); ?></div>
                                    </div>
                                </div>

                                <div class="actions">
                                    <a class="btn primary" href="<?php echo $mapQuery ? 'https://www.google.com/maps/search/?api=1&query=' . $mapQuery : '#'; ?>"<?php echo $mapQuery ? ' target="_blank" rel="noopener"' : ''; ?>>Locate The Shop</a>
                                    <a class="btn" href="<?php echo $email ? 'mailto:' . h($email) : '#'; ?>">Contact with seller</a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <!-- Why Shop with Us -->
        <section class="info-section">
            <h2>Why choose QuickMart shops?</h2>
            <p>Every shop on QuickMart is carefully curated to ensure you get the best shopping experience. Our verified sellers offer quality products, fast shipping, and excellent customer service.</p>
            
            <div class="features-grid">
                <div class="feature">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <div class="feature-text">
                        <strong>Verified Sellers</strong>
                        <span>All shops undergo strict verification</span>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="feature-text">
                        <strong>Wide Selection</strong>
                        <span>Thousands of products across categories</span>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="feature-text">
                        <strong>Fast Delivery</strong>
                        <span>Quick shipping from local sellers</span>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="feature-text">
                        <strong>Secure Payments</strong>
                        <span>Bank-level encryption for transactions</span>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="feature-text">
                        <strong>24/7 Support</strong>
                        <span>Always here to help you</span>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="feature-text">
                        <strong>Best Prices</strong>
                        <span>Competitive pricing guaranteed</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-col">
                <h3>QuickMart</h3>
                <p>Your trusted online marketplace for quality products from verified sellers. Shop with confidence, delivered with care.</p>
            </div>
            <div class="footer-col">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="html/products_page.php">Products</a></li>
                    <li><a href="shop.php">Shops</a></li>
                    <li><a href="html/sellers_overview.php">Become a Seller</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Support</h3>
                <ul class="footer-links">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="html/shipping.php">Shipping Info</a></li>
                    <li><a href="html/returns.php">Returns</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Legal</h3>
                <ul class="footer-links">
                    <li><a href="html/terms.php">Terms of Service</a></li>
                    <li><a href="html/privacy.php">Privacy Policy</a></li>
                    <li><a href="html/cookies.php">Cookie Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div>&copy; 2026 QuickMart. All rights reserved.</div>
            <div class="social-links">
                <a href="#" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                    </svg>
                </a>
                <a href="#" aria-label="Twitter">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>
                    </svg>
                </a>
                <a href="#" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                    </svg>
                </a>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
