<?php
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: dashboard.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Total quizzes
$stmt = $pdo->prepare("SELECT COUNT(*) FROM quizzes WHERE author_id = ?");
$stmt->execute([$teacher_id]);
$total_quizzes = $stmt->fetchColumn();

// Total participants
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM attempts a JOIN quizzes q ON a.quiz_id = q.id WHERE q.author_id = ?");
$stmt->execute([$teacher_id]);
$total_participants = $stmt->fetchColumn();

// Avg Score
$stmt = $pdo->prepare("SELECT AVG(CAST(score AS FLOAT) / total_points * 100) FROM attempts a JOIN quizzes q ON a.quiz_id = q.id WHERE q.author_id = ?");
$stmt->execute([$teacher_id]);
$avg_score = round($stmt->fetchColumn());

// Recent Attempts
$stmt = $pdo->prepare("
    SELECT a.*, q.title, u.username 
    FROM attempts a 
    JOIN quizzes q ON a.quiz_id = q.id 
    JOIN users u ON a.user_id = u.id 
    WHERE q.author_id = ? 
    ORDER BY a.completed_at DESC 
    LIMIT 20
");
$stmt->execute([$teacher_id]);
$recent_attempts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Quiz Master</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>
    <header class="nav-bar">
        <h2><i class="fas fa-chart-pie"></i> Quiz Analytics</h2>
        <a href="dashboard.php" class="btn" style="width: auto; padding: 10px 24px; font-size: 0.8rem;">Back to Dashboard</a>
    </header>

    <main class="content-wrapper">
        <div class="stat-row">
            <div class="stat-card">
                <span style="color: var(--text-muted); font-size: 0.8rem;">Managed Quizzes</span>
                <div class="stat-val"><?= $total_quizzes ?></div>
            </div>
            <div class="stat-card">
                <span style="color: var(--text-muted); font-size: 0.8rem;">Unique Participants</span>
                <div class="stat-val"><?= $total_participants ?></div>
            </div>
            <div class="stat-card">
                <span style="color: var(--text-muted); font-size: 0.8rem;">Global Average Score</span>
                <div class="stat-val"><?= $avg_score ?>%</div>
            </div>
        </div>

        <h1>Performance Trends</h1>
        <div class="glass-card" style="max-width: 100%; border-radius: 20px; padding: 0; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: rgba(255,255,255,0.05);">
                    <tr>
                        <th style="padding: 20px;">Student</th>
                        <th>Quiz Name</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_attempts)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">No attempts yet.</td></tr>
                    <?php else: ?>
                        <?php foreach($recent_attempts as $att): 
                            $pct = round(($att['score'] / $att['total_points']) * 100);
                        ?>
                            <tr>
                                <td style="padding: 20px;">
                                    <a href="history.php?user_id=<?= $att['user_id'] ?>" style="color: inherit; text-decoration: none;">
                                        <strong><i class="fas fa-external-link-alt" style="font-size: 0.7rem; color: var(--primary);"></i> <?= htmlspecialchars($att['username']) ?></strong>
                                    </a>
                                </td>
                                <td style="color: var(--text-muted);"><?= htmlspecialchars($att['title']) ?></td>
                                <td>
                                    <span style="font-weight: 700; color: <?= $pct >= 60 ? 'var(--success)' : 'var(--error)' ?>;">
                                        <?= $att['score'] ?>/<?= $att['total_points'] ?>
                                    </span> (<?= $pct ?>%)
                                </td>
                                <td>
                                    <span class="badge" style="background: <?= $pct >= 60 ? 'var(--success)' : 'var(--error)' ?>; margin: 0;">
                                        <?= $pct >= 60 ? 'Passed' : 'Failed' ?>
                                    </span>
                                </td>
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
