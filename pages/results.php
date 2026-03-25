<?php
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_result'])) {
    header("Location: dashboard.php");
    exit();
}

$result = $_SESSION['last_result'];
$score_percent = ($result['total'] > 0) ? round(($result['score'] / $result['total']) * 100) : 0;
$status = $score_percent >= 60 ? 'Passed' : 'Needs Improvement';
$status_color = $score_percent >= 60 ? 'var(--success)' : 'var(--error)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results | QuizMaster</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .result-card { text-align: center; padding: 3rem; background: var(--card-bg); border-radius: 32px; border: 1px solid var(--glass-border); margin-bottom: 2rem; }
        .eval-item { display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid var(--glass-border); }
        .eval-status { padding: 4px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .status-Correct { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .status-Incorrect { background: rgba(244, 63, 94, 0.1); color: var(--error); }
    </style>
</head>
<body class="student-dashboard">
    <header class="nav-bar">
        <h2><i class="fas fa-poll"></i> Assessment Report</h2>
        <a href="dashboard.php" class="btn" style="width: auto; padding: 10px 24px;">Return Home</a>
    </header>

    <main class="content-wrapper" style="max-width: 800px;">
        <div class="result-card">
            <h1 style="color: <?= $status_color ?>;"><?= $status ?>!</h1>
            <div style="font-size: 4rem; font-weight: 800; color: var(--primary); margin: 1rem 0;"><?= $result['score'] ?> / <?= $result['total'] ?></div>
            <p style="color: var(--text-muted);">You achieved <strong><?= $score_percent ?>%</strong> in <strong><?= htmlspecialchars($result['quiz_title']) ?></strong></p>
        </div>

        <h3 style="margin-bottom: 1.5rem;">Detailed Evaluation</h3>
        <div class="glass-card" style="padding: 0; overflow: hidden; max-width: 100%;">
            <?php foreach ($result['evaluations'] as $eval): ?>
                <div class="eval-item">
                    <div style="flex: 1;">
                        <div style="font-weight: 600;"><?= htmlspecialchars($eval['question']) ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Points earned: <?= $eval['points'] ?></div>
                    </div>
                    <span class="eval-status status-<?= $eval['status'] ?>"><?= $eval['status'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 3rem;">
            <button onclick="window.print()" class="btn btn-secondary" style="width: auto; padding: 14px 40px;"><i class="fas fa-print"></i> Print Scorecard</button>
        </div>
    </main>
</body>
</html>
<?php unset($_SESSION['last_result']); ?>
