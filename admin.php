<?php
ob_start();
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$message = "";

// Handle Delete Flight
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $del_b = $conn->prepare("DELETE FROM bookings WHERE flight_id = ?");
    $del_b->bind_param("i", $id);
    $del_b->execute();
    $del_b->close();
    $del_f = $conn->prepare("DELETE FROM flights WHERE id = ?");
    $del_f->bind_param("i", $id);
    $del_f->execute();
    $del_f->close();
    header("Location: admin.php?deleted=1");
    exit();
}

// Handle Ticket Cancellation
if (isset($_GET['cancel_ticket'])) {
    $t_id = intval($_GET['cancel_ticket']);
    $bk_stmt = $conn->prepare("SELECT flight_id, status FROM bookings WHERE id = ?");
    $bk_stmt->bind_param("i", $t_id);
    $bk_stmt->execute();
    $bk = $bk_stmt->get_result()->fetch_assoc();
    $bk_stmt->close();
    if ($bk && $bk['status'] === 'Booked') {
        $upd_b = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
        $upd_b->bind_param("i", $t_id);
        $upd_b->execute();
        $upd_b->close();
        $fid = intval($bk['flight_id']);
        $upd_f = $conn->prepare("UPDATE flights SET available_seats = available_seats + 1 WHERE id = ?");
        $upd_f->bind_param("i", $fid);
        $upd_f->execute();
        $upd_f->close();
    }
    header("Location: admin.php?ticket_msg=1");
    exit();
}

// Handle Flight Status Update
if (isset($_POST['update_status'])) {
    $f_id = intval($_POST['flight_id']);
    $stat = $_POST['new_status'];
    $allowed = ['Scheduled', 'Delayed', 'Cancelled'];
    if (in_array($stat, $allowed)) {
        $stmt = $conn->prepare("UPDATE flights SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $stat, $f_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin.php?status_msg=1");
    exit();
}

// Handle Add Flight
if (isset($_POST['add_flight'])) {
    $origin = trim($_POST['origin']);
    $dest   = trim($_POST['destination']);
    $date   = $_POST['departure_date'];
    $price  = floatval($_POST['price']);
    $seats  = intval($_POST['seats']);

    $stmt = $conn->prepare("INSERT INTO flights (origin, destination, departure_date, price, available_seats, status) VALUES (?, ?, ?, ?, ?, 'Scheduled')");
    $stmt->bind_param("sssdi", $origin, $dest, $date, $price, $seats);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: admin.php?success=1");
        exit();
    }
}

$toast = "";
if (isset($_GET['success']))    $toast = "success|Flight added successfully!";
if (isset($_GET['deleted']))    $toast = "warning|Flight and its bookings have been removed.";
if (isset($_GET['ticket_msg'])) $toast = "info|Booking status updated to Cancelled.";
if (isset($_GET['status_msg'])) $toast = "info|Flight status has been updated.";

// Create messages table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS admin_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread','read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Mark message as read
if (isset($_GET['mark_read'])) {
    $mid = intval($_GET['mark_read']);
    $mr = $conn->prepare("UPDATE admin_messages SET status='read' WHERE id=?"); $mr->bind_param("i", $mid); $mr->execute(); $mr->close();
    header("Location: admin.php?section=messages");
    exit();
}
// Delete message
if (isset($_GET['del_msg'])) {
    $mid = intval($_GET['del_msg']);
    $dm = $conn->prepare("DELETE FROM admin_messages WHERE id=?"); $dm->bind_param("i", $mid); $dm->execute(); $dm->close();
    header("Location: admin.php?section=messages");
    exit();
}

$all_flights  = $conn->query("SELECT * FROM flights ORDER BY id DESC");
$all_bookings = $conn->query("SELECT b.*, f.origin, f.destination FROM bookings b JOIN flights f ON b.flight_id = f.id ORDER BY b.id DESC");
$all_users    = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

$total_flights   = $conn->query("SELECT COUNT(*) as c FROM flights")->fetch_assoc()['c'];
$total_bookings  = $conn->query("SELECT COUNT(*) as c FROM bookings")->fetch_assoc()['c'];
$active_bookings = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='Booked'")->fetch_assoc()['c'];
$total_users     = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$all_messages    = $conn->query("SELECT * FROM admin_messages ORDER BY created_at DESC");
$unread_count    = $conn->query("SELECT COUNT(*) as c FROM admin_messages WHERE status='unread'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | SkyLine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; }
        :root {
            --sidebar-bg: #0b0f1a;
            --main-bg: #0f1526;
            --card-bg: #151c2e;
            --card-border: rgba(255,255,255,0.07);
            --text-primary: #f1f5f9;
            --text-secondary: #64748b;
            --accent: #3b82f6;
        }
        body { background: var(--main-bg); color: var(--text-primary); margin: 0; }
        aside {
            position: fixed; left: 0; top: 0; bottom: 0; width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(255,255,255,0.05);
            display: flex; flex-direction: column; padding: 28px 20px;
            z-index: 100;
        }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 16px; border-radius: 12px;
            font-size: 14px; font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none; transition: all 0.2s;
            cursor: pointer;
        }
        .nav-link:hover { background: rgba(255,255,255,0.05); color: var(--text-primary); }
        .nav-link.active { background: rgba(59,130,246,0.15); color: #60a5fa; }
        .nav-link.danger { color: #f87171; }
        .nav-link.danger:hover { background: rgba(239,68,68,0.1); }
        main { margin-left: 260px; padding: 36px 40px; min-height: 100vh; }
        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
        }
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 22px 24px;
        }
        .section { display: none; }
        .section.active { display: block; }
        .input-dark {
            background: rgba(255,255,255,0.04);
            border: 1.5px solid rgba(255,255,255,0.08);
            color: white; border-radius: 12px;
            padding: 11px 14px; width: 100%; outline: none;
            font-size: 14px; transition: border 0.2s;
        }
        .input-dark:focus { border-color: #3b82f6; background: rgba(59,130,246,0.07); }
        .input-dark::placeholder { color: rgba(255,255,255,0.2); }
        .btn-blue {
            background: linear-gradient(135deg,#2563eb,#1d4ed8);
            color: white; font-weight: 700; border-radius: 12px;
            padding: 12px 20px; border: none; cursor: pointer;
            width: 100%; font-size: 14px; transition: all 0.2s;
        }
        .btn-blue:hover { background: linear-gradient(135deg,#3b82f6,#2563eb); transform: translateY(-1px); }
        table { width: 100%; border-collapse: collapse; }
        thead tr { border-bottom: 1px solid rgba(255,255,255,0.06); }
        tbody tr { border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.025); }
        th { padding: 14px 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #475569; text-align: left; }
        td { padding: 16px 20px; font-size: 14px; vertical-align: middle; }
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .badge-green { background: rgba(34,197,94,0.12); color: #4ade80; }
        .badge-red { background: rgba(239,68,68,0.12); color: #f87171; }
        .badge-yellow { background: rgba(234,179,8,0.12); color: #facc15; }
        .badge-blue { background: rgba(59,130,246,0.12); color: #60a5fa; }
        .badge-gray { background: rgba(100,116,139,0.15); color: #94a3b8; }
        select.input-dark { cursor: pointer; }
        input[type="date"].input-dark::-webkit-calendar-picker-indicator { filter: invert(0.5); cursor: pointer; }
        #toast-container {
            position: fixed; bottom: 28px; right: 28px; z-index: 9999;
            display: flex; flex-direction: column; gap: 10px;
        }
        .toast {
            padding: 14px 20px; border-radius: 14px;
            font-size: 14px; font-weight: 600;
            display: flex; align-items: center; gap: 10px;
            animation: slideIn 0.4s ease; min-width: 280px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        @keyframes slideIn { from { opacity:0; transform: translateX(40px); } to { opacity:1; transform: translateX(0); } }
        .toast-success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.25); color: #4ade80; }
        .toast-warning { background: rgba(234,179,8,0.12); border: 1px solid rgba(234,179,8,0.2); color: #facc15; }
        .toast-info { background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.2); color: #60a5fa; }
        .section-title { font-size: 22px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; }
        .section-sub { font-size: 14px; color: var(--text-secondary); margin-bottom: 28px; }
    </style>
</head>
<body>

<aside>
    <div>
        <!-- Logo -->
        <div style="display:flex;align-items:center;gap:12px;padding:0 8px;margin-bottom:36px">
            <div style="width:38px;height:38px;background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">✈️</div>
            <div>
                <div style="font-weight:800;font-size:16px;color:#f1f5f9">SkyLine</div>
                <div style="font-size:10px;color:#475569;font-weight:600;text-transform:uppercase;letter-spacing:0.08em">Admin Panel</div>
            </div>
        </div>

        <!-- Nav -->
        <div style="font-size:10px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.12em;padding:0 16px;margin-bottom:10px">Navigation</div>
        <nav style="display:flex;flex-direction:column;gap:4px">
            <a class="nav-link active" onclick="showSection('dashboard', this)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Dashboard
            </a>
            <a class="nav-link" onclick="showSection('flights', this)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Flights
            </a>
            <a class="nav-link" onclick="showSection('bookings', this)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Bookings
            </a>
            <a class="nav-link" onclick="showSection('users', this)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                Users
            </a>
            <a class="nav-link" onclick="showSection('add-flight', this)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add Flight
            </a>
            <a class="nav-link" onclick="showSection('messages', this)" style="position:relative">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Messages
                <?php if($unread_count > 0): ?>
                <span style="background:#ef4444;color:white;font-size:10px;font-weight:700;padding:1px 6px;border-radius:999px;margin-left:auto"><?=$unread_count?></span>
                <?php endif; ?>
            </a>
        </nav>
    </div>

    <div style="border-top:1px solid rgba(255,255,255,0.05);padding-top:20px;margin-top:20px">
        <div style="padding:10px 16px;margin-bottom:8px;font-size:12px;color:#475569">
            Signed in as <span style="color:#94a3b8;font-weight:600"><?php echo htmlspecialchars($_SESSION['admin_user']); ?></span>
        </div>
        <a href="index.php" class="nav-link" style="color:#94a3b8;margin-bottom:4px">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Return to Home
        </a>
        <a href="logout.php" class="nav-link danger">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Sign Out
        </a>
    </div>
</aside>

<main>

    <!-- Toast -->
    <div id="toast-container"></div>

    <!-- DASHBOARD OVERVIEW -->
    <div id="sec-dashboard" class="section active">
        <div class="section-title">Dashboard Overview</div>
        <div class="section-sub">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_user']); ?>. Here's what's happening today.</div>

        <!-- Stat Cards -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:32px">
            <div class="stat-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <div style="font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px">Total Flights</div>
                        <div style="font-size:36px;font-weight:800;color:#f1f5f9;line-height:1"><?php echo $total_flights; ?></div>
                    </div>
                    <div style="width:44px;height:44px;background:rgba(59,130,246,0.12);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px">✈</div>
                </div>
            </div>
            <div class="stat-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <div style="font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px">Total Bookings</div>
                        <div style="font-size:36px;font-weight:800;color:#f1f5f9;line-height:1"><?php echo $total_bookings; ?></div>
                    </div>
                    <div style="width:44px;height:44px;background:rgba(34,197,94,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px">📋</div>
                </div>
            </div>
            <div class="stat-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <div style="font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px">Active Bookings</div>
                        <div style="font-size:36px;font-weight:800;color:#4ade80;line-height:1"><?php echo $active_bookings; ?></div>
                    </div>
                    <div style="width:44px;height:44px;background:rgba(34,197,94,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px">✅</div>
                </div>
            </div>
            <div class="stat-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <div style="font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px">Registered Users</div>
                        <div style="font-size:36px;font-weight:800;color:#f1f5f9;line-height:1"><?php echo $total_users; ?></div>
                    </div>
                    <div style="width:44px;height:44px;background:rgba(168,85,247,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px">👥</div>
                </div>
            </div>
        </div>

        <!-- Quick overview table -->
        <div class="card">
            <div style="padding:22px 24px;border-bottom:1px solid rgba(255,255,255,0.05);display:flex;justify-content:space-between;align-items:center">
                <div style="font-size:16px;font-weight:700">Recent Flights</div>
                <button onclick="showSection('flights', document.querySelector('[onclick*=\"flights\"]'))" style="font-size:13px;color:#3b82f6;font-weight:600;background:none;border:none;cursor:pointer">View all →</button>
            </div>
            <?php
            $flights_preview = $conn->query("SELECT * FROM flights ORDER BY id DESC LIMIT 5");
            ?>
            <table>
                <thead><tr><th>Route</th><th>Date</th><th>Price</th><th>Seats</th><th>Status</th></tr></thead>
                <tbody>
                <?php while($r = $flights_preview->fetch_assoc()): ?>
                <tr>
                    <td><span style="font-weight:600"><?=htmlspecialchars($r['origin'])?> → <?=htmlspecialchars($r['destination'])?></span></td>
                    <td style="color:#94a3b8"><?=htmlspecialchars($r['departure_date'])?></td>
                    <td style="font-weight:600;color:#4ade80">₹<?=number_format($r['price'],2)?></td>
                    <td><?=$r['available_seats']?></td>
                    <td>
                        <?php
                        $sc = $r['status']=='Scheduled' ? 'badge-green' : ($r['status']=='Delayed' ? 'badge-yellow' : 'badge-red');
                        echo "<span class='badge $sc'>".$r['status']."</span>";
                        ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FLIGHTS SECTION -->
    <div id="sec-flights" class="section">
        <div class="section-title">Flight Schedule</div>
        <div class="section-sub">Manage all flights — update status or remove entries.</div>
        <div class="card" style="overflow:hidden">
            <table>
                <thead><tr><th>#</th><th>Route</th><th>Date</th><th>Price</th><th>Seats</th><th>Status</th><th style="text-align:right">Action</th></tr></thead>
                <tbody>
                <?php
                $all_flights->data_seek(0);
                while($row = $all_flights->fetch_assoc()):
                ?>
                <tr>
                    <td style="color:#475569;font-weight:600">#<?=$row['id']?></td>
                    <td><span style="font-weight:700"><?=htmlspecialchars($row['origin'])?> → <?=htmlspecialchars($row['destination'])?></span></td>
                    <td style="color:#94a3b8"><?=htmlspecialchars($row['departure_date'])?></td>
                    <td style="font-weight:600;color:#4ade80">₹<?=number_format($row['price'],2)?></td>
                    <td><?=$row['available_seats']?></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="flight_id" value="<?=$row['id']?>">
                            <input type="hidden" name="update_status" value="1">
                            <select name="new_status" onchange="this.form.submit()" class="input-dark" style="padding:6px 12px;font-size:12px;font-weight:700;width:auto;border-radius:8px">
                                <option <?=($row['status']=='Scheduled'?'selected':'')?>>Scheduled</option>
                                <option <?=($row['status']=='Delayed'?'selected':'')?>>Delayed</option>
                                <option <?=($row['status']=='Cancelled'?'selected':'')?>>Cancelled</option>
                            </select>
                        </form>
                    </td>
                    <td style="text-align:right">
                        <a href="admin.php?delete=<?=$row['id']?>" onclick="return confirm('Remove flight #<?=$row['id']?> and all its bookings?')"
                           style="color:#f87171;font-size:13px;font-weight:700;text-decoration:none;padding:6px 14px;border:1px solid rgba(239,68,68,0.2);border-radius:8px;background:rgba(239,68,68,0.08)">
                            Remove
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- BOOKINGS SECTION -->
    <div id="sec-bookings" class="section">
        <div class="section-title">Passenger Bookings</div>
        <div class="section-sub">All booking records with cancellation controls.</div>
        <div class="card" style="overflow:hidden">
            <table>
                <thead><tr><th>#</th><th>Passenger</th><th>Route</th><th>Class</th><th>Contact</th><th>Date</th><th>Status</th><th style="text-align:right">Action</th></tr></thead>
                <tbody>
                <?php
                $all_bookings->data_seek(0);
                while($b = $all_bookings->fetch_assoc()):
                ?>
                <tr>
                    <td style="color:#475569;font-weight:600">#<?=$b['id']?></td>
                    <td>
                        <div style="font-weight:700"><?=htmlspecialchars($b['passenger_name'])?></div>
                        <div style="font-size:12px;color:#475569"><?=htmlspecialchars($b['email']??'N/A')?></div>
                    </td>
                    <td style="font-size:13px"><?=htmlspecialchars($b['origin'])?> → <?=htmlspecialchars($b['destination'])?></td>
                    <td><span class="badge badge-blue"><?=$b['class']??'Economy'?></span></td>
                    <td style="font-size:13px;color:#94a3b8"><?=htmlspecialchars($b['contact']??'N/A')?></td>
                    <td style="font-size:12px;color:#475569"><?=date('M d, Y',strtotime($b['booking_date']))?></td>
                    <td>
                        <?php if($b['status']=='Booked'): ?>
                            <span class="badge badge-green">● Booked</span>
                        <?php else: ?>
                            <span class="badge badge-red">● Cancelled</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right">
                        <?php if($b['status']=='Booked'): ?>
                        <a href="admin.php?cancel_ticket=<?=$b['id']?>" onclick="return confirm('Cancel this booking?')"
                           style="color:#f87171;font-size:13px;font-weight:700;text-decoration:none;padding:6px 14px;border:1px solid rgba(239,68,68,0.2);border-radius:8px;background:rgba(239,68,68,0.08)">
                            Cancel
                        </a>
                        <?php else: ?>
                            <span style="color:#334155;font-size:12px">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- USERS SECTION -->
    <div id="sec-users" class="section">
        <div class="section-title">Registered Users</div>
        <div class="section-sub">All users who have created accounts on SkyLine.</div>
        <div class="card" style="overflow:hidden">
            <table>
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Contact</th><th>Date of Birth</th><th>Joined</th></tr></thead>
                <tbody>
                <?php
                if ($all_users && $all_users->num_rows > 0):
                    while($u = $all_users->fetch_assoc()):
                ?>
                <tr>
                    <td style="color:#475569;font-weight:600">#<?=$u['id']?></td>
                    <td><span style="font-weight:700"><?=htmlspecialchars($u['name'])?></span></td>
                    <td style="color:#94a3b8;font-size:13px"><?=htmlspecialchars($u['email'])?></td>
                    <td style="font-size:13px;color:#94a3b8"><?=htmlspecialchars($u['contact']??'N/A')?></td>
                    <td style="font-size:13px;color:#94a3b8"><?=($u['dob'] ? date('M d, Y',strtotime($u['dob'])) : 'N/A')?></td>
                    <td style="font-size:12px;color:#475569"><?=date('M d, Y',strtotime($u['created_at']))?></td>
                </tr>
                <?php
                    endwhile;
                else:
                ?>
                <tr><td colspan="6" style="text-align:center;padding:40px;color:#475569">No users registered yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ADD FLIGHT SECTION -->
    <div id="sec-add-flight" class="section">
        <div class="section-title">Register New Flight</div>
        <div class="section-sub">Add a new flight to the schedule.</div>
        <div class="card" style="max-width:520px;padding:32px">
            <form method="POST" style="display:flex;flex-direction:column;gap:18px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:8px">Origin</label>
                        <input type="text" name="origin" required placeholder="e.g. Mumbai" class="input-dark">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:8px">Destination</label>
                        <input type="text" name="destination" required placeholder="e.g. Delhi" class="input-dark">
                    </div>
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:8px">Departure Date</label>
                    <input type="date" name="departure_date" required class="input-dark">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:8px">Price (₹)</label>
                        <input type="number" step="0.01" name="price" required placeholder="2500.00" class="input-dark">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:8px">Available Seats</label>
                        <input type="number" name="seats" required placeholder="180" class="input-dark">
                    </div>
                </div>
                <button type="submit" name="add_flight" class="btn-blue" style="margin-top:6px">
                    + Register Flight
                </button>
            </form>
        </div>
    </div>

    <!-- MESSAGES SECTION -->
    <div id="sec-messages" class="section">
        <div class="section-title">User Messages</div>
        <div class="section-sub">Messages sent by users via the Contact Admin button.</div>
        <?php
        $all_messages->data_seek(0);
        if ($all_messages->num_rows === 0): ?>
            <div style="text-align:center;padding:60px 20px;color:#475569">
                <div style="font-size:3rem;margin-bottom:16px">📭</div>
                <p style="font-size:16px;font-weight:600">No messages yet</p>
                <p style="font-size:13px;margin-top:4px">Messages from users will appear here.</p>
            </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:14px">
        <?php while($msg = $all_messages->fetch_assoc()): ?>
            <div class="card" style="padding:20px 24px;border-left:3px solid <?= $msg['status']==='unread' ? '#3b82f6' : 'rgba(255,255,255,0.07)' ?>">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
                    <div style="flex:1">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                            <?php if($msg['status']==='unread'): ?>
                            <span style="background:rgba(59,130,246,0.15);color:#60a5fa;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;text-transform:uppercase">New</span>
                            <?php endif; ?>
                            <span style="color:white;font-size:15px;font-weight:700"><?= htmlspecialchars($msg['subject']) ?></span>
                        </div>
                        <p style="color:#94a3b8;font-size:13px;margin:0 0 10px 0;line-height:1.6"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                        <div style="display:flex;gap:16px;font-size:12px;color:#475569">
                            <span>👤 <?= htmlspecialchars($msg['user_name']) ?></span>
                            <span>✉️ <?= htmlspecialchars($msg['user_email']) ?></span>
                            <span>🕐 <?= date('d M Y, h:i A', strtotime($msg['created_at'])) ?></span>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;flex-shrink:0">
                        <?php if($msg['status']==='unread'): ?>
                        <a href="admin.php?mark_read=<?=$msg['id']?>" style="background:rgba(34,197,94,0.1);color:#86efac;border:1px solid rgba(34,197,94,0.2);padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none">✓ Mark Read</a>
                        <?php endif; ?>
                        <a href="admin.php?del_msg=<?=$msg['id']?>" onclick="return confirm('Delete this message?')" style="background:rgba(239,68,68,0.1);color:#f87171;border:1px solid rgba(239,68,68,0.2);padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none">🗑 Delete</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

</main>

<script>
    // Auto-open section from URL param
    const urlSection = new URLSearchParams(window.location.search).get('section');
    if (urlSection) showSection(urlSection, document.querySelector(`[onclick*="${urlSection}"]`));

    function showSection(name, el) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
        document.getElementById('sec-' + name).classList.add('active');
        if (el) el.classList.add('active');
    }

    // Toast notifications
    <?php if($toast): 
        [$type, $msg] = explode('|', $toast, 2);
    ?>
    (function() {
        const container = document.getElementById('toast-container');
        const t = document.createElement('div');
        t.className = 'toast toast-<?=$type?>';
        t.innerHTML = `<svg style="width:18px;height:18px;flex-shrink:0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><?=addslashes($msg)?>`;
        container.appendChild(t);
        setTimeout(() => t.remove(), 4000);
    })();
    <?php endif; ?>
</script>
</body>
</html>
