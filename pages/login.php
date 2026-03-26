<?php
require_once __DIR__ . '/../includes/db.php';

$error = '';
$success = '';
$action = $_POST['action'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $access_code = $_POST['access_code'] ?? '';
    $security_pin = trim($_POST['security_pin'] ?? '0000');

    if ($action === 'register' && $username && $password) {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            $error = "Username or Email already exists.";
        } else {
            if ($role === 'teacher' && $access_code !== 'TEACHER2024') {
                $error = "Invalid Teacher Access Code.";
            } elseif ($role === 'admin' && $access_code !== 'ADMIN2024') {
                $error = "Invalid Admin Access Code.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, security_pin) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hash, $role, $security_pin]);
                $success = "🎉 Account created! You can now sign in.";
            }
        }
    } elseif ($action === 'login' && $username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username/email or password.";
        }
    } elseif ($action === 'reset_pass') {
        $reset_email = trim($_POST['reset_email'] ?? '');
        $reset_pin   = trim($_POST['reset_pin'] ?? '');
        $new_pass    = $_POST['new_password'] ?? '';
        if ($reset_email && $reset_pin && $new_pass) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND security_pin = ?");
            $stmt->execute([$reset_email, $reset_pin]);
            $user = $stmt->fetch();
            if ($user) {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user['id']]);
                $success = "✅ Password reset! You can now sign in.";
                $action = 'login';
            } else {
                $error = "Incorrect email or Security PIN.";
            }
        } else {
            $error = "Please fill in all recovery fields.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizMaster Pro — Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue-1: #0ea5e9;
            --blue-2: #38bdf8;
            --blue-3: #7dd3fc;
            --indigo: #6366f1;
            --white: #ffffff;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-400: #94a3b8;
            --slate-600: #475569;
            --slate-800: #1e293b;
            --radius: 20px;
        }

        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: var(--slate-100);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* ── Animated CSS Background ── */
        .bg-scene {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 40%, #dbeafe 70%, #e0e7ff 100%);
            z-index: 0;
            overflow: hidden;
        }
        .bg-scene::before, .bg-scene::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.55;
            animation: drift 18s ease-in-out infinite alternate;
        }
        .bg-scene::before {
            width: 700px; height: 700px;
            background: radial-gradient(circle, #93c5fd, #6366f1);
            top: -20%; left: -15%;
        }
        .bg-scene::after {
            width: 600px; height: 600px;
            background: radial-gradient(circle, #38bdf8, #a5f3fc);
            bottom: -20%; right: -10%;
            animation-delay: -9s;
        }
        .bg-dot {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, #c7d2fe, #e0f2fe);
            filter: blur(50px);
            opacity: 0.4;
            animation: drift 24s ease-in-out infinite alternate;
            animation-delay: -4s;
            width: 400px; height: 400px;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
        }
        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(60px, 40px) scale(1.1); }
        }

        /* ── Main Card ── */
        .auth-wrapper {
            position: relative;
            z-index: 10;
            width: 1080px;
            max-width: 98vw;
            min-height: 640px;
            display: flex;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 40px 120px rgba(14, 100, 200, 0.18), 0 4px 20px rgba(0,0,0,0.07);
        }

        /* ── Left Decorative Panel ── */
        .deco-panel {
            flex: 0 0 420px;
            background: linear-gradient(145deg, #0284c7, #2563eb, #4f46e5);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 50px;
            overflow: hidden;
            transition: transform 0.7s cubic-bezier(0.77,0,0.18,1);
        }
        .deco-panel::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
            top: -200px; right: -200px;
        }
        .deco-panel::after {
            content: '';
            position: absolute;
            width: 350px; height: 350px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            bottom: -120px; left: -120px;
        }
        .deco-logo {
            width: 80px; height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 22px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            font-size: 2rem;
            color: #fff;
            position: relative; z-index: 2;
        }
        .deco-panel h2 {
            font-size: 2.1rem; font-weight: 800;
            color: #fff; text-align: center;
            line-height: 1.25; margin-bottom: 18px;
            position: relative; z-index: 2;
        }
        .deco-panel p {
            font-size: 0.97rem; color: rgba(255,255,255,0.78);
            text-align: center; line-height: 1.7;
            position: relative; z-index: 2; margin-bottom: 36px;
        }
        .deco-btn {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            border: 1.5px solid rgba(255,255,255,0.5);
            color: #fff; font-weight: 700; font-size: 0.95rem;
            padding: 13px 36px; border-radius: 50px;
            cursor: pointer; transition: all 0.3s;
            position: relative; z-index: 2;
        }
        .deco-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        .deco-dots {
            position: absolute; bottom: 40px; z-index: 2;
            display: flex; gap: 8px;
        }
        .deco-dots span {
            width: 8px; height: 8px; border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transition: 0.3s;
        }
        .deco-dots span.active { background: #fff; width: 24px; border-radius: 4px; }

        /* ── Forms Panel ── */
        .forms-panel {
            flex: 1;
            background: #fff;
            position: relative;
            overflow: hidden;
            min-height: 640px;
        }

        /* Each form screen */
        .form-screen {
            position: absolute;
            inset: 0;
            padding: 50px 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transform: translateX(30px);
            transition: opacity 0.45s ease, transform 0.45s ease, visibility 0s 0.45s;
            overflow-y: auto;
        }
        .form-screen.active {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
            transition: opacity 0.45s ease, transform 0.45s ease;
        }
        .form-screen::-webkit-scrollbar { width: 4px; }
        .form-screen::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

        .form-screen h1 {
            font-size: 1.9rem; font-weight: 800;
            color: var(--slate-800); margin-bottom: 6px;
        }
        .form-screen .subtitle {
            font-size: 0.88rem; color: var(--slate-400);
            margin-bottom: 28px; font-weight: 400;
        }

        /* Input fields */
        .field { margin-bottom: 16px; }
        .field label {
            display: block; font-size: 0.8rem; font-weight: 600;
            color: var(--slate-600); margin-bottom: 6px;
            text-transform: uppercase; letter-spacing: 0.04em;
        }
        .field-inner { position: relative; }
        .field-inner i {
            position: absolute; left: 15px; top: 50%;
            transform: translateY(-50%); color: var(--slate-400);
            font-size: 0.9rem; pointer-events: none;
        }
        .field input, .field select {
            width: 100%; padding: 12px 14px 12px 42px;
            background: var(--slate-100); border: 1.5px solid var(--slate-200);
            border-radius: 12px; font-size: 0.93rem; color: var(--slate-800);
            font-family: 'Inter', sans-serif;
            transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
            outline: none;
        }
        .field input:focus, .field select:focus {
            border-color: var(--blue-1);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12);
        }

        /* Two-col grid for signup */
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        /* Buttons */
        .btn-primary {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #0ea5e9, #3b82f6);
            color: #fff; font-weight: 700; font-size: 1rem;
            border: none; border-radius: 12px; cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.35);
            margin-top: 6px;
            font-family: 'Inter', sans-serif;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(14, 165, 233, 0.45); }
        .btn-primary:active { transform: translateY(0); }

        /* Alert */
        .alert {
            padding: 12px 16px; border-radius: 10px;
            display: flex; align-items: center; gap: 10px;
            font-size: 0.87rem; font-weight: 500; margin-bottom: 20px;
        }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        /* Switch link */
        .switch-text {
            margin-top: 20px; text-align: center;
            font-size: 0.85rem; color: var(--slate-400);
        }
        .switch-text a { color: var(--blue-1); font-weight: 600; text-decoration: none; }
        .switch-text a:hover { text-decoration: underline; }

        /* Role badge selector */
        .role-selector { display: flex; gap: 10px; margin-bottom: 16px; }
        .role-btn {
            flex: 1; padding: 10px 8px; border-radius: 10px;
            border: 1.5px solid var(--slate-200); background: var(--slate-100);
            cursor: pointer; transition: 0.2s; text-align: center;
            font-size: 0.8rem; font-weight: 600; color: var(--slate-600);
        }
        .role-btn i { display: block; font-size: 1.2rem; margin-bottom: 4px; color: var(--slate-400); }
        .role-btn.active {
            border-color: var(--blue-1); background: #eff6ff;
            color: var(--blue-1);
        }
        .role-btn.active i { color: var(--blue-1); }

        /* Divider */
        .divider { display: flex; align-items: center; gap: 12px; margin: 14px 0; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--slate-200); }
        .divider span { font-size: 0.78rem; color: var(--slate-400); font-weight: 500; }

        /* Forgot link */
        .forgot-link {
            text-align: right; margin-top: -8px; margin-bottom: 14px;
        }
        .forgot-link a { font-size: 0.8rem; color: var(--blue-1); text-decoration: none; font-weight: 500; }

        /* Mobile */
        @media (max-width: 820px) {
            .deco-panel { display: none; }
            .auth-wrapper { width: 480px; max-width: 96vw; border-radius: 22px; }
            .form-screen { padding: 40px 30px; }
        }
    </style>
</head>
<body>
<div class="bg-scene"><div class="bg-dot"></div></div>

<div class="auth-wrapper">
    <!-- Left decorative panel -->
    <div class="deco-panel" id="decoPanel">
        <div class="deco-logo"><i class="fas fa-brain"></i></div>
        <h2 id="decoTitle">Welcome Back!</h2>
        <p id="decoSubtitle">Sign in to track your progress, beat the leaderboard, and ace your next quiz.</p>
        <button class="deco-btn" id="decoBtn" onclick="showScreen('signup')">Create Account</button>
        <div class="deco-dots">
            <span class="active" id="dot-login"></span>
            <span id="dot-signup"></span>
            <span id="dot-forgot"></span>
        </div>
    </div>

    <!-- Right forms panel -->
    <div class="forms-panel">

        <!-- ── LOGIN ── -->
        <div class="form-screen <?= $action === 'login' || $action === 'reset_pass' && !$error ? 'active' : '' ?> <?= $action === 'register' ? '' : '' ?>" id="screen-login">
            <h1>Sign In</h1>
            <p class="subtitle">Welcome back! Enter your credentials below.</p>

            <?php if ($error && $action === 'login'): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="field">
                    <label>Username or Email</label>
                    <div class="field-inner">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Enter your username or email" required>
                    </div>
                </div>
                <div class="field">
                    <label>Password</label>
                    <div class="field-inner">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                <div class="forgot-link"><a href="#" onclick="showScreen('forgot'); return false;">Forgot password?</a></div>
                <button type="submit" class="btn-primary"><i class="fas fa-arrow-right-to-bracket"></i> &nbsp;Sign In</button>
            </form>
            <p class="switch-text">Don't have an account? <a href="#" onclick="showScreen('signup'); return false;">Create one &rarr;</a></p>
        </div>

        <!-- ── SIGN UP ── -->
        <div class="form-screen <?= $action === 'register' ? 'active' : '' ?>" id="screen-signup">
            <h1>Create Account</h1>
            <p class="subtitle">Join QuizMaster Pro and start learning today.</p>

            <?php if ($error && $action === 'register'): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="regForm">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="role" id="roleInput" value="student">

                <!-- Role selector -->
                <div class="field">
                    <label>I am a...</label>
                    <div class="role-selector">
                        <div class="role-btn active" onclick="setRole('student', this)">
                            <i class="fas fa-graduation-cap"></i> Student
                        </div>
                        <div class="role-btn" onclick="setRole('teacher', this)">
                            <i class="fas fa-chalkboard-teacher"></i> Teacher
                        </div>
                        <div class="role-btn" onclick="setRole('admin', this)">
                            <i class="fas fa-shield-halved"></i> Admin
                        </div>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label>Username</label>
                        <div class="field-inner">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" placeholder="johndoe" required>
                        </div>
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <div class="field-inner">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" placeholder="john@mail.com" required>
                        </div>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label>Password</label>
                        <div class="field-inner">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="field">
                        <label>Security PIN (4-digit)</label>
                        <div class="field-inner">
                            <i class="fas fa-shield-alt"></i>
                            <input type="text" name="security_pin" placeholder="e.g. 1234" pattern="\d{4}" maxlength="4" required>
                        </div>
                    </div>
                </div>

                <div class="field" id="accessCodeField" style="display:none;">
                    <label>Authorization Code</label>
                    <div class="field-inner">
                        <i class="fas fa-key"></i>
                        <input type="text" name="access_code" placeholder="Provided by your admin" id="accessCodeInput">
                    </div>
                </div>

                <button type="submit" class="btn-primary"><i class="fas fa-user-plus"></i> &nbsp;Create Account</button>
            </form>
            <p class="switch-text">Already have an account? <a href="#" onclick="showScreen('login'); return false;">&larr; Sign In</a></p>
        </div>

        <!-- ── FORGOT PASSWORD ── -->
        <div class="form-screen" id="screen-forgot">
            <h1>Account Recovery</h1>
            <p class="subtitle">Enter your email and Security PIN to reset your password.</p>

            <?php if ($error && $action === 'reset_pass'): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="reset_pass">
                <div class="field">
                    <label>Email Address</label>
                    <div class="field-inner">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="reset_email" placeholder="Your registered email" required>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label>Security PIN</label>
                        <div class="field-inner">
                            <i class="fas fa-key"></i>
                            <input type="text" name="reset_pin" placeholder="1234" pattern="\d{4}" maxlength="4" required>
                        </div>
                    </div>
                    <div class="field">
                        <label>New Password</label>
                        <div class="field-inner">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="new_password" placeholder="••••••••" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-primary"><i class="fas fa-rotate-right"></i> &nbsp;Reset Password</button>
            </form>
            <p class="switch-text"><a href="#" onclick="showScreen('login'); return false;">&larr; Back to Sign In</a></p>
        </div>

    </div><!-- /forms-panel -->
</div><!-- /auth-wrapper -->

<script>
    const screens = { login: 'screen-login', signup: 'screen-signup', forgot: 'screen-forgot' };
    const decoTitles = {
        login: 'New Here?',
        signup: 'Welcome Back!',
        forgot: 'Need Help?'
    };
    const decoSubs = {
        login:  'Create a free account and join thousands of students acing their quizzes every day.',
        signup: 'Already have an account? Sign in to continue your learning journey.',
        forgot: 'If you remember your credentials, head back to the sign-in page.'
    };
    const decoBtnText = { login: 'Create Account', signup: 'Sign In', forgot: 'Sign In' };
    const decoBtnTarget = { login: 'signup', signup: 'login', forgot: 'login' };

    let currentScreen = '<?= ($action === 'register') ? 'signup' : 'login' ?>';

    function showScreen(name) {
        // hide all
        Object.values(screens).forEach(id => {
            const el = document.getElementById(id);
            el.classList.remove('active');
        });
        // show target
        setTimeout(() => {
            document.getElementById(screens[name]).classList.add('active');
        }, 20);

        // update deco panel
        document.getElementById('decoTitle').textContent = decoTitles[name];
        document.getElementById('decoSubtitle').textContent = decoSubs[name];
        document.getElementById('decoBtn').textContent = decoBtnText[name];
        document.getElementById('decoBtn').onclick = () => showScreen(decoBtnTarget[name]);

        // dots
        ['login', 'signup', 'forgot'].forEach(k => {
            document.getElementById('dot-' + k)?.classList.remove('active');
        });
        const dotEl = document.getElementById('dot-' + name);
        if (dotEl) dotEl.classList.add('active');

        currentScreen = name;
    }

    function setRole(role, btn) {
        document.getElementById('roleInput').value = role;
        document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const codeField = document.getElementById('accessCodeField');
        const codeInput = document.getElementById('accessCodeInput');
        if (role !== 'student') {
            codeField.style.display = 'block';
            codeInput.required = true;
        } else {
            codeField.style.display = 'none';
            codeInput.required = false;
        }
    }

    // Reopen the correct screen if backend redirected with errors
    <?php if ($action === 'register' && $error): ?>
        showScreen('signup');
    <?php elseif ($action === 'reset_pass' && $error): ?>
        showScreen('forgot');
    <?php endif; ?>
</script>
</body>
</html>
