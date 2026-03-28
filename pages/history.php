<?php
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$me_id = $_SESSION['user_id'];
$me_role = $_SESSION['role'];

// Target User Logic
$target_user_id = intval($_GET['user_id'] ?? $me_id);

// Permission Check: Students can only view themselves
if ($me_role === 'student' && $target_user_id !== $me_id) {
    header("Location: history.php");
    exit();
}

// Fetch target user info
$stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
$stmt->execute([$target_user_id]);
$target_user = $stmt->fetch();

if (!$target_user) {
    header("Location: dashboard.php");
    exit();
}

$username = $target_user['username'];
$role = $target_user['role'];
$user_id = $target_user_id;

// Fetch all attempts for this user
$stmt = $pdo->prepare("SELECT a.*, q.title, q.branch, q.course 
                        FROM attempts a 
                        JOIN quizzes q ON a.quiz_id = q.id 
                        WHERE a.user_id = ? 
                        ORDER BY a.completed_at DESC");
$stmt->execute([$user_id]);
$attempts = $stmt->fetchAll();

// Performance Per Quiz (Grouped)
$stmt = $pdo->prepare("SELECT q.title, 
                            AVG(CASE WHEN a.total_points > 0 THEN a.score*100.0/a.total_points ELSE 0 END) as avg_score, 
                            MAX(CASE WHEN a.total_points > 0 THEN a.score*100.0/a.total_points ELSE 0 END) as max_score, 
                            COUNT(*) as attempt_count
                        FROM attempts a 
                        JOIN quizzes q ON a.quiz_id = q.id 
                        WHERE a.user_id = ? 
                        GROUP BY q.id");
$stmt->execute([$user_id]);
$performance = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Score History | QuizMaster</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table-card { background: var(--card-bg); border-radius: 20px; border: 1px solid var(--glass-border); overflow: hidden; max-width: 100%; border-radius: 24px; padding: 10px; margin-bottom: 3rem; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 20px; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; border-bottom: 1px solid var(--glass-border); }
        td { padding: 20px; border-bottom: 1px solid var(--glass-border); }
        tr:last-child td { border-bottom: none; }
        .perf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .perf-card { background: var(--card-bg); border: 1px solid var(--glass-border); padding: 24px; border-radius: 20px; }
    </style>
</head>
<body class="student-dashboard">
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>
    <header class="nav-bar">
        <h2><i class="fas fa-history"></i> Performance: <?= htmlspecialchars($username) ?></h2>
        <div style="display: flex; gap: 10px;">
            <?php if ($me_role !== 'student'): ?>
                <a href="<?= $me_role === 'admin' ? 'dashboard.php' : 'analytics.php' ?>" class="btn btn-secondary" style="width: auto; padding: 10px 24px;">Back</a>
            <?php endif; ?>
            <a href="dashboard.php" class="btn" style="width: auto; padding: 10px 24px;">Home</a>
        </div>
    </header>

    <main class="content-wrapper">
        <h1 style="margin-bottom: 2rem;">Performance Trends</h1>

        <h3>Performance per Quiz</h3>
        <div class="perf-grid">
            <?php foreach ($performance as $p): ?>
                <div class="perf-card">
                    <h4 style="margin-bottom: 10px;"><?= htmlspecialchars($p['title']) ?></h4>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">Attempts: <?= $p['attempt_count'] ?></div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                        <div>
                            <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted);">Avg. Score</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);"><?= round($p['avg_score']) ?>%</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted);">Max. Score</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);"><?= round($p['max_score']) ?>%</div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 style="margin: 3rem 0 1.5rem 0;">All Attempts</h3>
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Quiz & Course</th>
                        <th>Branch</th>
                        <th>Score</th>
                        <th>Accuracy</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attempts)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 50px; color: var(--text-muted);">No attempts recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach($attempts as $att): 
                            $pct = ($att['total_points'] > 0) ? round(($att['score'] / $att['total_points']) * 100) : 0;
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($att['title']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($att['course']) ?></div>
                                </td>
                                <td><span class="badge"><?= htmlspecialchars($att['branch']) ?></span></td>
                                <td><strong style="color: <?= $pct >= 60 ? 'var(--success)' : 'var(--error)' ?>;"><?= $att['score'] ?>/<?= $att['total_points'] ?></strong></td>
                                <td><?= $pct ?>%</td>
                                <td style="color: var(--text-muted); font-size: 0.8rem;"><?= date('M j, Y H:i', strtotime($att['completed_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
