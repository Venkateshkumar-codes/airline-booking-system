<?php
ob_start();
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$profileMsg = "";
$profileErr = "";

if (isset($_POST['update_profile'])) {
    $new_name    = trim($_POST['p_name']);
    $new_dob     = $_POST['p_dob'];
    $new_contact = trim($_POST['p_contact']);
    $new_pass    = trim($_POST['p_password']);

    if (empty($new_name)) {
        $profileErr = "Name cannot be empty.";
    } else {
        if (!empty($new_pass)) {
            $stmt = $conn->prepare("UPDATE users SET name=?, dob=?, contact=?, password=? WHERE id=?");
            $stmt->bind_param("ssssi", $new_name, $new_dob, $new_contact, $new_pass, $_SESSION['user_id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, dob=?, contact=? WHERE id=?");
            $stmt->bind_param("sssi", $new_name, $new_dob, $new_contact, $_SESSION['user_id']);
        }
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $new_name;
            $profileMsg = "Profile updated successfully!";
        } else {
            $profileErr = "Update failed. Please try again.";
        }
        $stmt->close();
    }
}

$u_stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$u_stmt->bind_param("i", $_SESSION['user_id']);
$u_stmt->execute();
$u_data = $u_stmt->get_result()->fetch_assoc();
$u_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkyLine — LAGH Airlines</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --gold: #C9A84C;
            --gold-light: #E8C97A;
            --gold-dim: rgba(201,168,76,0.15);
            --cream: #F5F0E8;
            --navy: #090D1A;
            --navy-mid: #111827;
            --navy-card: rgba(10,15,35,0.85);
            --text-muted: rgba(245,240,232,0.5);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--navy);
            color: var(--cream);
            overflow-x: hidden;
            cursor: none;
        }

        /* Custom cursor */
        .cursor-dot {
            position: fixed;
            width: 8px; height: 8px;
            background: var(--gold);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            transform: translate(-50%, -50%);
            transition: transform 0.1s;
        }
        .cursor-ring {
            position: fixed;
            width: 36px; height: 36px;
            border: 1.5px solid var(--gold);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9998;
            transform: translate(-50%, -50%);
            transition: all 0.18s cubic-bezier(0.25,0.46,0.45,0.94);
            opacity: 0.6;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 1;
            opacity: 0.35;
        }

        /* ── NAVIGATION ── */
        nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 60px;
            z-index: 100;
            background: linear-gradient(to bottom, rgba(9,13,26,0.98) 0%, rgba(9,13,26,0.8) 100%);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(201,168,76,0.08);
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: var(--cream);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo-icon {
            display: inline-flex;
            width: 36px; height: 36px;
            background: var(--gold);
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        .logo span { color: var(--gold); }

        .nav-links {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .nav-btn {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 9px 18px;
            border-radius: 100px;
            border: none;
            background: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .nav-btn:hover { color: var(--cream); background: rgba(255,255,255,0.06); }
        .nav-btn.gold {
            background: var(--gold-dim);
            color: var(--gold);
            border: 1px solid rgba(201,168,76,0.25);
        }
        .nav-btn.gold:hover { background: rgba(201,168,76,0.25); }
        .nav-btn.danger {
            background: rgba(239,68,68,0.08);
            color: #fca5a5;
            border: 1px solid rgba(239,68,68,0.2);
        }
        .nav-btn.danger:hover { background: rgba(239,68,68,0.15); }
        .user-chip {
            color: var(--gold-light);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            padding: 9px 18px;
            background: var(--gold-dim);
            border: 1px solid rgba(201,168,76,0.2);
            border-radius: 100px;
        }

        /* ── HERO ── */
        .hero {
            position: relative;
            height: 100vh;
            min-height: 640px;
            overflow: hidden;
        }
        .hero-img {
            position: absolute;
            inset: 0;
            background: url('bg.jpg') center/cover no-repeat;
        }
        .hero-img::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(to bottom, rgba(9,13,26,0.5) 0%, rgba(9,13,26,0.2) 40%, rgba(9,13,26,0.7) 80%, rgba(9,13,26,1) 100%),
                linear-gradient(to right, rgba(9,13,26,0.6) 0%, transparent 60%);
        }

        .hero-content {
            position: relative;
            z-index: 10;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 60px;
            padding-top: 100px;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            opacity: 0;
            animation: fadeUp 0.8s 0.2s forwards;
        }
        .eyebrow-line { display: block; width: 36px; height: 1px; background: var(--gold); }
        .eyebrow-text {
            font-size: 0.68rem;
            font-weight: 500;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--gold);
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(3.2rem, 6.5vw, 6rem);
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: -0.02em;
            margin-bottom: 24px;
            opacity: 0;
            animation: fadeUp 0.9s 0.4s forwards;
        }
        .hero-title em { font-style: italic; color: var(--gold); }

        .hero-sub {
            font-size: 1rem;
            color: var(--text-muted);
            max-width: 420px;
            line-height: 1.75;
            font-weight: 300;
            margin-bottom: 44px;
            opacity: 0;
            animation: fadeUp 0.9s 0.6s forwards;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── SEARCH PILL ── */
        .search-pill {
            background: rgba(9,13,26,0.75);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(201,168,76,0.2);
            border-radius: 100px;
            display: flex;
            align-items: center;
            gap: 0;
            max-width: 760px;
            overflow: hidden;
            opacity: 0;
            animation: fadeUp 0.9s 0.8s forwards;
            box-shadow: 0 8px 60px rgba(0,0,0,0.4);
        }
        .search-field {
            flex: 2;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 28px;
            border-right: 1px solid rgba(201,168,76,0.12);
        }
        .search-field svg { width: 18px; height: 18px; color: var(--gold); flex-shrink: 0; stroke: var(--gold); }
        .search-field input {
            background: none;
            border: none;
            outline: none;
            color: var(--cream);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            width: 100%;
        }
        .search-field input::placeholder { color: var(--text-muted); }
        .search-counter {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 22px;
            border-right: 1px solid rgba(201,168,76,0.12);
        }
        .counter-btn {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: rgba(201,168,76,0.1);
            border: 1px solid rgba(201,168,76,0.2);
            color: var(--gold);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: bold;
        }
        .counter-btn:hover { background: rgba(201,168,76,0.25); }
        .counter-display { text-align: center; min-width: 40px; }
        .counter-num { font-size: 1.1rem; font-weight: 700; color: var(--cream); display: block; }
        .counter-label { font-size: 0.6rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--text-muted); }
        .search-submit {
            background: var(--gold);
            color: var(--navy);
            border: none;
            padding: 20px 36px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 0 100px 100px 0;
        }
        .search-submit:hover { background: var(--gold-light); }

        /* ── FLIGHTS SECTION ── */
        .flights-section {
            padding: 90px 60px 60px;
            position: relative;
        }
        .section-eyebrow {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .section-eyebrow .eyebrow-line { width: 28px; }
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 3.5vw, 3rem);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin-bottom: 12px;
        }
        .section-title em { font-style: italic; color: var(--gold); }
        .section-desc {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.7;
            max-width: 400px;
            margin-bottom: 56px;
        }

        /* ── FLIGHTS GRID ── */
        .flights-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .flight-card {
            background: var(--navy-mid);
            border: 1px solid rgba(201,168,76,0.1);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.25,0.46,0.45,0.94);
            cursor: pointer;
            position: relative;
        }
        .flight-card:hover {
            border-color: rgba(201,168,76,0.3);
            transform: translateY(-4px);
            box-shadow: 0 24px 60px rgba(0,0,0,0.4);
        }
        .flight-img {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }
        .flight-img::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 40%, rgba(9,13,26,0.9) 100%);
        }
        .flight-img-inner {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            transition: transform 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
        }
        .flight-card:hover .flight-img-inner { transform: scale(1.06); }

        .flight-tag {
            position: absolute;
            top: 16px; left: 16px;
            z-index: 2;
            background: rgba(9,13,26,0.75);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(201,168,76,0.3);
            color: var(--gold);
            font-size: 0.6rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            padding: 5px 12px;
            border-radius: 100px;
        }
        .flight-code {
            position: absolute;
            bottom: 16px; right: 16px;
            z-index: 2;
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            font-weight: 900;
            color: rgba(255,255,255,0.15);
            line-height: 1;
            pointer-events: none;
        }

        .flight-body {
            padding: 22px 24px;
        }
        .flight-city {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 4px;
            line-height: 1;
        }
        .flight-country {
            font-size: 0.75rem;
            color: var(--text-muted);
            letter-spacing: 0.08em;
            margin-bottom: 18px;
        }
        .flight-details {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px solid rgba(201,168,76,0.08);
            padding-top: 16px;
        }
        .flight-price-label {
            font-size: 0.6rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .flight-price {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gold-light);
        }
        .flight-price span { font-size: 0.8rem; font-weight: 400; color: var(--text-muted); font-family: 'DM Sans', sans-serif; }
        .flight-btn {
            background: var(--gold-dim);
            border: 1px solid rgba(201,168,76,0.25);
            color: var(--gold);
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 10px 18px;
            border-radius: 100px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        .flight-card:hover .flight-btn {
            background: var(--gold);
            color: var(--navy);
        }

        /* ── DESTINATIONS SECTION ── */
        .destinations {
            padding: 0 60px 100px;
        }
        .dest-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: 280px 280px;
            gap: 12px;
            margin-top: 56px;
        }
        .dest-card {
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            background: var(--navy-mid);
            border: 1px solid rgba(201,168,76,0.1);
        }
        .dest-card:first-child { grid-row: span 2; }

        .dest-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            transition: transform 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
        }
        .dest-card:hover .dest-bg { transform: scale(1.05); }

        .dest-city-art {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6rem;
            opacity: 0.14;
            user-select: none;
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            white-space: nowrap;
            color: white;
            pointer-events: none;
        }
        .dest-card:first-child .dest-city-art { font-size: 10rem; }

        .dest-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(5,8,20,0.97) 0%, rgba(5,8,20,0.5) 45%, rgba(5,8,20,0.15) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 28px;
        }
        .dest-tag {
            display: inline-block;
            background: rgba(9,13,26,0.75);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(201,168,76,0.3);
            color: var(--gold);
            font-size: 0.6rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            padding: 5px 12px;
            border-radius: 100px;
            margin-bottom: 10px;
            width: fit-content;
        }
        .dest-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 6px;
        }
        .dest-card:first-child .dest-name { font-size: 2.4rem; }
        .dest-price {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .dest-price strong { color: var(--gold-light); }
        .dest-book-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 14px;
            background: var(--gold-dim);
            border: 1px solid rgba(201,168,76,0.25);
            color: var(--gold);
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 9px 18px;
            border-radius: 100px;
            cursor: pointer;
            transition: all 0.3s;
            width: fit-content;
            text-decoration: none;
        }
        .dest-card:hover .dest-book-btn { background: var(--gold); color: var(--navy); }

        /* Real city images */
        .dest-card:nth-child(1) .dest-bg { background-image: url('https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=900&q=80'); }
        .dest-card:nth-child(2) .dest-bg { background-image: url('https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=700&q=80'); }
        .dest-card:nth-child(3) .dest-bg { background-image: url('https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=700&q=80'); }
        .dest-card:nth-child(4) .dest-bg { background-image: url('https://images.unsplash.com/photo-1522083165195-3424ed129620?w=700&q=80'); }
        .dest-card:nth-child(5) .dest-bg { background-image: url('https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=700&q=80'); }

        /* ── MODALS ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 200;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-box {
            background: #0c1120;
            border: 1px solid rgba(201,168,76,0.15);
            border-radius: 24px;
            width: 100%;
            max-width: 460px;
            padding: 40px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-close {
            position: absolute;
            top: 16px; right: 20px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.4);
            font-size: 18px;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .modal-close:hover { background: rgba(255,255,255,0.1); color: white; }
        .modal-label {
            display: block;
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 8px;
        }
        .modal-input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1.5px solid rgba(201,168,76,0.15);
            color: var(--cream);
            padding: 13px 16px;
            border-radius: 12px;
            outline: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .modal-input:focus { border-color: rgba(201,168,76,0.45); background: rgba(201,168,76,0.04); }
        .btn-modal-submit {
            width: 100%;
            background: var(--gold);
            color: var(--navy);
            font-weight: 700;
            padding: 14px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-top: 4px;
            transition: all 0.3s;
        }
        .btn-modal-submit:hover { background: var(--gold-light); transform: translateY(-1px); }
        input[type="date"].modal-input::-webkit-calendar-picker-indicator { filter: invert(0.5); cursor: pointer; }
        .modal-divider { height: 1px; background: rgba(201,168,76,0.1); margin: 20px 0; }

        /* ── FOOTER ── */
        footer {
            border-top: 1px solid rgba(201,168,76,0.1);
            padding: 36px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer-logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 700;
        }
        .footer-logo span { color: var(--gold); }
        .footer-copy { font-size: 0.72rem; color: var(--text-muted); }
        .footer-links { display: flex; gap: 20px; }
        .footer-link {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-decoration: none;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            transition: color 0.3s;
        }
        .footer-link:hover { color: var(--gold); }

        @media (max-width: 960px) {
            nav { padding: 18px 24px; }
            .hero-content { padding: 0 24px; padding-top: 90px; }
            .flights-section, .destinations { padding-left: 24px; padding-right: 24px; }
            .flights-grid { grid-template-columns: 1fr 1fr; }
            .dest-grid { grid-template-columns: 1fr 1fr; grid-template-rows: auto; }
            .dest-card:first-child { grid-row: span 1; grid-column: span 2; }
            footer { flex-direction: column; gap: 16px; text-align: center; padding: 28px 24px; }
        }
    </style>
</head>
<body>

<div class="cursor-dot" id="cursorDot"></div>
<div class="cursor-ring" id="cursorRing"></div>

<!-- ── NAVIGATION ── -->
<nav>
    <a href="index.php" class="logo">
        <span class="logo-icon">✈</span>
        Sky<span>Line</span>
    </a>
    <div class="nav-links">
        <button onclick="scrollToFlights()" class="nav-btn">Flights</button>
        <button onclick="scrollToDestinations()" class="nav-btn">Destinations</button>
        <?php if(isset($_SESSION['user_id'])): ?>
            <span class="user-chip">✦ <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <button onclick="openProfileModal()" class="nav-btn gold">👤 Profile</button>
            <button onclick="openContactModal()" class="nav-btn gold">📩 Support</button>
            <a href="logout_user.php" class="nav-btn danger">Logout</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ── HERO ── -->
<section class="hero">
    <div class="hero-img"></div>
    <div class="hero-content">
        <div class="hero-eyebrow">
            <span class="eyebrow-line"></span>
            <span class="eyebrow-text">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
        <h1 class="hero-title">
            Discover the world<br>
            <em>from above.</em>
        </h1>
        <p class="hero-sub">Premium flights, effortless booking, and experiences that elevate your journey.</p>

        <!-- Search pill -->
        <form action="search.php" method="GET" style="opacity:0;animation:fadeUp 0.9s 0.8s forwards">
            <div class="search-pill">
                <div class="search-field">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" id="dest-input" name="destination" placeholder="Where to?" required>
                </div>
                <div class="search-counter">
                    <button type="button" class="counter-btn" id="btn-minus">−</button>
                    <div class="counter-display">
                        <span class="counter-num" id="p-count">1</span>
                        <span class="counter-label">Travellers</span>
                    </div>
                    <input type="hidden" name="passengers" id="p-input" value="1">
                    <button type="button" class="counter-btn" id="btn-plus">+</button>
                </div>
                <button type="submit" class="search-submit">Find Flights →</button>
            </div>
        </form>
    </div>
</section>

<!-- ── FEATURED FLIGHTS ── -->
<section class="flights-section" id="flights-section">
    <div class="section-eyebrow">
        <span class="eyebrow-line"></span>
        <span class="eyebrow-text">Available Routes</span>
    </div>
    <h2 class="section-title">Book your next <em>flight.</em></h2>
    <p class="section-desc">Real-time availability on our most popular international routes.</p>

    <div class="flights-grid">

        <!-- Paris -->
        <div class="flight-card" onclick="document.getElementById('dest-input').value='Paris';scrollToHero()">
            <div class="flight-img">
                <div class="flight-img-inner" style="background-image:url('https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=800&q=80')"></div>
                <span class="flight-tag">Most Popular</span>
                <span class="flight-code">PAR</span>
            </div>
            <div class="flight-body">
                <div class="flight-city">Paris</div>
                <div class="flight-country">France · CDG Airport</div>
                <div class="flight-details">
                    <div>
                        <div class="flight-price-label">From</div>
                        <div class="flight-price">₹42,000 <span>· Economy</span></div>
                    </div>
                    <a href="search.php?destination=Paris&passengers=1" class="flight-btn">Book Now</a>
                </div>
            </div>
        </div>

        <!-- Dubai -->
        <div class="flight-card" onclick="document.getElementById('dest-input').value='Dubai';scrollToHero()">
            <div class="flight-img">
                <div class="flight-img-inner" style="background-image:url('https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=800&q=80')"></div>
                <span class="flight-tag">Business Class</span>
                <span class="flight-code">DXB</span>
            </div>
            <div class="flight-body">
                <div class="flight-city">Dubai</div>
                <div class="flight-country">UAE · DXB Airport</div>
                <div class="flight-details">
                    <div>
                        <div class="flight-price-label">From</div>
                        <div class="flight-price">₹18,500</div>
                    </div>
                    <a href="search.php?destination=Dubai&passengers=1" class="flight-btn">Book Now</a>
                </div>
            </div>
        </div>

        <!-- Singapore -->
        <div class="flight-card" onclick="document.getElementById('dest-input').value='Singapore';scrollToHero()">
            <div class="flight-img">
                <div class="flight-img-inner" style="background-image:url('https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=800&q=80')"></div>
                <span class="flight-tag">New Route</span>
                <span class="flight-code">SGP</span>
            </div>
            <div class="flight-body">
                <div class="flight-city">Singapore</div>
                <div class="flight-country">Singapore · Changi Airport</div>
                <div class="flight-details">
                    <div>
                        <div class="flight-price-label">From</div>
                        <div class="flight-price">₹22,000</div>
                    </div>
                    <a href="search.php?destination=Singapore&passengers=1" class="flight-btn">Book Now</a>
                </div>
            </div>
        </div>

        <!-- New York -->
        <div class="flight-card" onclick="document.getElementById('dest-input').value='New York';scrollToHero()">
            <div class="flight-img">
                <div class="flight-img-inner" style="background-image:url('https://images.unsplash.com/photo-1522083165195-3424ed129620?w=800&q=80')"></div>
                <span class="flight-tag">International</span>
                <span class="flight-code">NYC</span>
            </div>
            <div class="flight-body">
                <div class="flight-city">New York</div>
                <div class="flight-country">USA · JFK Airport</div>
                <div class="flight-details">
                    <div>
                        <div class="flight-price-label">From</div>
                        <div class="flight-price">₹58,000</div>
                    </div>
                    <a href="search.php?destination=New York&passengers=1" class="flight-btn">Book Now</a>
                </div>
            </div>
        </div>

        <!-- Tokyo -->
        <div class="flight-card" onclick="document.getElementById('dest-input').value='Tokyo';scrollToHero()">
            <div class="flight-img">
                <div class="flight-img-inner" style="background-image:url('https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=800&q=80')"></div>
                <span class="flight-tag">Asia-Pacific</span>
                <span class="flight-code">TYO</span>
            </div>
            <div class="flight-body">
                <div class="flight-city">Tokyo</div>
                <div class="flight-country">Japan · Narita Airport</div>
                <div class="flight-details">
                    <div>
                        <div class="flight-price-label">From</div>
                        <div class="flight-price">₹39,000</div>
                    </div>
                    <a href="search.php?destination=Tokyo&passengers=1" class="flight-btn">Book Now</a>
                </div>
            </div>
        </div>

        <!-- London -->
        <div class="flight-card" onclick="document.getElementById('dest-input').value='London';scrollToHero()">
            <div class="flight-img">
                <div class="flight-img-inner" style="background-image:url('https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=800&q=80')"></div>
                <span class="flight-tag">Classic Route</span>
                <span class="flight-code">LON</span>
            </div>
            <div class="flight-body">
                <div class="flight-city">London</div>
                <div class="flight-country">United Kingdom · Heathrow</div>
                <div class="flight-details">
                    <div>
                        <div class="flight-price-label">From</div>
                        <div class="flight-price">₹46,000</div>
                    </div>
                    <a href="search.php?destination=London&passengers=1" class="flight-btn">Book Now</a>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ── DESTINATIONS ── -->
<section class="destinations" id="destinations-section">
    <div class="section-eyebrow">
        <span class="eyebrow-line"></span>
        <span class="eyebrow-text">Popular Routes</span>
    </div>
    <h2 class="section-title">The world is <em>calling.</em></h2>
    <p class="section-desc">Handpicked destinations loved by SkyLine travellers this season.</p>

    <div class="dest-grid">
        <div class="dest-card">
            <div class="dest-bg"></div>
            <div class="dest-city-art">PAR</div>
            <div class="dest-overlay">
                <span class="dest-tag">Most Popular</span>
                <div class="dest-name">Paris, France</div>
                <div class="dest-price">From <strong>₹42,000</strong> · Economy</div>
                <a href="search.php?destination=Paris&passengers=1" class="dest-book-btn">Book Flight →</a>
            </div>
        </div>
        <div class="dest-card">
            <div class="dest-bg"></div>
            <div class="dest-city-art">DXB</div>
            <div class="dest-overlay">
                <span class="dest-tag">Business Class</span>
                <div class="dest-name">Dubai, UAE</div>
                <div class="dest-price">From <strong>₹18,500</strong></div>
                <a href="search.php?destination=Dubai&passengers=1" class="dest-book-btn">Book Flight →</a>
            </div>
        </div>
        <div class="dest-card">
            <div class="dest-bg"></div>
            <div class="dest-city-art">SGP</div>
            <div class="dest-overlay">
                <span class="dest-tag">New Route</span>
                <div class="dest-name">Singapore</div>
                <div class="dest-price">From <strong>₹22,000</strong></div>
                <a href="search.php?destination=Singapore&passengers=1" class="dest-book-btn">Book Flight →</a>
            </div>
        </div>
        <div class="dest-card">
            <div class="dest-bg"></div>
            <div class="dest-city-art">NYC</div>
            <div class="dest-overlay">
                <span class="dest-tag">International</span>
                <div class="dest-name">New York, USA</div>
                <div class="dest-price">From <strong>₹58,000</strong></div>
                <a href="search.php?destination=New York&passengers=1" class="dest-book-btn">Book Flight →</a>
            </div>
        </div>
        <div class="dest-card">
            <div class="dest-bg"></div>
            <div class="dest-city-art">TYO</div>
            <div class="dest-overlay">
                <span class="dest-tag">Asia-Pacific</span>
                <div class="dest-name">Tokyo, Japan</div>
                <div class="dest-price">From <strong>₹39,000</strong></div>
                <a href="search.php?destination=Tokyo&passengers=1" class="dest-book-btn">Book Flight →</a>
            </div>
        </div>
    </div>
</section>

<!-- ── FOOTER ── -->
<footer>
    <div class="footer-logo">Sky<span>Line</span> · LAGH Airlines</div>
    <div class="footer-copy">© 2024 SkyLine Airlines. All rights reserved.</div>
    <div class="footer-links">
        <a href="#" onclick="openProfileModal()" class="footer-link">My Profile</a>
        <a href="#" onclick="openContactModal()" class="footer-link">Contact Admin</a>
        <a href="logout_user.php" class="footer-link">Logout</a>
    </div>
</footer>

<!-- ===== PROFILE MODAL ===== -->
<div id="profileModal" class="modal-overlay" onclick="if(event.target===this) closeProfileModal()">
    <div class="modal-box">
        <button class="modal-close" onclick="closeProfileModal()">✕</button>
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px">
            <div style="width:44px;height:44px;background:var(--gold-dim);border:1px solid rgba(201,168,76,0.25);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem">👤</div>
            <div>
                <h3 style="color:var(--cream);font-size:1.2rem;font-weight:700;margin:0;font-family:'Playfair Display',serif">My Profile</h3>
                <p style="color:var(--text-muted);font-size:0.78rem;margin:0">Update your personal details</p>
            </div>
        </div>
        <div class="modal-divider"></div>

        <?php if($profileMsg): ?>
        <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);border-radius:12px;padding:12px 16px;color:#86efac;font-size:13px;margin-bottom:16px">
            ✅ <?= htmlspecialchars($profileMsg) ?>
        </div>
        <?php endif; ?>
        <?php if($profileErr): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:12px;padding:12px 16px;color:#fca5a5;font-size:13px;margin-bottom:16px">
            ⚠️ <?= htmlspecialchars($profileErr) ?>
        </div>
        <?php endif; ?>

        <form method="POST" style="display:flex;flex-direction:column;gap:16px">
            <div>
                <label class="modal-label">Full Name</label>
                <input type="text" name="p_name" required class="modal-input" value="<?= htmlspecialchars($u_data['name'] ?? '') ?>">
            </div>
            <div>
                <label class="modal-label">New Password <span style="color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0"> — leave blank to keep current</span></label>
                <div style="position:relative">
                    <input type="password" name="p_password" id="profilePass" placeholder="Enter new password" class="modal-input" style="padding-right:48px">
                    <button type="button" onclick="toggleProfilePass()" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:4px;display:flex">
                        <svg id="profileEye" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>
            <div>
                <label class="modal-label">Date of Birth</label>
                <input type="date" name="p_dob" class="modal-input" value="<?= htmlspecialchars($u_data['dob'] ?? '') ?>">
            </div>
            <div>
                <label class="modal-label">Contact Number</label>
                <input type="tel" name="p_contact" placeholder="9876543210" class="modal-input" value="<?= htmlspecialchars($u_data['contact'] ?? '') ?>">
            </div>
            <button type="submit" name="update_profile" class="btn-modal-submit">Save Changes ✓</button>
        </form>
    </div>
</div>

<!-- ===== CONTACT ADMIN MODAL ===== -->
<div id="contactModal" class="modal-overlay" onclick="if(event.target===this) closeContactModal()">
    <div class="modal-box" style="max-width:480px">
        <button class="modal-close" onclick="closeContactModal()">✕</button>
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:8px">
            <div style="width:44px;height:44px;background:var(--gold-dim);border:1px solid rgba(201,168,76,0.25);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem">📩</div>
            <div>
                <h3 style="color:var(--cream);font-size:1.2rem;font-weight:700;margin:0;font-family:'Playfair Display',serif">Contact Admin</h3>
                <p style="color:var(--text-muted);font-size:0.78rem;margin:0">Send a message to the admin team</p>
            </div>
        </div>
        <div class="modal-divider"></div>

        <div id="contactSuccess" style="display:none;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);border-radius:12px;padding:16px;color:#86efac;font-size:14px;text-align:center;margin-bottom:16px">
            ✅ Message sent! The admin will get back to you.
        </div>
        <div id="contactError" style="display:none;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:12px;padding:12px 16px;color:#fca5a5;font-size:13px;margin-bottom:16px"></div>

        <div id="contactForm" style="display:flex;flex-direction:column;gap:16px">
            <div>
                <label class="modal-label">Subject</label>
                <input id="cSubject" type="text" placeholder="e.g. Issue with my booking" class="modal-input">
            </div>
            <div>
                <label class="modal-label">Message</label>
                <textarea id="cMessage" rows="4" placeholder="Describe your issue or query..." class="modal-input" style="resize:vertical;min-height:100px"></textarea>
            </div>
            <button onclick="sendContactMessage()" id="cSendBtn" class="btn-modal-submit">Send Message →</button>
        </div>
    </div>
</div>

<script>
    // Custom cursor
    const dot = document.getElementById('cursorDot');
    const ring = document.getElementById('cursorRing');
    let mx = 0, my = 0, rx = 0, ry = 0;
    document.addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; dot.style.left = mx+'px'; dot.style.top = my+'px'; });
    function animateCursor() { rx += (mx-rx)*0.12; ry += (my-ry)*0.12; ring.style.left=rx+'px'; ring.style.top=ry+'px'; requestAnimationFrame(animateCursor); }
    animateCursor();
    document.querySelectorAll('a, button, .flight-card, .dest-card').forEach(el => {
        el.addEventListener('mouseenter', () => { ring.style.transform='translate(-50%,-50%) scale(1.8)'; ring.style.opacity='0.4'; dot.style.transform='translate(-50%,-50%) scale(0.5)'; });
        el.addEventListener('mouseleave', () => { ring.style.transform='translate(-50%,-50%) scale(1)'; ring.style.opacity='0.6'; dot.style.transform='translate(-50%,-50%) scale(1)'; });
    });

    // Passenger counter
    document.getElementById('btn-minus').addEventListener('click', () => updateQty(-1));
    document.getElementById('btn-plus').addEventListener('click',  () => updateQty(1));
    function updateQty(change) {
        const display = document.getElementById('p-count');
        const input   = document.getElementById('p-input');
        let val = parseInt(display.textContent) + change;
        if (val >= 1 && val <= 10) { display.textContent = val; input.value = val; }
    }

    // Scroll helpers
    function scrollToHero() { window.scrollTo({ top: 0, behavior: 'smooth' }); setTimeout(() => document.getElementById('dest-input').focus(), 600); }
    function scrollToFlights() { document.getElementById('flights-section').scrollIntoView({ behavior: 'smooth' }); }
    function scrollToDestinations() { document.getElementById('destinations-section').scrollIntoView({ behavior: 'smooth' }); }

    // Profile modal
    function openProfileModal()  { document.getElementById('profileModal').style.display='flex'; }
    function closeProfileModal() { document.getElementById('profileModal').style.display='none'; }
    function toggleProfilePass() {
        const inp = document.getElementById('profilePass');
        const eye = document.getElementById('profileEye');
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        eye.innerHTML = show
            ? `<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`
            : `<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
    }
    <?php if($profileMsg || $profileErr): ?>
    window.addEventListener('DOMContentLoaded', openProfileModal);
    <?php endif; ?>

    // Contact modal
    function openContactModal() {
        document.getElementById('contactModal').style.display='flex';
        document.getElementById('contactSuccess').style.display='none';
        document.getElementById('contactError').style.display='none';
        document.getElementById('contactForm').style.display='flex';
        document.getElementById('cSubject').value='';
        document.getElementById('cMessage').value='';
        document.getElementById('cSendBtn').disabled=false;
        document.getElementById('cSendBtn').textContent='Send Message →';
    }
    function closeContactModal() { document.getElementById('contactModal').style.display='none'; }

    async function sendContactMessage() {
        const subject = document.getElementById('cSubject').value.trim();
        const message = document.getElementById('cMessage').value.trim();
        const errEl   = document.getElementById('contactError');
        errEl.style.display='none';
        if (!subject || !message) { errEl.textContent='Please fill in both subject and message.'; errEl.style.display='block'; return; }
        const btn = document.getElementById('cSendBtn');
        btn.disabled=true; btn.textContent='Sending...';
        const fd = new FormData();
        fd.append('subject', subject); fd.append('message', message);
        try {
            const res  = await fetch('contact_admin.php', { method:'POST', body:fd });
            const data = await res.json();
            if (data.success) {
                document.getElementById('contactForm').style.display='none';
                document.getElementById('contactSuccess').style.display='block';
                setTimeout(closeContactModal, 3000);
            } else {
                errEl.textContent=data.error||'Failed. Please try again.'; errEl.style.display='block';
                btn.disabled=false; btn.textContent='Send Message →';
            }
        } catch(e) {
            errEl.textContent='Network error. Please try again.'; errEl.style.display='block';
            btn.disabled=false; btn.textContent='Send Message →';
        }
    }

    // Scroll animations
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity='1';
                entry.target.style.transform='translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.flight-card, .dest-card').forEach((el, i) => {
        el.style.opacity='0';
        el.style.transform='translateY(28px)';
        el.style.transition=`opacity 0.6s ${i*0.08}s, transform 0.6s ${i*0.08}s`;
        observer.observe(el);
    });
</script>
</body>
</html>
