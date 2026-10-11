<?php
include 'db.php';

$dest = isset($_GET['destination']) ? trim($_GET['destination']) : '';
$passengers = isset($_GET['passengers']) ? max(1, intval($_GET['passengers'])) : 1;

// Fix: use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM flights 
    WHERE (destination LIKE ? OR origin LIKE ?) 
    AND available_seats >= ? 
    AND status != 'Cancelled' 
    AND departure_date >= CURDATE()
    ORDER BY departure_date ASC");

$like = "%" . $dest . "%";
$stmt->bind_param("ssi", $like, $like, $passengers);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results | SkyLine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: white; }
        .glass-card { background: rgba(255,255,255,0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); transition: all 0.3s ease; }
        .glass-card:hover { background: rgba(255,255,255,0.07); transform: translateY(-5px); border-color: rgba(59,130,246,0.3); }
    </style>
</head>
<body class="min-h-screen p-8 md:p-16">
    <header class="max-w-6xl mx-auto flex justify-between items-center mb-16">
        <div>
            <h1 class="text-4xl font-bold tracking-tighter italic">SKY<span class="text-blue-500">LINE.</span></h1>
            <p class="text-gray-400 mt-2">
                Flights to <span class="text-blue-400 font-semibold">"<?php echo htmlspecialchars($dest); ?>"</span>
                for <span class="text-white font-bold"><?php echo $passengers; ?> Traveler(s)</span>
            </p>
        </div>
        <a href="dashboard.php" class="bg-white/10 hover:bg-white/20 px-6 py-2 rounded-full text-sm font-medium transition border border-white/10">← New Search</a>
    </header>

    <main class="max-w-6xl mx-auto space-y-6">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="glass-card p-8 rounded-[2.5rem] flex flex-col md:flex-row justify-between items-center gap-8">
                    <div class="flex-1">
                        <div class="flex items-center gap-4 mb-3">
                            <?php if($row['status'] == 'Delayed'): ?>
                                <span class="bg-orange-500/20 text-orange-400 text-[10px] font-bold px-3 py-1 rounded-full uppercase">🕒 Delayed</span>
                            <?php else: ?>
                                <span class="bg-blue-600/20 text-blue-400 text-[10px] font-bold px-3 py-1 rounded-full uppercase">On Time</span>
                            <?php endif; ?>
                            <span class="text-gray-500 text-xs">Flight #<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="text-3xl font-bold"><?php echo htmlspecialchars($row['origin']); ?></div>
                            <div class="text-blue-500 text-2xl flex flex-col items-center">
                                <span class="text-[10px] uppercase font-black tracking-widest mb-1 opacity-40">Direct</span>✈
                            </div>
                            <div class="text-3xl font-bold"><?php echo htmlspecialchars($row['destination']); ?></div>
                        </div>
                        <p class="text-gray-400 mt-2 font-light">
                            Departure: <span class="text-white font-medium"><?php echo date('D, M d, Y', strtotime($row['departure_date'])); ?></span>
                        </p>
                    </div>
                    <div class="flex items-center gap-10 border-t md:border-t-0 md:border-l border-white/10 pt-6 md:pt-0 md:pl-10 w-full md:w-auto justify-between">
                        <div class="text-center md:text-right">
                            <p class="text-gray-500 text-[10px] uppercase font-black tracking-[0.2em] mb-1">Total Group Price</p>
                            <p class="text-4xl font-black text-white"> ₹<?php echo number_format($row['price'] * $passengers, 2); ?></p>
                            <p class="text-xs text-blue-400 mt-1 font-medium"><?php echo $row['available_seats']; ?> seats remaining</p>
                        </div>
                        <a href="book.php?id=<?php echo $row['id']; ?>&passengers=<?php echo $passengers; ?>"
                           class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-5 px-12 rounded-2xl transition shadow-xl shadow-blue-900/40 active:scale-95 text-center">
                            Select
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="glass-card p-20 rounded-[3rem] text-center border-dashed border-white/10">
                <div class="text-6xl mb-6">☁️</div>
                <h3 class="text-2xl font-bold mb-2">No Seats Available</h3>
                <p class="text-gray-400 max-w-sm mx-auto">We couldn't find a flight to "<?php echo htmlspecialchars($dest); ?>" with at least <?php echo $passengers; ?> available seats.</p>
                <a href="dashboard.php" class="inline-block mt-8 bg-white text-black font-bold py-3 px-8 rounded-full hover:bg-gray-200 transition">Adjust Search</a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="max-w-6xl mx-auto mt-20 text-center text-gray-600 text-xs pb-10 uppercase tracking-widest">
        &copy; 2026 SkyLine LAGH Airlines &bull; Premium Cloud Travel
    </footer>
</body>
</html>
