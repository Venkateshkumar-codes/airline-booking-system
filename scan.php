<?php
/**
 * scan.php  — Public ticket verification page
 * Accessed via QR code scan (Google Lens, camera app, etc.)
 * URL: scan.php?ref=SK-00004&key=HMAC_HASH
 *
 * No login required — authenticated via HMAC signature baked into the QR URL.
 */

include 'db.php';

// ── Secret key (change this to any random string in production) ──
define('QR_SECRET', 'SkyLine_LAGH_2026_QRsecret!');

$ref     = isset($_GET['ref'])  ? trim($_GET['ref'])  : '';
$key     = isset($_GET['key'])  ? trim($_GET['key'])  : '';
$booking_id = intval(ltrim($ref, 'SK-'));

// Verify HMAC so the URL can't be tampered with
$expected = substr(hash_hmac('sha256', $ref, QR_SECRET), 0, 16);

if (empty($ref) || empty($key) || $key !== $expected || $booking_id <= 0) {
    http_response_code(400);
    $error = "Invalid or expired QR code.";
}

if (!isset($error)) {
    $stmt = $conn->prepare(
        "SELECT b.*, f.origin, f.destination, f.departure_date, f.price, f.status as flight_status
         FROM bookings b
         JOIN flights f ON b.flight_id = f.id
         WHERE b.id = ?"
    );
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$data) {
        $error = "Booking not found.";
    }
}

if (!isset($error)) {
    $seat_no  = ($data['id'] % 30) + 1;
    $seat_ltr = chr(65 + ($data['id'] % 6));
    $gate_no  = ($data['id'] % 12) + 1;
    $booking_ref = '#SK-' . str_pad($data['id'], 5, '0', STR_PAD_LEFT);

    $is_cancelled = $data['status'] === 'Cancelled' || $data['flight_status'] === 'Cancelled';
    $is_delayed   = $data['flight_status'] === 'Delayed';

    $status_color = $is_cancelled ? '#ef4444' : ($is_delayed ? '#f97316' : '#22c55e');
    $status_text  = $is_cancelled ? 'CANCELLED' : ($is_delayed ? 'DELAYED' : 'CONFIRMED');
    $status_bg    = $is_cancelled ? 'rgba(239,68,68,0.12)' : ($is_delayed ? 'rgba(249,115,22,0.12)' : 'rgba(34,197,94,0.12)');
    $status_border= $is_cancelled ? 'rgba(239,68,68,0.3)' : ($is_delayed ? 'rgba(249,115,22,0.3)' : 'rgba(34,197,94,0.3)');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <title>SkyLine — Boarding Pass</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --gold: #C9A84C;
            --gold-light: #E8C97A;
            --navy: #090D1A;
            --navy-mid: #111827;
            --cream: #F5F0E8;
            --muted: rgba(245,240,232,0.5);
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--navy);
            color: var(--cream);
            min-height: 100vh;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ── TOP BAR ── */
        .topbar {
            width: 100%;
            background: rgba(9,13,26,0.98);
            border-bottom: 1px solid rgba(201,168,76,0.15);
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .topbar-logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--cream);
        }
        .topbar-logo span { color: var(--gold); }
        .topbar-icon {
            width: 32px; height: 32px;
            background: var(--gold);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem;
        }

        /* ── MAIN CARD ── */
        .page-wrap {
            width: 100%;
            max-width: 460px;
            padding: 24px 16px 40px;
        }

        /* ERROR STATE */
        .error-card {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 20px;
            padding: 40px 28px;
            text-align: center;
            margin-top: 32px;
        }
        .error-icon { font-size: 3rem; margin-bottom: 16px; }
        .error-title { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 700; color: #fca5a5; margin-bottom: 10px; }
        .error-msg { font-size: 0.85rem; color: var(--muted); line-height: 1.6; }

        /* TICKET CARD */
        .ticket {
            background: #0c1120;
            border: 1px solid rgba(201,168,76,0.18);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0,0,0,0.6);
            margin-top: 16px;
            position: relative;
        }

        /* Cancelled watermark */
        .cancelled-mark {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            font-weight: 900;
            color: rgba(239,68,68,0.1);
            transform: rotate(-25deg);
            pointer-events: none;
            z-index: 1;
            user-select: none;
            letter-spacing: 0.05em;
        }

        /* Header strip */
        .ticket-header {
            background: linear-gradient(135deg, #0d1f3a, #0a1628);
            border-bottom: 1px solid rgba(201,168,76,0.15);
            padding: 22px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ticket-header-left { }
        .th-label {
            font-size: 0.6rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 600;
            margin-bottom: 3px;
        }
        .th-class {
            font-size: 0.78rem;
            color: var(--muted);
            letter-spacing: 0.1em;
        }
        .ticket-ref {
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--gold-light);
            letter-spacing: 0.05em;
            background: rgba(201,168,76,0.1);
            border: 1px solid rgba(201,168,76,0.2);
            padding: 8px 14px;
            border-radius: 10px;
        }

        /* Status badge */
        .status-bar {
            margin: 0 20px;
            margin-top: -1px;
            padding: 12px 18px;
            border-radius: 0 0 14px 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
        }

        /* Passenger block */
        .ticket-body { padding: 24px; position: relative; z-index: 2; }

        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        .info-block { }
        .info-label {
            font-size: 0.58rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 600;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--cream);
        }
        .info-value.gold { color: var(--gold-light); }

        /* Route */
        .route-strip {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(201,168,76,0.1);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
        }
        .route-city {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 900;
            line-height: 1;
            text-align: center;
        }
        .route-sub {
            font-size: 0.6rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            text-align: center;
            margin-top: 4px;
        }
        .route-mid {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .route-plane { font-size: 1.3rem; }
        .route-line {
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(201,168,76,0.4), transparent);
        }

        /* Details grid */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .detail-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(201,168,76,0.08);
            border-radius: 12px;
            padding: 12px 10px;
            text-align: center;
        }

        /* Divider with notches */
        .ticket-divider {
            display: flex;
            align-items: center;
            gap: 0;
            margin: 0 -1px;
            position: relative;
        }
        .notch {
            width: 22px; height: 22px;
            background: var(--navy);
            border-radius: 50%;
            flex-shrink: 0;
            border: 1px solid rgba(201,168,76,0.18);
        }
        .divider-line {
            flex: 1;
            border-top: 1.5px dashed rgba(201,168,76,0.15);
        }

        /* Verify footer */
        .ticket-footer {
            padding: 22px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .verify-badge {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .verify-icon {
            width: 42px; height: 42px;
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.25);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }
        .verify-text-top {
            font-size: 0.65rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #86efac;
            font-weight: 700;
        }
        .verify-text-bot {
            font-size: 0.7rem;
            color: var(--muted);
            margin-top: 2px;
        }
        .price-block { text-align: right; }
        .price-label {
            font-size: 0.58rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 3px;
        }
        .price-value {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gold-light);
        }

        /* Scan note */
        .scan-note {
            text-align: center;
            margin-top: 20px;
            font-size: 0.72rem;
            color: var(--muted);
            line-height: 1.6;
        }
        .scan-note strong { color: var(--gold); }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-icon">✈</div>
    <div class="topbar-logo">Sky<span>Line</span> · LAGH Airlines</div>
</div>

<div class="page-wrap">

<?php if (isset($error)): ?>
    <div class="error-card">
        <div class="error-icon">🚫</div>
        <div class="error-title">Verification Failed</div>
        <div class="error-msg"><?= htmlspecialchars($error) ?><br><br>This QR code may be invalid, expired, or has been tampered with.</div>
    </div>

<?php else: ?>

    <div class="ticket">

        <?php if ($is_cancelled): ?>
        <div class="cancelled-mark">CANCELLED</div>
        <?php endif; ?>

        <!-- Header -->
        <div class="ticket-header">
            <div class="ticket-header-left">
                <div class="th-label">✈ SkyLine Airlines</div>
                <div class="th-class">Boarding Pass · <?= htmlspecialchars($data['class']) ?></div>
            </div>
            <div class="ticket-ref"><?= htmlspecialchars($booking_ref) ?></div>
        </div>

        <!-- Status badge -->
        <div class="status-bar" style="background:<?= $status_bg ?>;border:1px solid <?= $status_border ?>;color:<?= $status_color ?>">
            <?= $is_cancelled ? '✕' : ($is_delayed ? '⏱' : '✓') ?>
            &nbsp;<?= $status_text ?>
            <?php if($is_delayed): ?>&nbsp;— Please check departure boards<?php endif; ?>
        </div>

        <div class="ticket-body">

            <!-- Passenger & class -->
            <div class="info-row">
                <div class="info-block">
                    <div class="info-label">Passenger Name</div>
                    <div class="info-value"><?= htmlspecialchars($data['passenger_name']) ?></div>
                </div>
                <div class="info-block" style="text-align:right">
                    <div class="info-label">Travel Class</div>
                    <div class="info-value gold"><?= htmlspecialchars($data['class']) ?></div>
                </div>
            </div>

            <!-- Route -->
            <div class="route-strip">
                <div>
                    <div class="route-city"><?= htmlspecialchars(strtoupper(substr($data['origin'],0,3))) ?></div>
                    <div class="route-sub"><?= htmlspecialchars($data['origin']) ?></div>
                </div>
                <div class="route-mid">
                    <div class="route-plane">✈️</div>
                    <div class="route-line"></div>
                </div>
                <div>
                    <div class="route-city"><?= htmlspecialchars(strtoupper(substr($data['destination'],0,3))) ?></div>
                    <div class="route-sub"><?= htmlspecialchars($data['destination']) ?></div>
                </div>
            </div>

            <!-- Flight details -->
            <div class="details-grid">
                <div class="detail-box">
                    <div class="info-label">Date</div>
                    <div class="info-value" style="font-size:0.82rem"><?= date('d M Y', strtotime($data['departure_date'])) ?></div>
                </div>
                <div class="detail-box">
                    <div class="info-label">Seat</div>
                    <div class="info-value gold"><?= $seat_no . $seat_ltr ?></div>
                </div>
                <div class="detail-box">
                    <div class="info-label">Gate</div>
                    <div class="info-value gold">B-<?= $gate_no ?></div>
                </div>
                <div class="detail-box">
                    <div class="info-label">Passenger Type</div>
                    <div class="info-value" style="font-size:0.82rem"><?= htmlspecialchars($data['passenger_type']) ?></div>
                </div>
                <div class="detail-box">
                    <div class="info-label">Booked On</div>
                    <div class="info-value" style="font-size:0.78rem"><?= date('d M Y', strtotime($data['booking_date'])) ?></div>
                </div>
                <div class="detail-box">
                    <div class="info-label">Payment</div>
                    <div class="info-value" style="font-size:0.82rem;color:#86efac">UPI ✓</div>
                </div>
            </div>

        </div>

        <!-- Notched divider -->
        <div class="ticket-divider">
            <div class="notch"></div>
            <div class="divider-line"></div>
            <div class="notch"></div>
        </div>

        <!-- Footer verify strip -->
        <div class="ticket-footer">
            <div class="verify-badge">
                <div class="verify-icon">✅</div>
                <div>
                    <div class="verify-text-top">QR Verified</div>
                    <div class="verify-text-bot">Valid for Gate Entry & Security</div>
                </div>
            </div>
            <div class="price-block">
                <div class="price-label">Amount Paid</div>
                <div class="price-value">₹<?= number_format($data['price'] ?? 0, 0) ?></div>
            </div>
        </div>

    </div>

    <div class="scan-note">
        Scanned via <strong>SkyLine QR</strong> · Booking <?= htmlspecialchars($booking_ref) ?><br>
        This page confirms the authenticity of your boarding pass.
    </div>

<?php endif; ?>
</div>

</body>
</html>
