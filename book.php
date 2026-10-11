<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';

// 1. SECURITY: Force Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php?error=login_required");
    exit();
}

// 2. DATA ACQUISITION & VALIDATION
$flight_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$num_passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1; 

// Clamp passenger count between 1 and 10 for UI stability
$num_passengers = max(1, min(10, $num_passengers));

// Fetch flight details using Prepared Statement
$stmt = $conn->prepare("SELECT * FROM flights WHERE id = ?");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result_flight = $stmt->get_result();
$flight = $result_flight->fetch_assoc();

// Validate flight existence, seat availability, status, and date
if (!$flight || $flight['available_seats'] < $num_passengers || $flight['status'] == 'Cancelled' || strtotime($flight['departure_date']) < strtotime(date('Y-m-d'))) {
    header("Location: search.php?error=unavailable");
    exit();
}

$booking_error = '';
$booking_success_id = 0;

if (isset($_POST['confirm_booking'])) {
    $names = $_POST['name'];   
    $emails = $_POST['email']; 
    $dobs = $_POST['dob'];
    $contacts = $_POST['contact'];
    $classes = $_POST['class'];
    $types = $_POST['passenger_type'];
    
    $booked_by = $_SESSION['user_id'];
    $first_booking_id = 0;

    $conn->begin_transaction();

    try {
        $check_seats = $conn->prepare("SELECT available_seats FROM flights WHERE id = ? FOR UPDATE");
        $check_seats->bind_param("i", $flight_id);
        $check_seats->execute();
        $current_seats = $check_seats->get_result()->fetch_assoc()['available_seats'];

        if ($current_seats < $num_passengers) {
            throw new Exception("Seats were taken by another user. Please try again.");
        }

        $insert_stmt = $conn->prepare("INSERT INTO bookings (flight_id, user_id, passenger_name, email, dob, contact, class, passenger_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Booked')");
        if (!$insert_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        for ($i = 0; $i < $num_passengers; $i++) {
            $name    = trim($names[$i]);
            $email   = trim($emails[$i]);
            $dob     = $dobs[$i];
            $contact = trim($contacts[$i]);
            $class   = $classes[$i];
            $type    = $types[$i];

            $insert_stmt->bind_param("iissssss", $flight_id, $booked_by, $name, $email, $dob, $contact, $class, $type);
            
            if (!$insert_stmt->execute()) {
                throw new Exception("Error inserting passenger " . ($i + 1) . ": " . $insert_stmt->error);
            }
            
            if ($i === 0) {
                $first_booking_id = $conn->insert_id;
            }
        }

        $update_seats = $conn->prepare("UPDATE flights SET available_seats = available_seats - ? WHERE id = ?");
        $update_seats->bind_param("ii", $num_passengers, $flight_id);
        
        if (!$update_seats->execute()) {
            throw new Exception("Error updating seat availability: " . $update_seats->error);
        }

        $conn->commit();

        ob_end_clean();
        header("Location: receipt.php?id=$first_booking_id&booked=1");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $booking_error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Booking | SkyLine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: white; }
        .glass-panel { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); }
        select { color-scheme: dark; }
    </style>
</head>
<body class="min-h-screen p-6 md:p-10 flex flex-col items-center">

    <div class="max-w-6xl w-full grid lg:grid-cols-3 gap-10">
        
        <!-- SIDEBAR: TRIP SUMMARY -->
        <div class="lg:col-span-1 space-y-6">
            <div class="flex items-center gap-3">
                <h2 class="text-4xl font-black italic tracking-tighter">SKY<span class="text-blue-500">LINE.</span></h2>
            </div>
            
            <div class="glass-panel p-6 rounded-3xl sticky top-10">
                <p class="text-blue-400 font-bold text-xs uppercase mb-2 tracking-widest">Trip Summary</p>
                <h3 class="text-2xl font-bold"><?php echo htmlspecialchars($flight['origin']); ?> → <?php echo htmlspecialchars($flight['destination']); ?></h3>
                
                <div class="mt-6 space-y-4 text-sm">
                    <div class="flex justify-between border-b border-white/5 pb-2">
                        <span class="text-gray-400">Date:</span> 
                        <span class="text-white font-semibold"><?php echo date('M d, Y', strtotime($flight['departure_date'])); ?></span>
                    </div>
                    <div class="flex justify-between border-b border-white/5 pb-2">
                        <span class="text-gray-400">Main Contact:</span> 
                        <span class="text-white font-semibold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </div>
                    <div class="flex justify-between border-b border-white/5 pb-2">
                        <span class="text-gray-400">Quantity:</span> 
                        <span class="text-white"><?php echo $num_passengers; ?> Traveler(s)</span>
                    </div>
                    <div class="pt-4 text-center">
                        <p class="text-gray-500 text-[10px] uppercase font-bold mb-1">Total Payable Amount</p>
                        <p class="text-4xl font-black text-blue-500">₹<?php echo number_format($flight['price'] * $num_passengers, 2); ?></p>
                    </div>
                </div>

                <a href="search.php" class="block text-center mt-6 text-gray-500 hover:text-white text-xs uppercase font-bold tracking-widest transition">
                    ✕ Cancel & Go Back
                </a>
            </div>
        </div>

        <!-- MAIN FORM SECTION -->
        <div class="lg:col-span-2">
            <form method="POST" class="space-y-8">
                
                <?php for($i = 0; $i < $num_passengers; $i++): ?>
                <div class="glass-panel p-8 rounded-[2.5rem] relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5 font-black text-8xl italic select-none"><?php echo ($i + 1); ?></div>
                    
                    <h3 class="text-xl font-bold mb-8 flex items-center gap-3">
                        <span class="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center text-sm rotate-3 shadow-lg shadow-blue-900/50">
                            <span class="-rotate-3"><?php echo ($i + 1); ?></span>
                        </span>
                        Passenger <?php echo ($i + 1); ?> Details
                    </h3>
                    
                    <div class="grid md:grid-cols-2 gap-x-8 gap-y-6">
                        <div class="space-y-1">
                            <label class="text-gray-500 text-[10px] uppercase font-bold ml-1 tracking-widest">Full Name</label>
                            <input type="text" name="name[]" required placeholder="As per Govt. ID"
                                   value="<?php echo ($i === 0) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>"
                                   class="w-full bg-white/5 border border-white/10 p-4 rounded-xl text-white outline-none focus:border-blue-500 transition placeholder:text-gray-700">
                        </div>
                        <div class="space-y-1">
                            <label class="text-gray-500 text-[10px] uppercase font-bold ml-1 tracking-widest">Email Address</label>
                            <input type="email" name="email[]" required placeholder="contact@email.com"
                                   value="<?php echo ($i === 0 && isset($_SESSION['user_email'])) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>"
                                   class="w-full bg-white/5 border border-white/10 p-4 rounded-xl text-white outline-none focus:border-blue-500 transition placeholder:text-gray-700">
                        </div>
                        <div class="space-y-1">
                            <label class="text-gray-500 text-[10px] uppercase font-bold ml-1 tracking-widest">Date of Birth</label>
                            <input type="date" name="dob[]" required 
                                   class="w-full bg-white/5 border border-white/10 p-4 rounded-xl text-white outline-none focus:border-blue-500 transition">
                        </div>
                        <div class="space-y-1">
                            <label class="text-gray-500 text-[10px] uppercase font-bold ml-1 tracking-widest">Phone Number</label>
                            <input type="text" name="contact[]" required placeholder="+1 234 567 890"
                                   class="w-full bg-white/5 border border-white/10 p-4 rounded-xl text-white outline-none focus:border-blue-500 transition placeholder:text-gray-700">
                        </div>
                        <div class="space-y-1">
                            <label class="text-gray-500 text-[10px] uppercase font-bold ml-1 tracking-widest">Cabin Class</label>
                            <select name="class[]" class="w-full bg-[#1e293b] border border-white/10 p-4 rounded-xl outline-none focus:border-blue-500 cursor-pointer">
                                <option value="Economy">Economy Class</option>
                                <option value="Business">Business Class</option>
                                <option value="First">First Class</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-gray-500 text-[10px] uppercase font-bold ml-1 tracking-widest">Passenger Type</label>
                            <select name="passenger_type[]" class="w-full bg-[#1e293b] border border-white/10 p-4 rounded-xl outline-none focus:border-blue-500 cursor-pointer">
                                <option value="Adult">Adult (12+ Years)</option>
                                <option value="Child">Child (2-11 Years)</option>
                                <option value="Infant">Infant (Under 2)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>

                <div class="pb-20">
                    <button type="submit" name="confirm_booking" 
                            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-6 rounded-3xl transition shadow-2xl shadow-blue-900/40 text-xl active:scale-[0.98] mt-4">
                        Confirm & Generate Tickets
                    </button>
                    <p class="text-center text-gray-500 text-xs mt-4">By clicking confirm, you agree to SkyLine's Terms of Service and Privacy Policy.</p>
                </div>
            </form>
        </div>
    </div>


    <!-- Error Banner -->
    <div id="errorBanner" style="display:<?php echo $booking_error ? 'flex' : 'none'; ?>;position:fixed;top:24px;left:50%;transform:translateX(-50%);z-index:60;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;padding:14px 24px;border-radius:16px;font-size:14px;font-weight:600;align-items:center;gap:10px;max-width:500px;width:90%">
        ⚠️ <span id="errorMsg"><?php echo htmlspecialchars($booking_error); ?></span>
        <button onclick="this.parentElement.style.display='none'" style="margin-left:auto;background:none;border:none;color:#fca5a5;cursor:pointer;font-size:18px">✕</button>
    </div>

    <!-- Payment Success Modal -->
    <div id="paymentModal" style="display:none;position:fixed;inset:0;z-index:50;background:rgba(0,0,0,0.8);backdrop-filter:blur(8px);align-items:center;justify-content:center;padding:20px">
        <div style="background:#0f172a;border:1px solid rgba(255,255,255,0.1);border-radius:28px;padding:48px 40px;max-width:440px;width:100%;text-align:center;position:relative">
            <!-- Animated checkmark -->
            <div style="width:80px;height:80px;background:linear-gradient(135deg,#16a34a,#22c55e);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;box-shadow:0 0 40px rgba(34,197,94,0.4)">
                <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 style="color:white;font-size:26px;font-weight:800;margin:0 0 8px">Payment Successful!</h2>
            <p style="color:#22c55e;font-size:15px;font-weight:600;margin:0 0 6px">✈️ Your tickets are confirmed</p>
            <p style="color:#64748b;font-size:13px;margin:0 0 28px">Redirecting you to your boarding passes...</p>
            <!-- Animated progress bar -->
            <div style="background:rgba(255,255,255,0.07);border-radius:999px;height:4px;overflow:hidden">
                <div style="background:linear-gradient(90deg,#3b82f6,#22c55e);height:100%;border-radius:999px;animation:progressBar 4s linear forwards"></div>
            </div>
            <p style="color:#475569;font-size:11px;margin-top:12px;text-transform:uppercase;letter-spacing:0.1em">Generating boarding passes...</p>
        </div>
    </div>

    <style>
        @keyframes progressBar { from{width:0%} to{width:100%} }
    </style>

</body>
</html>