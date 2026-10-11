<?php
ob_start();
session_start();
include 'db.php';

// If already logged in, go home
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$success = "";

if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = "Account created successfully! Please log in.";
}

if (isset($_GET['error']) && $_GET['error'] == 'login_required') {
    $error = "Please login to proceed with your booking.";
}

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Check if users table exists first
        $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
        if ($tableCheck->num_rows === 0) {
            $error = "Database error: 'users' table not found. Please import the updated airline_db.sql file.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password = ? LIMIT 1");
            if (!$stmt) {
                $error = "Query error: " . $conn->error;
            } else {
                $stmt->bind_param("ss", $email, $password);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['user_name']  = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Invalid email or password. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login | SkyLine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #0a0f1e 0%, #0d1b3e 40%, #0a1628 70%, #030712 100%);
            min-height: 100vh;
        }
        .star { position: absolute; background: white; border-radius: 50%; animation: twinkle 3s infinite; }
        @keyframes twinkle { 0%,100%{opacity:0.2;transform:scale(1)} 50%{opacity:1;transform:scale(1.3)} }
        .glass { background:rgba(255,255,255,0.04); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,0.08); }
        .input-field { background:rgba(255,255,255,0.05); border:1.5px solid rgba(255,255,255,0.08); color:white; transition:all 0.3s; width:100%; padding:14px 18px; border-radius:14px; outline:none; font-size:15px; }
        .input-field:focus { border-color:#3b82f6; background:rgba(59,130,246,0.08); box-shadow:0 0 0 4px rgba(59,130,246,0.12); }
        .input-field::placeholder { color:rgba(255,255,255,0.25); }
        .btn-primary { background:linear-gradient(135deg,#2563eb,#1d4ed8); box-shadow:0 8px 32px rgba(37,99,235,0.4); transition:all 0.3s; border:none; cursor:pointer; }
        .btn-primary:hover { background:linear-gradient(135deg,#3b82f6,#2563eb); box-shadow:0 12px 40px rgba(37,99,235,0.55); transform:translateY(-1px); }
        .divider { height:1px; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.08),transparent); }
        .plane-trail { position:absolute; top:18%; left:-100px; font-size:2rem; animation:flyPlane 12s linear infinite; opacity:0.12; }
        @keyframes flyPlane { 0%{left:-100px;top:18%} 100%{left:110%;top:12%} }
    </style>
</head>
<body class="relative flex items-center justify-center p-6" style="min-height:100vh;overflow-y:auto;padding-top:32px;padding-bottom:32px;">

    <div class="plane-trail">✈</div>
    <?php for($i=0;$i<30;$i++): $s=rand(1,3);$t=rand(0,100);$l=rand(0,100);$d=rand(0,300)/100; ?>
    <div class="star" style="width:<?=$s?>px;height:<?=$s?>px;top:<?=$t?>%;left:<?=$l?>%;animation-delay:<?=$d?>s;animation-duration:<?=rand(20,40)/10?>s"></div>
    <?php endfor; ?>

    <div class="relative z-10 w-full max-w-md">

        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4"
                 style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);box-shadow:0 8px 32px rgba(37,99,235,0.4)">
                <span style="font-size:1.75rem">✈️</span>
            </div>
            <h1 class="text-white font-bold text-3xl tracking-tight">SkyLine</h1>
            <p class="text-blue-400 text-sm font-medium mt-1 tracking-wide">YOUR JOURNEY STARTS HERE</p>
        </div>

        <!-- Card -->
        <div class="glass rounded-3xl p-9 shadow-2xl">
            <h2 class="text-white text-2xl font-bold mb-1">Welcome back</h2>
            <p class="text-gray-400 text-sm mb-8">Sign in to manage your bookings</p>

            <?php if($success): ?>
            <div class="flex items-center gap-3 p-4 rounded-2xl mb-6 text-sm font-medium"
                 style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:#86efac">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <?php if($error): ?>
            <div class="flex items-center gap-3 p-4 rounded-2xl mb-6 text-sm font-medium"
                 style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#fca5a5">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form action="login_user.php" method="POST" class="space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Email Address</label>
                    <input type="email" name="email" required placeholder="you@example.com"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           class="input-field">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Password</label>
                    <div class="relative">
                        <input type="text" name="password" id="userPass" required placeholder="Enter your password" class="input-field" style="padding-right:48px">
                        <button type="button" onclick="togglePass(this)" aria-label="Toggle password" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#000;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" name="login" class="btn-primary w-full text-white font-bold py-4 rounded-2xl text-base mt-2">
                    Sign In →
                </button>
            </form>

            <div class="divider my-7"></div>

            <p class="text-center text-gray-500 text-sm">
                New to SkyLine?
                <a href="user_setup.php" class="text-blue-400 font-semibold hover:text-blue-300 transition ml-1">Create an account</a>
            </p>

            <!-- Return to Home -->
            <div class="mt-5 text-center">
                <a href="index.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-white text-xs font-semibold uppercase tracking-widest transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Return to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePass(btn) {
            var input = btn.parentElement.querySelector('input');
            var show = input.type === 'text';
            input.type = show ? 'password' : 'text';
            btn.innerHTML = show
                ? `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>`
                : `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
        }
    </script>
</body>
</html>
