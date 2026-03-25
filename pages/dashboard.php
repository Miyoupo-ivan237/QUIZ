<?php
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Branch filtering (for CS theme)
$selected_branch = $_GET['branch'] ?? 'All';

// Admin Functionalities Logic
if ($role === 'admin') {
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $quiz_count = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
    $attempt_count = $pdo->query("SELECT COUNT(*) FROM attempts")->fetchColumn();
    
    $all_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll();
}

// Fetch Quizzes depending on role and branch
if ($role === 'teacher') {
    $sql = "SELECT q.* FROM quizzes q WHERE q.author_id = ?";
    $params = [$user_id];
} elseif ($role === 'admin') {
    $sql = "SELECT q.*, u.username as teacher_name FROM quizzes q JOIN users u ON q.author_id = u.id";
    $params = [];
} else {
    $sql = "SELECT q.*, u.username as teacher_name FROM quizzes q JOIN users u ON q.author_id = u.id";
    $params = [];
    if ($selected_branch !== 'All') {
        $sql .= " WHERE q.branch = ?";
        $params[] = $selected_branch;
    }
}
$stmt = $pdo->prepare($sql . " ORDER BY q.created_at DESC");
$stmt->execute($params);
$quizzes = $stmt->fetchAll();

// Student Stats
$student_stats = [];
if ($role === 'student') {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, AVG(score*100.0/total_points) as avg_score FROM attempts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student_stats = $stmt->fetch();
}

// Leaderboard
$leaderboard = $pdo->query("SELECT u.username, SUM(a.score) as total_score 
                            FROM attempts a 
                            JOIN users u ON a.user_id = u.id 
                            GROUP BY u.id 
                            ORDER BY total_score DESC LIMIT 5")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | QuizMaster</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?= $role ?>-dashboard">
    <header class="nav-bar">
        <h2 style="color: var(--primary); margin: 0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-brain"></i> QuizMaster <small style="font-size: 0.6rem; background: var(--primary-glow); padding: 2px 8px; border-radius: 10px;"><?= strtoupper($role) ?></small>
        </h2>
        
        <div style="display: flex; gap: 1.5rem; align-items: center;">
            <button onclick="toggleDarkMode()" class="btn" style="width: 40px; height: 40px; border-radius: 50%; padding: 0;">
                <i class="fas fa-moon"></i>
            </button>
            <span style="color: var(--text-main); font-size: 0.9rem;">
                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($username) ?>
            </span>
            <a href="logout.php" class="btn btn-secondary" style="padding: 10px 20px; font-size: 0.8rem; width: auto;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <main class="content-wrapper">
        <div style="margin-bottom: 2rem;">
            <h1>Welcome, <?= htmlspecialchars($username) ?>!</h1>
            <p style="color: var(--text-muted);">Manage your quizzes and track your performance.</p>
        </div>

        <?php if ($role === 'admin'): ?>
            <!-- Admin Global Stats -->
            <div class="stat-row">
                <div class="stat-card">
                    <span style="color:var(--text-muted);">Total Users</span>
                    <div class="stat-val"><?= $user_count ?></div>
                </div>
                <div class="stat-card">
                    <span style="color:var(--text-muted);">Total Quizzes</span>
                    <div class="stat-val"><?= $quiz_count ?></div>
                </div>
                <div class="stat-card">
                    <span style="color:var(--text-muted);">Total Attempts</span>
                    <div class="stat-val"><?= $attempt_count ?></div>
                </div>
            </div>

            <!-- Admin Management Tools -->
            <div class="glass-card" style="margin-bottom: 3rem; max-width: 100%;">
                <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-users-cog"></i> User Management</h3>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; text-align: left; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <th style="padding: 10px;">User ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_users as $u): ?>
                                <tr style="border-bottom: 1px solid var(--glass-border);">
                                    <td style="padding: 15px;"><?= $u['id'] ?></td>
                                    <td><?= htmlspecialchars($u['username']) ?></td>
                                    <td><span class="badge" style="background: var(--primary-glow);"><?= strtoupper($u['role']) ?></span></td>
                                    <td style="color: var(--text-muted); font-size: 0.8rem;"><?= $u['created_at'] ?></td>
                                    <td>
                                        <button class="btn btn-secondary" style="width: auto; padding: 5px 12px; font-size: 0.7rem;" onclick="alert('Demo: User deletion restricted.')">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($role === 'student'): ?>
            <div class="stat-row">
                <div class="stat-card">
                    <span style="color:var(--text-muted);">Quizzes Completed</span>
                    <div class="stat-val"><?= $student_stats['total'] ?? 0 ?></div>
                    <a href="history.php" style="color: var(--primary); font-size: 0.8rem; text-decoration: none; margin-top: 10px; display: inline-block;">View Detailed History <i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="stat-card">
                    <span style="color:var(--text-muted);">Average Score</span>
                    <div class="stat-val"><?= round($student_stats['avg_score'] ?? 0) ?>%</div>
                </div>
                <div class="stat-card" style="flex: 2;">
                    <span style="color:var(--text-muted);"><i class="fas fa-trophy" style="color: gold;"></i> Global Leaderboard <small style="float: right; font-size: 0.7rem; color: var(--text-muted);">Top 5</small></span>
                    <div style="margin-top: 15px; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; border: 1px solid var(--glass-border);">
                        <?php foreach($leaderboard as $index => $hero): ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 5px;">
                                <span>#<?= $index+1 ?> <?= htmlspecialchars($hero['username']) ?></span>
                                <span style="font-weight: bold; color: var(--primary);"><?= $hero['total_score'] ?> pts</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Branch Navigator -->
            <div style="display: flex; gap: 10px; margin-bottom: 2rem; overflow-x: auto; padding-bottom: 10px;">
                <a href="?branch=All" class="badge" style="background: <?= $selected_branch == 'All' ? 'var(--primary)' : 'var(--card-bg)' ?>; cursor: pointer;">All</a>
                <a href="?branch=Software Engineering" class="badge" style="background: <?= $selected_branch == 'Software Engineering' ? 'var(--primary)' : 'var(--card-bg)' ?>; cursor: pointer;">Software Engineering</a>
                <a href="?branch=Cyber Security" class="badge" style="background: <?= $selected_branch == 'Cyber Security' ? 'var(--primary)' : 'var(--card-bg)' ?>; cursor: pointer;">Cyber Security</a>
                <a href="?branch=Artificial Intelligence" class="badge" style="background: <?= $selected_branch == 'Artificial Intelligence' ? 'var(--primary)' : 'var(--card-bg)' ?>; cursor: pointer;">AI</a>
                <a href="?branch=Networking" class="badge" style="background: <?= $selected_branch == 'Networking' ? 'var(--primary)' : 'var(--card-bg)' ?>; cursor: pointer;">Networking</a>
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3><?= $role === 'student' ? 'Available Quizzes' : 'Manage Content' ?></h3>
            <?php if ($role === 'teacher' || $role === 'admin'): ?>
                <a href="create.php" class="btn" style="width: auto; padding: 12px 24px;">
                    <i class="fas fa-plus"></i> Create Quiz
                </a>
            <?php endif; ?>
        </div>

        <div class="grid-container">
            <?php if (empty($quizzes)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                    <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--text-muted);"></i>
                    <p>No quizzes available in this category.</p>
                </div>
            <?php else: ?>
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="quiz-card" onclick="location.href='<?= ($role === 'student') ? "quiz.php?id=".$quiz['id'] : "editor.php?id=".$quiz['id'] ?>'">
                        <div class="badge"><?= htmlspecialchars($quiz['branch']) ?></div>
                        <h4><?= htmlspecialchars($quiz['title']) ?></h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($quiz['course']) ?></p>
                        <p style="margin: 1rem 0; font-size: 0.9rem;"><?= htmlspecialchars(substr($quiz['description'], 0, 80)) ?>...</p>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.75rem;"><i class="fas fa-clock"></i> <?= $quiz['time_limit'] ?>m</span>
                            <span style="font-size: 0.75rem;"><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($quiz['teacher_name'] ?? 'System') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleDarkMode() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }

        // Initialize theme
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.body.setAttribute('data-theme', savedTheme);
    </script>
</body>
</html>
