<?php
ob_start();
session_start();
include 'db.php';

// If already logged in as admin, go to dashboard
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin.php");
    exit();
}

$error = "";

if (isset($_POST['login'])) {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    if (empty($user) || empty($pass)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND password = ? LIMIT 1");
        if (!$stmt) {
            $error = "Query error: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $user, $pass);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $admin_data = $result->fetch_assoc();
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $admin_data['username'];
                header("Location: admin.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | SkyLine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family:'Inter',sans-serif; }
        body { background:linear-gradient(160deg,#050b1a 0%,#091428 45%,#04090f 100%); min-height:100vh; }
        .star { position:absolute; background:white; border-radius:50%; animation:twinkle 3s infinite; }
        @keyframes twinkle { 0%,100%{opacity:0.15} 50%{opacity:0.9} }
        .glass { background:rgba(255,255,255,0.035); backdrop-filter:blur(24px); -webkit-backdrop-filter:blur(24px); border:1px solid rgba(255,255,255,0.07); }
        .input-field { background:rgba(255,255,255,0.04); border:1.5px solid rgba(255,255,255,0.07); color:white; transition:all 0.3s; width:100%; padding:14px 18px; border-radius:14px; outline:none; font-size:15px; }
        .input-field:focus { border-color:#ef4444; background:rgba(239,68,68,0.07); box-shadow:0 0 0 4px rgba(239,68,68,0.1); }
        .input-field::placeholder { color:rgba(255,255,255,0.22); }
        .btn-admin { background:linear-gradient(135deg,#dc2626,#b91c1c); box-shadow:0 8px 32px rgba(220,38,38,0.35); transition:all 0.3s; border:none; cursor:pointer; }
        .btn-admin:hover { background:linear-gradient(135deg,#ef4444,#dc2626); box-shadow:0 12px 40px rgba(220,38,38,0.5); transform:translateY(-1px); }
        .divider { height:1px; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.06),transparent); }
        .admin-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 14px; border-radius:999px; font-size:11px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); color:#fca5a5; }
    </style>
</head>
<body class="relative flex items-center justify-center p-6" style="min-height:100vh;overflow-y:auto;padding-top:32px;padding-bottom:32px;">

    <!-- Stars -->
    <?php for($i=0;$i<35;$i++): $s=rand(1,2);$t=rand(0,100);$l=rand(0,100);$d=rand(0,400)/100; ?>
    <div class="star" style="width:<?=$s?>px;height:<?=$s?>px;top:<?=$t?>%;left:<?=$l?>%;animation-delay:<?=$d?>s;animation-duration:<?=rand(20,50)/10?>s"></div>
    <?php endfor; ?>

    <!-- Glow -->
    <div style="position:absolute;width:400px;height:400px;background:radial-gradient(circle,rgba(239,68,68,0.06),transparent 70%);top:-80px;right:-80px;border-radius:50%;pointer-events:none"></div>
    <div style="position:absolute;width:300px;height:300px;background:radial-gradient(circle,rgba(99,102,241,0.05),transparent 70%);bottom:-60px;left:-60px;border-radius:50%;pointer-events:none"></div>

    <div class="relative z-10 w-full max-w-sm">

        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4"
                 style="background:linear-gradient(135deg,#991b1b,#dc2626);box-shadow:0 8px 32px rgba(220,38,38,0.4)">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-white font-bold text-2xl tracking-tight">SkyLine Admin</h1>
            <div class="admin-badge mt-3">🔒 Restricted Access</div>
        </div>

        <!-- Card -->
        <div class="glass rounded-3xl p-8 shadow-2xl">
            <h2 class="text-white text-xl font-bold mb-1">Sign in to Dashboard</h2>
            <p class="text-gray-500 text-sm mb-7">Authorized personnel only</p>

            <?php if($error): ?>
            <div class="flex items-center gap-3 p-4 rounded-2xl mb-6 text-sm font-medium"
                 style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#fca5a5">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Username</label>
                    <input type="text" name="username" required autocomplete="off" placeholder="admin"
                           value="<?=htmlspecialchars($_POST['username']??'')?>" class="input-field">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Password</label>
                    <div class="relative">
                        <input type="text" name="password" id="adminPass" required placeholder="Enter password" class="input-field" style="padding-right:48px">
                        <button type="button" onclick="togglePass(this)" aria-label="Toggle password" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#000;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" name="login" class="btn-admin w-full text-white font-bold py-4 rounded-2xl text-base mt-2">
                    Access Dashboard →
                </button>
            </form>

            <div class="divider my-6"></div>

            <!-- Return to Home -->
            <div class="text-center">
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
