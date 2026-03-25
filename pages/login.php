<?php
require_once __DIR__ . '/../includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $access_code = $_POST['access_code'] ?? '';

    if ($username && $password) {
        if ($action === 'register') {
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                $error = "User already exists. Choose another name.";
            } else {
                if ($role === 'teacher' && $access_code !== 'TEACHER2024') {
                    $error = "Invalid Teacher Code. Contact admin for authorization.";
                } elseif ($role === 'admin' && $access_code !== 'ADMIN2024') {
                     $error = "Invalid Admin Code. System protection enabled.";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $hash, $role]);
                    $success = "Account created! You can now login.";
                }
            }
        } elseif ($action === 'login') {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid credentials.";
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
    <title>QuizMaster - Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body.auth-page {
            background: linear-gradient(rgba(2, 6, 23, 0.8), rgba(2, 6, 23, 0.8)), url('../assets/img/auth_bg.png');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
        }

        .auth-container {
            position: relative;
            width: 1000px;
            max-width: 100%;
            height: 650px;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(30px);
            border-radius: 40px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.6);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-panel {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.7s cubic-bezier(0.645, 0.045, 0.355, 1);
            width: 50%;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .sign-in-panel { left: 0; z-index: 2; opacity: 1; }
        .sign-up-panel { left: 0; z-index: 1; opacity: 0; }

        .right-active .sign-in-panel { transform: translateX(100%); opacity: 0; }
        .right-active .sign-up-panel { transform: translateX(100%); opacity: 1; z-index: 5; }

        .overlay-mask {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.7s cubic-bezier(0.645, 0.045, 0.355, 1);
            z-index: 100;
        }

        .right-active .overlay-mask { transform: translateX(-100%); }

        .overlay-content {
            background: linear-gradient(135deg, #1e1b4b, #312e81);
            color: #FFFFFF;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.7s cubic-bezier(0.645, 0.045, 0.355, 1);
        }

        .right-active .overlay-content { transform: translateX(50%); }

        .panel-desc {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 40px;
            text-align: center;
            top: 0;
            height: 100%;
            width: 50%;
            transition: transform 0.7s cubic-bezier(0.645, 0.045, 0.355, 1);
        }

        .panel-left { transform: translateX(-20%); }
        .right-active .panel-left { transform: translateX(0); }
        .panel-right { right: 0; transform: translateX(0); }
        .right-active .panel-right { transform: translateX(20%); }

        .auth-image {
            width: 180px;
            height: 180px;
            margin-bottom: 20px;
            object-fit: contain;
            filter: drop-shadow(0 0 20px rgba(139, 92, 246, 0.5));
            animation: floating 3s infinite ease-in-out;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .input-group { margin-bottom: 1.2rem; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 0.8rem; color: #94a3b8; }
        .alert { padding: 12px; border-radius: 12px; font-size: 0.85rem; margin-bottom: 20px; text-align: center; }
        .alert-error { background: rgba(244, 63, 94, 0.2); border: 1px solid #f43f5e; color: #f43f5e; }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; }

        .ghost-btn {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 12px;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .ghost-btn:hover { background: white; color: var(--primary); }

        @media (max-width: 768px) {
            .auth-container { height: auto; min-height: 800px; border-radius: 30px; }
            .form-panel, .overlay-mask { width: 100% !important; height: 50% !important; position: relative !important; left: 0 !important; transform: none !important; }
            .overlay-mask { display: none; }
            .sign-up-panel, .sign-in-panel { opacity: 1 !important; transform: none !important; }
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container" id="authPortal">
        <!-- Sign Up (Hidden by default) -->
        <div class="form-panel sign-up-panel">
            <form action="" method="POST">
                <input type="hidden" name="action" value="register">
                <h1 style="color:white; margin-bottom: 1rem;">Join the Ranks</h1>
                <p style="color: var(--text-muted); margin-bottom: 2rem;">Start your knowledge journey today.</p>

                <?php if ($error && $_POST['action'] === 'register'): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Choose a username" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="input-group">
                    <label>Role</label>
                    <select name="role" onchange="document.getElementById('teacherGrp').style.display = (this.value!=='student'?'block':'none')">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">System Admin</option>
                    </select>
                </div>
                <div class="input-group" id="teacherGrp" style="display:none;">
                    <label>Secret Authorization Code</label>
                    <input type="text" name="access_code" placeholder="TEACHER2024 or ADMIN2024">
                </div>
                <button type="submit" class="btn">Create Account</button>
                <div style="text-align: center; margin-top: 20px; display: block;" id="mobileSwitchToLogin">
                    <span style="color:var(--text-muted);">Already a member?</span> <a href="#" onclick="togglePanel(false)">Sign In</a>
                </div>
            </form>
        </div>

        <!-- Sign In -->
        <div class="form-panel sign-in-panel">
            <form action="" method="POST">
                <input type="hidden" name="action" value="login">
                <h1 style="color:white; margin-bottom: 1rem;">Welcome Back</h1>
                <p style="color: var(--text-muted); margin-bottom: 2rem;">Login to continue your assessments.</p>

                <?php if ($error && $_POST['action'] === 'login'): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Your username" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn">Login & Start</button>
                <a href="#" style="color: var(--text-muted); font-size: 0.8rem; text-align: center; display: block; margin-top: 15px;">Forgot Credentials?</a>
            </form>
        </div>

        <!-- Sliding Overlay -->
        <div class="overlay-mask">
            <div class="overlay-content">
                <div class="panel-desc panel-left">
                    <img src="../assets/img/login_brain.png" class="auth-image" alt="Knowledge">
                    <h2>Got an account?</h2>
                    <p style="margin: 1.5rem 0;">Log in today to check your latest assessment scores and compete in the leaderboard.</p>
                    <button class="ghost-btn" onclick="togglePanel(false)">Sign In</button>
                </div>
                <div class="panel-desc panel-right">
                    <img src="../assets/img/signup_journey.png" class="auth-image" alt="Success">
                    <h2>New Explorer?</h2>
                    <p style="margin: 1.5rem 0;">Create a student or teacher account to start managing and taking premium CS quizzes.</p>
                    <button class="ghost-btn" onclick="togglePanel(true)">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const portal = document.getElementById('authPortal');
        
        function togglePanel(isRightActive) {
            if (isRightActive) {
                portal.classList.add('right-active');
            } else {
                portal.classList.remove('right-active');
            }
        }

        // Touch support: Tap overlay to slide if needed, or swipe
        let touchstartX = 0;
        let touchendX = 0;

        portal.addEventListener('touchstart', e => {
            touchstartX = e.changedTouches[0].screenX;
        });

        portal.addEventListener('touchend', e => {
            touchendX = e.changedTouches[0].screenX;
            handleGesture();
        });

        function handleGesture() {
            if (touchendX < touchstartX - 50) {
                // Swiped Left
                togglePanel(true);
            }
            if (touchendX > touchstartX + 50) {
                // Swiped Right
                togglePanel(false);
            }
        }
    </script>
</body>
</html>
