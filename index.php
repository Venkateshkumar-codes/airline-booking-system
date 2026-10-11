<?php
session_start();
// If already logged in, redirect to dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkyLine — LAGH Airlines</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --gold: #C9A84C;
            --gold-light: #E8C97A;
            --gold-dim: rgba(201,168,76,0.15);
            --cream: #F5F0E8;
            --navy: #090D1A;
            --navy-mid: #111827;
            --navy-card: rgba(10, 15, 35, 0.85);
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

        /* ── NOISE TEXTURE OVERLAY ── */
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
            padding: 28px 60px;
            z-index: 100;
            background: linear-gradient(to bottom, rgba(9,13,26,0.95) 0%, transparent 100%);
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
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
        .nav-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 500;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            padding: 10px 20px;
            border-radius: 100px;
            transition: all 0.3s;
        }
        .nav-link:hover { color: var(--cream); background: rgba(255,255,255,0.05); }

        .btn-login {
            background: var(--gold);
            color: var(--navy);
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            padding: 12px 28px;
            border-radius: 100px;
            transition: all 0.3s;
            box-shadow: 0 0 30px rgba(201,168,76,0.3);
        }
        .btn-login:hover {
            background: var(--gold-light);
            box-shadow: 0 0 45px rgba(201,168,76,0.5);
            transform: translateY(-1px);
        }

        /* ── HERO ── */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        /* Atmospheric gradient sky */
        .hero-bg {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 50% 110%, rgba(201,168,76,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 20% 50%, rgba(30,60,120,0.3) 0%, transparent 60%),
                radial-gradient(ellipse 70% 50% at 80% 30%, rgba(15,30,80,0.4) 0%, transparent 60%),
                linear-gradient(160deg, #0a1628 0%, #090d1a 40%, #07111E 100%);
        }

        /* Horizontal gold line accent */
        .hero-line {
            position: absolute;
            left: 0; right: 0;
            bottom: 35%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(201,168,76,0.3) 30%, rgba(201,168,76,0.3) 70%, transparent);
        }

        /* Decorative circle */
        .hero-circle {
            position: absolute;
            right: -180px;
            top: 50%;
            transform: translateY(-50%);
            width: 700px; height: 700px;
            border-radius: 50%;
            border: 1px solid rgba(201,168,76,0.1);
            box-shadow: inset 0 0 120px rgba(201,168,76,0.04);
        }
        .hero-circle::before {
            content: '';
            position: absolute;
            inset: 40px;
            border-radius: 50%;
            border: 1px solid rgba(201,168,76,0.07);
        }
        .hero-circle::after {
            content: '';
            position: absolute;
            inset: 80px;
            border-radius: 50%;
            border: 1px solid rgba(201,168,76,0.05);
        }

        /* Flying plane accent */
        .plane-trail {
            position: absolute;
            top: 22%;
            left: -5%;
            width: 110%;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, rgba(201,168,76,0.0) 30%, rgba(201,168,76,0.5) 60%, rgba(201,168,76,0.15) 80%, transparent);
            transform: rotate(-8deg);
            animation: trailFade 4s ease-in-out infinite;
        }
        @keyframes trailFade {
            0%, 100% { opacity: 0; transform: rotate(-8deg) scaleX(0.3); }
            50% { opacity: 1; transform: rotate(-8deg) scaleX(1); }
        }
        .plane-icon {
            position: absolute;
            top: calc(22% - 14px);
            right: 8%;
            font-size: 1.8rem;
            transform: rotate(-8deg);
            animation: planeFly 4s ease-in-out infinite;
            filter: drop-shadow(0 0 12px rgba(201,168,76,0.8));
        }
        @keyframes planeFly {
            0%, 100% { opacity: 0; right: 8%; }
            40% { opacity: 1; }
            50% { opacity: 1; right: 6%; }
            60% { opacity: 0; right: 4%; }
        }

        .hero-content {
            position: relative;
            z-index: 10;
            padding: 0 60px;
            max-width: 900px;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
            opacity: 0;
            animation: fadeUp 0.8s 0.2s forwards;
        }
        .eyebrow-line {
            display: block;
            width: 40px; height: 1px;
            background: var(--gold);
        }
        .eyebrow-text {
            font-size: 0.7rem;
            font-weight: 500;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--gold);
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(3.5rem, 7vw, 6.5rem);
            font-weight: 900;
            line-height: 1.0;
            letter-spacing: -0.02em;
            margin-bottom: 28px;
            opacity: 0;
            animation: fadeUp 0.9s 0.4s forwards;
        }
        .hero-title em {
            font-style: italic;
            color: var(--gold);
        }

        .hero-sub {
            font-size: 1.05rem;
            line-height: 1.75;
            color: var(--text-muted);
            max-width: 480px;
            margin-bottom: 52px;
            font-weight: 300;
            opacity: 0;
            animation: fadeUp 0.9s 0.6s forwards;
        }

        .hero-cta {
            display: flex;
            gap: 16px;
            align-items: center;
            opacity: 0;
            animation: fadeUp 0.9s 0.8s forwards;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--gold);
            color: var(--navy);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 18px 36px;
            border-radius: 100px;
            transition: all 0.3s cubic-bezier(0.25,0.46,0.45,0.94);
            box-shadow: 0 8px 40px rgba(201,168,76,0.35);
        }
        .btn-primary:hover {
            background: var(--gold-light);
            transform: translateY(-2px);
            box-shadow: 0 16px 50px rgba(201,168,76,0.45);
        }
        .btn-primary svg { width: 18px; height: 18px; }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--cream);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 18px 24px;
            transition: all 0.3s;
            opacity: 0.7;
        }
        .btn-ghost:hover { opacity: 1; gap: 14px; }
        .btn-ghost svg { width: 16px; height: 16px; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── STATS BAR ── */
        .stats-bar {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            display: flex;
            justify-content: center;
            gap: 0;
            z-index: 10;
            border-top: 1px solid rgba(201,168,76,0.1);
            background: rgba(9,13,26,0.6);
            backdrop-filter: blur(20px);
        }
        .stat-item {
            padding: 28px 56px;
            text-align: center;
            border-right: 1px solid rgba(201,168,76,0.1);
            flex: 1;
            max-width: 220px;
        }
        .stat-item:last-child { border-right: none; }
        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--gold);
            line-height: 1;
            margin-bottom: 6px;
        }
        .stat-label {
            font-size: 0.65rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        /* ── FEATURES SECTION ── */
        .section {
            padding: 120px 60px;
            position: relative;
        }

        .section-eyebrow {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .section-eyebrow .eyebrow-line { width: 30px; }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.2rem, 4vw, 3.4rem);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin-bottom: 16px;
        }
        .section-title em { font-style: italic; color: var(--gold); }

        .section-desc {
            font-size: 1rem;
            color: var(--text-muted);
            line-height: 1.7;
            max-width: 420px;
            margin-bottom: 64px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: rgba(201,168,76,0.1);
            border: 1px solid rgba(201,168,76,0.1);
            border-radius: 24px;
            overflow: hidden;
        }

        .feature-card {
            background: var(--navy-mid);
            padding: 48px 40px;
            transition: background 0.3s;
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 80% 60% at 0% 100%, var(--gold-dim) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s;
        }
        .feature-card:hover::before { opacity: 1; }
        .feature-card:hover { background: rgba(20,28,50,1); }

        .feature-icon {
            width: 52px; height: 52px;
            background: var(--gold-dim);
            border: 1px solid rgba(201,168,76,0.25);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 28px;
            transition: all 0.3s;
        }
        .feature-card:hover .feature-icon {
            background: rgba(201,168,76,0.25);
            box-shadow: 0 0 20px rgba(201,168,76,0.2);
        }

        .feature-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--cream);
        }
        .feature-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.7;
        }

        /* ── DESTINATIONS ── */
        .destinations {
            padding: 0 60px 120px;
        }
        .dest-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: 280px 280px;
            gap: 12px;
            margin-top: 60px;
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
            transition: transform 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dest-card:hover .dest-bg { transform: scale(1.05); }

        .dest-city-art {
            font-size: 6rem;
            opacity: 0.15;
            user-select: none;
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            white-space: nowrap;
            color: white;
            text-shadow: 0 2px 20px rgba(0,0,0,0.5);
        }
        .dest-card:first-child .dest-city-art { font-size: 10rem; }

        .dest-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(5,8,20,0.98) 0%, rgba(5,8,20,0.55) 45%, rgba(5,8,20,0.2) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 28px;
        }
        .dest-tag {
            display: inline-block;
            background: var(--gold-dim);
            border: 1px solid rgba(201,168,76,0.3);
            color: var(--gold);
            font-size: 0.62rem;
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
            margin-bottom: 4px;
        }
        .dest-card:first-child .dest-name { font-size: 2.4rem; }
        .dest-price {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .dest-price strong { color: var(--gold-light); }

        /* Real city image backgrounds */
        .dest-bg { background-size: cover; background-position: center; }
        .dest-card:nth-child(1) .dest-bg { background-image: url('https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=900&q=80'); }
        .dest-card:nth-child(2) .dest-bg { background-image: url('https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=700&q=80'); }
        .dest-card:nth-child(3) .dest-bg { background-image: url('https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=700&q=80'); }
        .dest-card:nth-child(4) .dest-bg { background-image: url('https://images.unsplash.com/photo-1522083165195-3424ed129620?w=700&q=80'); }
        .dest-card:nth-child(5) .dest-bg { background-image: url('https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=700&q=80'); }

        /* ── BOOKING CTA SECTION ── */
        .booking-section {
            margin: 0 60px 120px;
            background: linear-gradient(135deg, rgba(201,168,76,0.08) 0%, rgba(9,13,26,0) 60%);
            border: 1px solid rgba(201,168,76,0.15);
            border-radius: 32px;
            padding: 80px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        .booking-section::before {
            content: '✈';
            position: absolute;
            right: 80px;
            top: 50%;
            transform: translateY(-50%) rotate(45deg);
            font-size: 16rem;
            opacity: 0.025;
            font-family: sans-serif;
            line-height: 1;
        }

        .booking-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 3.5vw, 3rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 20px;
        }
        .booking-title em { font-style: italic; color: var(--gold); }
        .booking-desc {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.75;
        }

        .booking-form {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-label {
            font-size: 0.65rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 600;
        }
        .form-input {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(201,168,76,0.2);
            border-radius: 12px;
            padding: 14px 18px;
            color: var(--cream);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s;
            width: 100%;
        }
        .form-input::placeholder { color: rgba(245,240,232,0.25); }
        .form-input:focus {
            border-color: rgba(201,168,76,0.5);
            background: rgba(201,168,76,0.05);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        .btn-book {
            background: var(--gold);
            color: var(--navy);
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 4px;
            box-shadow: 0 6px 30px rgba(201,168,76,0.3);
        }
        .btn-book:hover {
            background: var(--gold-light);
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(201,168,76,0.4);
        }

        /* ── FOOTER ── */
        footer {
            border-top: 1px solid rgba(201,168,76,0.1);
            padding: 40px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer-logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--cream);
        }
        .footer-logo span { color: var(--gold); }
        .footer-copy {
            font-size: 0.75rem;
            color: var(--text-muted);
            letter-spacing: 0.05em;
        }
        .footer-links {
            display: flex;
            gap: 24px;
        }
        .footer-link {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-decoration: none;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            transition: color 0.3s;
        }
        .footer-link:hover { color: var(--gold); }

        /* ── SCROLL INDICATOR ── */
        .scroll-hint {
            position: absolute;
            bottom: 100px;
            right: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            opacity: 0;
            animation: fadeUp 1s 1.2s forwards;
            z-index: 10;
        }
        .scroll-hint span {
            font-size: 0.6rem;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--text-muted);
            writing-mode: vertical-rl;
        }
        .scroll-line {
            width: 1px;
            height: 60px;
            background: linear-gradient(to bottom, var(--gold), transparent);
            animation: scrollPulse 1.5s ease-in-out infinite;
        }
        @keyframes scrollPulse {
            0%, 100% { transform: scaleY(1); opacity: 1; }
            50% { transform: scaleY(0.5); opacity: 0.5; }
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            nav { padding: 20px 24px; }
            .hero-content { padding: 0 24px; }
            .section { padding: 80px 24px; }
            .destinations { padding: 0 24px 80px; }
            .booking-section { margin: 0 24px 80px; padding: 40px 28px; grid-template-columns: 1fr; }
            .features-grid { grid-template-columns: 1fr; }
            .dest-grid { grid-template-columns: 1fr 1fr; grid-template-rows: auto; }
            .dest-card:first-child { grid-row: span 1; grid-column: span 2; }
            footer { flex-direction: column; gap: 20px; text-align: center; padding: 30px 24px; }
            .stats-bar { flex-wrap: wrap; }
            .stat-item { border-right: none; border-bottom: 1px solid rgba(201,168,76,0.1); flex: 0 0 50%; }
        }
    </style>
</head>
<body>

    <!-- Custom Cursor -->
    <div class="cursor-dot" id="cursorDot"></div>
    <div class="cursor-ring" id="cursorRing"></div>

    <!-- ── NAVIGATION ── -->
    <nav>
        <a href="index.php" class="logo">
            <span class="logo-icon">✈</span>
            Sky<span>Line</span>
        </a>
        <div class="nav-links">
            <a href="#destinations" class="nav-link">Destinations</a>
            <a href="#features" class="nav-link">Services</a>
            <a href="#book" class="nav-link">Book</a>
            <a href="login.php" class="nav-link">Admin</a>
            <a href="login_user.php" class="btn-login">Sign In →</a>
        </div>
    </nav>

    <!-- ── HERO ── -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-line"></div>
        <div class="hero-circle"></div>
        <div class="plane-trail"></div>
        <div class="plane-icon">✈</div>

        <div class="hero-content">
            <div class="hero-eyebrow">
                <span class="eyebrow-line"></span>
                <span class="eyebrow-text">LAGH Airlines · Est. 2024</span>
            </div>

            <h1 class="hero-title">
                Where every<br>
                journey becomes<br>
                <em>legendary.</em>
            </h1>

            <p class="hero-sub">
                Premium air travel redefined. Book flights to destinations worldwide with unmatched elegance, comfort, and ease.
            </p>

            <div class="hero-cta">
                <a href="login_user.php" class="btn-primary">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Sign In to Book
                </a>

            </div>
        </div>

        <div class="scroll-hint">
            <span>Scroll</span>
            <div class="scroll-line"></div>
        </div>

        <!-- Stats bar -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number">120+</div>
                <div class="stat-label">Destinations</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">2.4M</div>
                <div class="stat-label">Happy Travellers</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">98%</div>
                <div class="stat-label">On-Time Rate</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">4.9★</div>
                <div class="stat-label">Passenger Rating</div>
            </div>
        </div>
    </section>

    <!-- ── FEATURES ── -->
    <section class="section" id="features">
        <div class="section-eyebrow">
            <span class="eyebrow-line"></span>
            <span class="eyebrow-text">Why SkyLine</span>
        </div>
        <h2 class="section-title">Travel that feels <em>effortless.</em></h2>
        <p class="section-desc">Every touchpoint crafted to make your journey seamless — from first click to final landing.</p>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <div class="feature-title">Instant Booking</div>
                <p class="feature-desc">Search, select, and confirm your flight in under two minutes. Real-time seat availability across all routes.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <div class="feature-title">Secure & Protected</div>
                <p class="feature-desc">Your data and payments are fully encrypted. Book with confidence knowing every transaction is safe.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌐</div>
                <div class="feature-title">Global Network</div>
                <p class="feature-desc">120+ destinations across 6 continents. Wherever you want to go, SkyLine gets you there with style.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <div class="feature-title">Digital Receipts</div>
                <p class="feature-desc">Instant booking confirmations and e-receipts sent directly to you. All your trips in one place.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎒</div>
                <div class="feature-title">Flexible Luggage</div>
                <p class="feature-desc">Choose the baggage plan that fits your trip. From carry-on only to generous checked allowances.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <div class="feature-title">Premium Service</div>
                <p class="feature-desc">Award-winning cabin crew and in-flight experience. Rated #1 for customer satisfaction four years running.</p>
            </div>
        </div>
    </section>

    <!-- ── DESTINATIONS ── -->
    <section class="destinations" id="destinations">
        <div class="section-eyebrow">
            <span class="eyebrow-line"></span>
            <span class="eyebrow-text">Popular Routes</span>
        </div>
        <h2 class="section-title">The world is <em>calling.</em></h2>
        <p class="section-desc">Handpicked destinations loved by SkyLine travellers this season.</p>

        <div class="dest-grid">
            <div class="dest-card">
                <div class="dest-bg"><div class="dest-city-art">PAR</div></div>
                <div class="dest-overlay">
                    <span class="dest-tag">Most Popular</span>
                    <div class="dest-name">Paris, France</div>
                    <div class="dest-price">From <strong>₹42,000</strong> · Economy</div>
                </div>
            </div>
            <div class="dest-card">
                <div class="dest-bg"><div class="dest-city-art">DXB</div></div>
                <div class="dest-overlay">
                    <span class="dest-tag">Business Class</span>
                    <div class="dest-name">Dubai, UAE</div>
                    <div class="dest-price">From <strong>₹18,500</strong></div>
                </div>
            </div>
            <div class="dest-card">
                <div class="dest-bg"><div class="dest-city-art">SGP</div></div>
                <div class="dest-overlay">
                    <span class="dest-tag">New Route</span>
                    <div class="dest-name">Singapore</div>
                    <div class="dest-price">From <strong>₹22,000</strong></div>
                </div>
            </div>
            <div class="dest-card">
                <div class="dest-bg"><div class="dest-city-art">NYC</div></div>
                <div class="dest-overlay">
                    <span class="dest-tag">International</span>
                    <div class="dest-name">New York, USA</div>
                    <div class="dest-price">From <strong>₹58,000</strong></div>
                </div>
            </div>
            <div class="dest-card">
                <div class="dest-bg"><div class="dest-city-art">TYO</div></div>
                <div class="dest-overlay">
                    <span class="dest-tag">Asia-Pacific</span>
                    <div class="dest-name">Tokyo, Japan</div>
                    <div class="dest-price">From <strong>₹39,000</strong></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── BOOKING CTA ── -->
    <section class="booking-section" id="book">
        <div>
            <div class="section-eyebrow" style="margin-bottom:14px;">
                <span class="eyebrow-line"></span>
                <span class="eyebrow-text">Ready to fly?</span>
            </div>
            <h2 class="booking-title">Your next <em>adventure</em> starts here.</h2>
            <p class="booking-desc" style="margin-top:16px;">
                Sign in to your SkyLine account to search real-time flight availability and complete your booking in seconds. New here? Registration takes just a moment.
            </p>
            <div style="display:flex;gap:14px;margin-top:36px;flex-wrap:wrap;">
                <a href="login_user.php" class="btn-primary" style="text-decoration:none;">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Login to Book
                </a>
                <a href="user_setup.php" class="btn-ghost" style="text-decoration:none;padding:18px 0;opacity:0.8;">
                    Create Account →
                </a>
            </div>
        </div>
        <div class="booking-form">
            <div class="form-group">
                <label class="form-label">Where to?</label>
                <input type="text" class="form-input" placeholder="City or airport" readonly onfocus="this.blur(); window.location='login_user.php';">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Departure</label>
                    <input type="date" class="form-input" readonly onfocus="this.blur(); window.location='login_user.php';">
                </div>
                <div class="form-group">
                    <label class="form-label">Passengers</label>
                    <input type="number" class="form-input" placeholder="1" min="1" max="10" readonly onfocus="this.blur(); window.location='login_user.php';">
                </div>
            </div>
            <button class="btn-book" onclick="window.location='login_user.php'">
                🔐 &nbsp; Sign In to Search Flights
            </button>
            <p style="font-size:0.72rem;color:var(--text-muted);text-align:center;margin-top:4px;">Login required to view real-time availability</p>
        </div>
    </section>

    <!-- ── FOOTER ── -->
    <footer>
        <div class="footer-logo">Sky<span>Line</span> · LAGH Airlines</div>
        <div class="footer-copy">© 2024 SkyLine Airlines. All rights reserved.</div>
        <div class="footer-links">
            <a href="login_user.php" class="footer-link">Login</a>
            <a href="user_setup.php" class="footer-link">Register</a>
            <a href="login.php" class="footer-link">Admin</a>
        </div>
    </footer>

    <script>
        // Custom cursor
        const dot = document.getElementById('cursorDot');
        const ring = document.getElementById('cursorRing');
        let mx = 0, my = 0, rx = 0, ry = 0;

        document.addEventListener('mousemove', e => {
            mx = e.clientX; my = e.clientY;
            dot.style.left = mx + 'px';
            dot.style.top = my + 'px';
        });

        function animateCursor() {
            rx += (mx - rx) * 0.12;
            ry += (my - ry) *.12;
            ring.style.left = rx + 'px';
            ring.style.top = ry + 'px';
            requestAnimationFrame(animateCursor);
        }
        animateCursor();

        document.querySelectorAll('a, button, .feature-card, .dest-card').forEach(el => {
            el.addEventListener('mouseenter', () => {
                ring.style.transform = 'translate(-50%, -50%) scale(1.8)';
                ring.style.opacity = '0.4';
                dot.style.transform = 'translate(-50%, -50%) scale(0.5)';
            });
            el.addEventListener('mouseleave', () => {
                ring.style.transform = 'translate(-50%, -50%) scale(1)';
                ring.style.opacity = '0.6';
                dot.style.transform = 'translate(-50%, -50%) scale(1)';
            });
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const target = document.querySelector(a.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth' });
            });
        });

        // Intersection observer for section animations
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('.feature-card, .dest-card').forEach((el, i) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(32px)';
            el.style.transition = `opacity 0.6s ${i * 0.08}s, transform 0.6s ${i * 0.08}s, background 0.3s`;
            observer.observe(el);
        });
    </script>
</body>
</html>
