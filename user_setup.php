<?php
ob_start();
session_start();
include 'db.php';

// If already logged in, go home
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = "";
$formData = [];

if (isset($_POST['register'])) {
    $name     = trim($_POST['u_name']);
    $email    = trim($_POST['u_email']);
    $password = trim($_POST['u_password']);
    $dob      = $_POST['u_dob'];
    $contact  = trim($_POST['u_contact']);

    // Keep form data on error
    $formData = ['name'=>$name,'email'=>$email,'dob'=>$dob,'contact'=>$contact];

    // Validate
    if (empty($name) || empty($email) || empty($password)) {
        $message = "Name, email and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        // Check users table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
        if ($tableCheck->num_rows === 0) {
            $message = "Database error: 'users' table not found. Please import the updated airline_db.sql file.";
        } else {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $message = "This email is already registered. Try logging in.";
            } else {
                $ins = $conn->prepare("INSERT INTO users (name, email, password, dob, contact) VALUES (?, ?, ?, ?, ?)");
                $ins->bind_param("sssss", $name, $email, $password, $dob, $contact);

                if ($ins->execute()) {
                    $ins->close();
                    $check->close();
                    // Redirect to login page after successful registration
                    header("Location: login_user.php?registered=1");
                    exit();
                } else {
                    $message = "Registration failed: " . $conn->error;
                    $ins->close();
                }
            }
            $check->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | SkyLine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family:'Inter',sans-serif; }
        body { background:linear-gradient(135deg,#0a0f1e 0%,#0d1b3e 40%,#0a1628 70%,#030712 100%); min-height:100vh; overflow-y:auto; }
        .star { position:absolute; background:white; border-radius:50%; animation:twinkle 3s infinite; }
        @keyframes twinkle { 0%,100%{opacity:0.2;transform:scale(1)} 50%{opacity:1;transform:scale(1.3)} }
        .glass { background:rgba(255,255,255,0.04); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,0.08); }
        .input-field { background:rgba(255,255,255,0.05); border:1.5px solid rgba(255,255,255,0.08); color:white; transition:all 0.3s; width:100%; padding:13px 18px; border-radius:14px; outline:none; font-size:14px; }
        .input-field:focus { border-color:#3b82f6; background:rgba(59,130,246,0.08); box-shadow:0 0 0 4px rgba(59,130,246,0.12); }
        .input-field::placeholder { color:rgba(255,255,255,0.25); }
        input[type="date"].input-field::-webkit-calendar-picker-indicator { filter:invert(0.5); cursor:pointer; }
        .btn-primary { background:linear-gradient(135deg,#2563eb,#1d4ed8); box-shadow:0 8px 32px rgba(37,99,235,0.4); transition:all 0.3s; border:none; cursor:pointer; }
        .btn-primary:hover { background:linear-gradient(135deg,#3b82f6,#2563eb); box-shadow:0 12px 40px rgba(37,99,235,0.55); transform:translateY(-1px); }
        .divider { height:1px; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.08),transparent); }
        label { display:block; font-size:11px; font-weight:600; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:8px; }
        .plane-trail { position:absolute; top:10%; left:-100px; font-size:2rem; animation:fly 14s linear infinite; opacity:0.12; }
        @keyframes fly { 0%{left:-100px} 100%{left:110%} }
    </style>
</head>
<body class="relative flex items-center justify-center p-6 py-12" style="min-height:100vh;">

    <div class="plane-trail">✈</div>
    <?php for($i=0;$i<28;$i++): $s=rand(1,3);$t=rand(0,100);$l=rand(0,100);$d=rand(0,300)/100; ?>
    <div class="star" style="width:<?=$s?>px;height:<?=$s?>px;top:<?=$t?>%;left:<?=$l?>%;animation-delay:<?=$d?>s;animation-duration:<?=rand(20,40)/10?>s"></div>
    <?php endfor; ?>

    <div class="relative z-10 w-full max-w-lg">

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
            <h2 class="text-white text-2xl font-bold mb-1">Create your account</h2>
            <p class="text-gray-400 text-sm mb-8">Join SkyLine and start booking flights instantly</p>

            <?php if($message): ?>
            <div class="flex items-start gap-3 p-4 rounded-2xl mb-6 text-sm font-medium"
                 style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#fca5a5">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label>Full Name</label>
                    <input type="text" name="u_name" required placeholder="John Doe"
                           value="<?=htmlspecialchars($formData['name']??'')?>" class="input-field">
                </div>
                <div>
                    <label>Email Address</label>
                    <input type="email" name="u_email" required placeholder="you@example.com"
                           value="<?=htmlspecialchars($formData['email']??'')?>" class="input-field">
                </div>
                <div>
                    <label>Password</label>
                    <div class="relative">
                        <input type="text" name="u_password" id="regPass" required placeholder="Create a strong password" class="input-field" style="padding-right:48px">
                        <button type="button" onclick="togglePass(this)" aria-label="Toggle password" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#000;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label>Date of Birth</label>
                        <input type="date" name="u_dob" required
                               value="<?=htmlspecialchars($formData['dob']??'')?>" class="input-field">
                    </div>
                    <div>
                        <label>Contact</label>
                        <input type="tel" name="u_contact" required placeholder="9876543210"
                               value="<?=htmlspecialchars($formData['contact']??'')?>" class="input-field">
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" name="register" class="btn-primary w-full text-white font-bold py-4 rounded-2xl text-base">
                        Create Account →
                    </button>
                </div>
            </form>

            <div class="divider my-7"></div>

            <p class="text-center text-gray-500 text-sm">
                Already have an account?
                <a href="login_user.php" class="text-blue-400 font-semibold hover:text-blue-300 transition ml-1">Sign in</a>
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
