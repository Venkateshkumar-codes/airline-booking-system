<?php
ob_start();
session_start();
include 'db.php';

// 1. SECURITY: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

// 2. DATA ACQUISITION
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$just_booked = isset($_GET['booked']) && $_GET['booked'] == '1';

if ($booking_id === 0) {
    die("Invalid Booking ID.");
}

// 3. FETCH DATA
// We fetch the flight details and all passengers belonging to this booking "session"
// Note: We use the flight_id and user_id from the initial booking to find the whole group
$stmt = $conn->prepare("SELECT b.*, f.origin, f.destination, f.departure_date 
                        FROM bookings b 
                        JOIN flights f ON b.flight_id = f.id 
                        WHERE b.id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$first_ticket = $stmt->get_result()->fetch_assoc();

if (!$first_ticket) {
    die("Booking not found.");
}

// Fetch all passengers in this group (booked on the same flight by the same user)
$group_stmt = $conn->prepare("SELECT b.*, f.status as flight_status FROM bookings b JOIN flights f ON b.flight_id = f.id WHERE b.flight_id = ? AND b.user_id = ? ORDER BY b.id ASC");
$group_stmt->bind_param("ii", $first_ticket['flight_id'], $_SESSION['user_id']);
$group_stmt->execute();
$all_passengers = $group_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Boarding Passes | SkyLine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .ticket-cut {
            clip-path: polygon(0% 0%, 100% 0%, 100% 70%, 98% 75%, 100% 80%, 100% 100%, 0% 100%, 0% 80%, 2% 75%, 0% 70%);
        }
        @media print {
            .no-print { display: none; }
            body { background-color: white; padding: 0; }
            .ticket-container { break-after: page; margin-bottom: 0; box-shadow: none; border: 1px solid #e2e8f0; }
        }
    </style>
</head>
<body class="p-6 md:p-10">

    <!-- Header Actions -->
    <div class="max-w-2xl mx-auto mb-10 flex justify-between items-center no-print">
        <a href="dashboard.php" class="text-slate-500 hover:text-blue-600 font-bold transition">← Return Home</a>

    <?php if ($just_booked): ?>
    </div>
    <div class="max-w-2xl mx-auto mb-8 no-print" style="background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.35);border-radius:16px;padding:16px 24px;display:flex;align-items:center;gap:12px;color:#4ade80;font-weight:700;font-size:14px;">
        <span style="font-size:1.4rem">✅</span>
        Payment Successful! Your tickets are confirmed below.
    </div>
    <div class="max-w-2xl mx-auto mb-6 flex justify-end no-print">
    <?php endif; ?>
        <button onclick="window.print()" class="bg-blue-600 text-white px-8 py-3 rounded-2xl font-bold shadow-lg hover:bg-blue-700 transition">
            Print All Tickets
        </button>
    </div>

    <!-- TICKET LOOP -->
    <div class="space-y-10">
        <?php while($data = $all_passengers->fetch_assoc()): ?>
        <div class="max-w-2xl mx-auto bg-white rounded-[2rem] shadow-2xl overflow-hidden ticket-cut ticket-container" style="position:relative">
            
            <!-- Cancelled overlay -->
            <?php if($data['status'] === 'Cancelled'): ?>
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-25deg);font-size:72px;font-weight:900;color:rgba(239,68,68,0.15);z-index:1;pointer-events:none;white-space:nowrap;user-select:none">CANCELLED</div>
            <?php endif; ?>

            <!-- Ticket Header -->
            <div class="bg-blue-600 p-8 text-white flex justify-between items-center" style="<?php echo $data['status']==='Cancelled' ? 'background:#7f1d1d!important' : ''; ?>">
                <div>
                    <h1 class="text-2xl font-black italic tracking-tighter uppercase">SkyLine</h1>
                    <p class="text-blue-100 text-[10px] uppercase font-black tracking-[0.2em] mt-1">Boarding Pass &bull; <?php echo $data['class']; ?></p>
                </div>
                <div class="text-right">
                    <p class="text-xs opacity-80 uppercase font-bold">Gate Pass</p>
                    <p class="font-mono font-bold text-xl">#SK-<?php echo str_pad($data['id'], 5, '0', STR_PAD_LEFT); ?></p>
                </div>
            </div>

            <!-- Passenger Info -->
            <div class="p-10">
                <div class="grid grid-cols-2 gap-10 mb-10">
                    <div>
                        <label class="text-gray-400 text-[10px] uppercase font-black tracking-widest">Passenger Name</label>
                        <p class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($data['passenger_name']); ?></p>
                    </div>
                    <div class="text-right">
                        <label class="text-gray-400 text-[10px] uppercase font-black tracking-widest">Travel Class</label>
                        <p class="text-lg font-semibold text-blue-600"><?php echo $data['class']; ?></p>
                    </div>
                </div>

                <!-- Flight Route -->
                <div class="flex items-center justify-between border-y border-dashed border-gray-200 py-8 mb-8 relative">
                    <div class="text-center">
                        <p class="text-4xl font-black text-slate-900"><?php echo htmlspecialchars($first_ticket['origin']); ?></p>
                        <p class="text-gray-500 text-xs uppercase font-bold mt-1">Departure</p>
                    </div>
                    
                    <div class="flex flex-col items-center flex-1 px-4">
                        <div class="text-blue-500 text-2xl animate-pulse">✈️</div>
                        <div class="w-full border-t-2 border-gray-100 mt-2"></div>
                    </div>

                    <div class="text-center">
                        <p class="text-4xl font-black text-slate-900"><?php echo htmlspecialchars($first_ticket['destination']); ?></p>
                        <p class="text-gray-500 text-xs uppercase font-bold mt-1">Arrival</p>
                    </div>
                </div>

                <!-- Flight Details -->
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="bg-slate-50 p-3 rounded-2xl">
                        <label class="text-gray-400 text-[10px] uppercase font-black block mb-1">Date</label>
                        <p class="font-bold text-sm"><?php echo date('M d, Y', strtotime($first_ticket['departure_date'])); ?></p>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-2xl">
                        <label class="text-gray-400 text-[10px] uppercase font-black block mb-1">Gate</label>
                        <p class="font-bold text-sm text-blue-600">B-<?php echo ($data['id'] % 12) + 1; ?></p>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-2xl">
                        <label class="text-gray-400 text-[10px] uppercase font-black block mb-1">Seat</label>
                        <p class="font-bold text-sm"><?php echo ($data['id'] % 30) + 1 . chr(65 + ($data['id'] % 6)); ?></p>
                    </div>
                </div>
            </div>

            <!-- Footer / QR Section -->
            <?php
                $seat_no_f  = ($data['id'] % 30) + 1;
                $seat_ltr_f = chr(65 + ($data['id'] % 6));
                $gate_no_f  = ($data['id'] % 12) + 1;
                $ref_code_f = '#SK-' . str_pad($data['id'], 5, '0', STR_PAD_LEFT);
                // Plain text QR — Google Lens reads this and shows formatted booking details directly
                $qr_text = "SkyLine Premium Ticket"
                         . "\n"
                         . "\nAirline : LAGH Airlines"
                         . "\nRoute : " . $first_ticket['origin'] . " to " . $first_ticket['destination']
                         . "\nClass : " . $data['class']
                         . "\nDeparture : " . date('d M Y', strtotime($first_ticket['departure_date']))
                         . "\nPayment : UPI"
                         . "\nTotal Amount : Rs." . number_format($first_ticket['price'] ?? 0, 0)
                         . "\n"
                         . "\nPassenger:"
                         . "\n"
                         . "\nName : " . $data['passenger_name']
                         . "\nSeat : " . $seat_no_f . $seat_ltr_f
                         . "\nGate : B-" . $gate_no_f
                         . "\nClass : " . $data['class']
                         . "\nType : " . $data['passenger_type']
                         . "\nBooking : " . $ref_code_f
                         . "\nStatus : " . $data['status'];
                $bstatus = $data['status'];
                $fstatus = $data['flight_status'];
            ?>
            <div class="bg-slate-900 p-6 text-white" style="border-top:2px dashed rgba(255,255,255,0.08)">
                <div class="flex justify-between items-center gap-6">
                    <!-- QR Code — big and scannable -->
                    <div class="flex flex-col items-center gap-2">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=10&color=000000&bgcolor=ffffff&data=<?php echo urlencode($qr_text); ?>"
                             alt="Scan with Google Lens" style="width:110px;height:110px;background:white;padding:5px;border-radius:10px;display:block">
                        <p style="font-size:9px;color:rgba(255,255,255,0.4);letter-spacing:0.12em;text-transform:uppercase;text-align:center">Scan with Google Lens</p>
                    </div>
                    <!-- Middle info -->
                    <div class="flex-1">
                        <p class="text-[10px] text-blue-400 font-black uppercase tracking-widest mb-1">✈ Digital Boarding Pass</p>
                        <p class="text-xs text-slate-400 mb-3">Valid for Gate Entry &amp; Security Check</p>
                        <p class="font-mono text-sm font-bold text-white"><?php echo htmlspecialchars($ref_code_f); ?></p>
                        <p class="text-[10px] text-slate-500 mt-1"><?php echo htmlspecialchars($first_ticket['origin']); ?> → <?php echo htmlspecialchars($first_ticket['destination']); ?></p>
                    </div>
                    <!-- Status badge -->
                    <div class="text-right">
                        <p class="text-[10px] text-slate-500 font-bold uppercase mb-2">Booking Status</p>
                        <?php
                        if ($bstatus === 'Cancelled') {
                            echo '<span class="bg-red-500/20 text-red-400 text-[10px] font-black px-3 py-1.5 rounded-full border border-red-500/30">✕ CANCELLED</span>';
                        } elseif ($fstatus === 'Delayed') {
                            echo '<span class="bg-orange-500/20 text-orange-400 text-[10px] font-black px-3 py-1.5 rounded-full border border-orange-500/30">⏱ DELAYED</span>';
                        } elseif ($fstatus === 'Cancelled') {
                            echo '<span class="bg-red-500/20 text-red-400 text-[10px] font-black px-3 py-1.5 rounded-full border border-red-500/30">✕ FLT CANCELLED</span>';
                        } else {
                            echo '<span class="bg-green-500/20 text-green-400 text-[10px] font-black px-3 py-1.5 rounded-full border border-green-500/30">✓ CONFIRMED</span>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <footer class="text-center mt-20 text-gray-400 text-[10px] uppercase tracking-[0.4em] no-print pb-10">
        &copy; 2026 SkyLine Airlines &bull; Cloud Management System
    </footer>

</body>
</html>