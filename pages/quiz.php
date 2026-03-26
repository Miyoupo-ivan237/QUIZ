<?php
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$quiz_id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: dashboard.php");
    exit();
}

// Fetch all questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

// Fetch all options for these questions
$all_options = [];
if (!empty($questions)) {
    $q_ids = array_column($questions, 'id');
    $placeholders = implode(',', array_fill(0, count($q_ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM options WHERE question_id IN ($placeholders)");
    $stmt->execute($q_ids);
    $opts = $stmt->fetchAll();
    foreach ($opts as $opt) {
        $all_options[$opt['question_id']][] = $opt;
    }
}

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_answers = $_POST['answers'] ?? [];
    $score = 0;
    $total_possible = 0;
    $evaluations = [];

    foreach ($questions as $q) {
        $q_id = $q['id'];
        $type = $q['question_type'];
        $points = intval($q['points'] ?? 10);
        $total_possible += $points;
        $is_correct = false;
        $provided = $user_answers[$q_id] ?? '';

        if ($type === 'multiple_choice' || $type === 'true_false') {
            $selected_opt_id = intval($provided);
            $opts = $all_options[$q_id] ?? [];
            foreach ($opts as $opt) {
                if ($opt['is_correct'] && $opt['id'] == $selected_opt_id) {
                    $is_correct = true;
                    break;
                }
            }
        } elseif ($type === 'short_answer') {
            $correct = trim(strtolower($q['correct_answer']));
            $input = trim(strtolower($provided));
            if ($correct === $input) {
                $is_correct = true;
            }
        }

        if ($is_correct) {
            $score += $points;
        }
        
        $evaluations[] = [
            'question' => $q['question_text'],
            'status' => $is_correct ? 'Correct' : 'Incorrect',
            'points' => $is_correct ? $points : 0,
            'explanation' => $q['explanation'],
            'correct_answer' => $q['correct_answer'] ?? '' // For MCQ we might want text, but let's stick to this for now
        ];
    }

    // Save Attempt
    $stmt = $pdo->prepare("INSERT INTO attempts (user_id, quiz_id, score, total_points) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $quiz_id, $score, $total_possible]);
    $attempt_id = $pdo->lastInsertId();

    $_SESSION['last_result'] = [
        'quiz_title' => $quiz['title'],
        'score' => $score,
        'total' => $total_possible,
        'evaluations' => $evaluations
    ];
    header("Location: results.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($quiz['title']) ?> | Quiz</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container-quiz { max-width: 900px; margin: 2rem auto; padding: 20px; }
        .q-card { background: var(--card-bg); border: 1px solid var(--glass-border); padding: 30px; border-radius: 24px; margin-bottom: 2rem; display: none; }
        .q-card.active { display: block; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .opt-box { border: 1px solid var(--glass-border); padding: 15px 20px; border-radius: 12px; margin-bottom: 15px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.03); }
        .opt-box:hover { border-color: var(--primary); background: rgba(139, 92, 246, 0.1); }
        input[type="radio"] { display: none; }
        input[type="radio"]:checked + .opt-box { border-color: var(--primary); background: var(--primary-glow); }
        
        .timer-bar { position: sticky; top: 0; background: var(--bg-dark); z-index: 1000; padding: 15px 0; border-bottom: 1px solid var(--glass-border); }
        .progress-line { height: 4px; background: var(--primary); width: 0%; transition: 0.3s; }
        
        .short-answer-input { background: rgba(0,0,0,0.2); border: 1px solid var(--glass-border); color: white; padding: 15px; border-radius: 12px; width: 100%; font-size: 1.1rem; }
    </style>
</head>
<body class="student-dashboard">
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>
    <div class="timer-bar">
        <div class="content-wrapper" style="padding: 0 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; color: var(--primary);"><?= htmlspecialchars($quiz['title']) ?></h3>
            <div id="quiz-timer" style="font-size: 1.5rem; font-weight: bold; color: var(--secondary);"><i class="fas fa-clock"></i> <span>--:--</span></div>
        </div>
        <div class="progress-line" id="progressLine"></div>
    </div>

    <div class="container-quiz">
        <div style="text-align: center; margin-bottom: 2rem;">
            <img src="https://cdn-icons-png.flaticon.com/512/3242/3242257.png" style="width: 100px; height: 100px; opacity: 0.9;" alt="Quiz Header">
        </div>
        <form id="quizForm" method="POST">
            <?php foreach ($questions as $index => $q): ?>
                <div class="q-card <?= $index === 0 ? 'active' : '' ?>" id="q-<?= $index ?>">
                    <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 10px;">Question <?= $index + 1 ?> of <?= count($questions) ?></div>
                    <h2 style="margin-bottom: 1.5rem; background: none; -webkit-text-fill-color: var(--text-main);"><?= htmlspecialchars($q['question_text']) ?></h2>
                    
                    <div class="options-container">
                        <?php if ($q['question_type'] === 'multiple_choice' || $q['question_type'] === 'true_false'): ?>
                            <?php 
                            $opts = $all_options[$q['id']] ?? [];
                            foreach ($opts as $opt): 
                            ?>
                                <label>
                                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $opt['id'] ?>" 
                                           onchange="provideFeedback(<?= $index ?>, <?= $opt['is_correct'] ? 'true' : 'false' ?>, '<?= addslashes($q['explanation'] ?? '') ?>')" required>
                                    <div class="opt-box" id="opt-<?= $opt['id'] ?>">
                                        <i class="far fa-circle"></i> <?= htmlspecialchars($opt['option_text']) ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php elseif ($q['question_type'] === 'short_answer'): ?>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="sa-<?= $q['id'] ?>" name="answers[<?= $q['id'] ?>]" class="short-answer-input" placeholder="Type your answer here..." required>
                                <button type="button" class="btn" style="width: auto;" onclick="checkShortAnswer(<?= $index ?>, <?= $q['id'] ?>, '<?= addslashes($q['correct_answer']) ?>', '<?= addslashes($q['explanation'] ?? '') ?>')">Check</button>
                            </div>
                        <?php endif; ?>
                        
                        <div id="feedback-<?= $index ?>" class="feedback-msg" style="display: none; margin-top: 20px; padding: 15px; border-radius: 12px; font-weight: 600;"></div>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-top: 2.5rem;">
                        <?php if ($index > 0): ?>
                            <button type="button" class="btn btn-secondary" style="width: auto; padding: 12px 30px;" onclick="goTo(<?= $index - 1 ?>)">Previous</button>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>

                        <?php if ($index < count($questions) - 1): ?>
                            <button type="button" id="next-btn-<?= $index ?>" class="btn" style="width: auto; padding: 12px 30px;" onclick="goTo(<?= $index + 1 ?>)">Next <i class="fas fa-chevron-right"></i></button>
                        <?php else: ?>
                            <button type="submit" id="submit-btn" class="btn" style="width: auto; padding: 12px 40px; background: linear-gradient(135deg, #10b981, #059669);">Submit Quiz <i class="fas fa-check-double"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </form>
    </div>

    <script>
        let currentIdx = 0;
        const total = <?= count($questions) ?>;
        
        function provideFeedback(idx, isCorrect, explanation) {
            const feedbackEl = document.getElementById('feedback-' + idx);
            feedbackEl.style.display = 'block';
            feedbackEl.style.background = isCorrect ? 'rgba(34, 197, 94, 0.15)' : 'rgba(239, 68, 68, 0.15)';
            feedbackEl.style.color = isCorrect ? '#86efac' : '#fca5a5';
            feedbackEl.innerHTML = `<i class="fas ${isCorrect ? 'fa-check-circle' : 'fa-times-circle'}"></i> ` + 
                                   (isCorrect ? 'Correct! ' : 'Incorrect. ') + explanation;
            
            // Auto advance
            setTimeout(() => {
                if (idx < total - 1) {
                    goTo(idx + 1);
                }
            }, 2500);
        }

        function checkShortAnswer(idx, qId, correct, explanation) {
            const inputEl = document.getElementById('sa-' + qId);
            const isCorrect = inputEl.value.trim().toLowerCase() === correct.toLowerCase();
            provideFeedback(idx, isCorrect, explanation);
        }

        function goTo(n) {
            document.getElementById('q-' + currentIdx).classList.remove('active');
            document.getElementById('q-' + n).classList.add('active');
            currentIdx = n;
            updateProgress();
        }

        function updateProgress() {
            document.getElementById('progressLine').style.width = ((currentIdx + 1) / total * 100) + '%';
        }

        updateProgress();

        // Timer Logic
        let timeLeft = <?= ($quiz['time_limit'] > 0) ? ($quiz['time_limit'] * 60) : 0 ?>;
        const timerDisplay = document.querySelector('#quiz-timer span');

        if (timeLeft > 0) {
            const timer = setInterval(() => {
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;
                timerDisplay.innerText = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    alert("Time is up! Submitting automatically.");
                    document.getElementById('quizForm').submit();
                }
                timeLeft--;
            }, 1000);
        } else {
            timerDisplay.innerText = "No Limit";
        }
    </script>
</body>
</html>
