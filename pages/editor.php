<?php
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    header("Location: dashboard.php");
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

// Handle Add Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_question') {
        $text = trim($_POST['question_text'] ?? '');
        $type = $_POST['question_type'] ?? 'multiple_choice';
        $pts = intval($_POST['points'] ?? 10);
        $correct_answer = trim($_POST['correct_answer'] ?? '');
        $options = $_POST['options'] ?? [];
        $correct_index = intval($_POST['correct_option'] ?? 0);

        if ($text) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type, points, correct_answer) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$quiz_id, $text, $type, $pts, $correct_answer]);
            $q_id = $pdo->lastInsertId();

            if ($type === 'multiple_choice' || $type === 'true_false') {
                foreach ($options as $i => $opt_text) {
                    if (trim($opt_text)) {
                        $is_correct = ($i == $correct_index) ? 1 : 0;
                        $stmt = $pdo->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                        $stmt->execute([$q_id, $opt_text, $is_correct]);
                    }
                }
            }
            $pdo->commit();
        }
    } elseif ($_POST['action'] === 'delete_question') {
        $q_id = intval($_POST['question_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
        $stmt->execute([$q_id, $quiz_id]);
    }
}

// Fetch all questions for this quiz
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor | <?= htmlspecialchars($quiz['title']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .q-row { background: var(--card-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 20px; position: relative; }
        .aside-editor { position: sticky; top: 120px; }
    </style>
</head>
<body class="teacher-dashboard">
    <header class="nav-bar">
        <h2><i class="fas fa-magic"></i> <?= htmlspecialchars($quiz['title']) ?></h2>
        <a href="dashboard.php" class="btn" style="width: auto; padding: 10px 24px;">Publish & Finish</a>
    </header>

    <main class="content-wrapper" style="display: grid; grid-template-columns: 1fr 400px; gap: 40px; align-items: start;">
        <section>
            <h1>Quiz Questions (<?= count($questions) ?> / <?= $quiz['total_questions'] ?>)</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Manage content for this assessment.</p>

            <?php foreach ($questions as $i => $q): ?>
                <div class="q-row">
                    <form method="POST" style="position: absolute; top: 20px; right: 20px;">
                        <input type="hidden" name="action" value="delete_question">
                        <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                        <button type="submit" style="background: none; border: none; color: var(--error); cursor: pointer;" onclick="return confirm('Delete this question?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    <div style="font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px;">
                        <span class="badge" style="margin: 0;"><?= strtoupper($q['question_type']) ?></span>
                        Q<?= $i+1 ?>: <?= htmlspecialchars($q['question_text']) ?>
                    </div>
                    
                    <?php if (in_array($q['question_type'], ['multiple_choice', 'true_false'])): ?>
                        <ul style="list-style: none;">
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM options WHERE question_id = ?");
                            $stmt->execute([$q['id']]);
                            foreach ($stmt->fetchAll() as $opt):
                            ?>
                                <li style="padding: 10px; background: <?= $opt['is_correct'] ? 'rgba(16, 185, 129, 0.1)' : 'rgba(255,255,255,0.02)' ?>; border-radius: 8px; margin-bottom: 5px;">
                                    <i class="fas <?= $opt['is_correct'] ? 'fa-check-circle' : 'fa-circle' ?>" style="color: <?= $opt['is_correct'] ? 'var(--success)' : 'var(--text-muted)' ?>; margin-right: 10px;"></i>
                                    <?= htmlspecialchars($opt['option_text']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div style="padding: 10px; background: rgba(56, 189, 248, 0.1); border-radius: 8px; color: var(--accent);">
                            <i class="fas fa-key"></i> Correct Answer: <strong><?= htmlspecialchars($q['correct_answer']) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <aside class="aside-editor">
            <div class="glass-card">
                <h3><i class="fas fa-plus"></i> New Question</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_question">
                    <div class="input-group">
                        <label>Question Type</label>
                        <select name="question_type" id="qType" onchange="toggleInputs(this.value)">
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True / False</option>
                            <option value="short_answer">Short Answer</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Question Text</label>
                        <textarea name="question_text" rows="2" placeholder="Enter your question..." required></textarea>
                    </div>

                    <div id="optionsGroup">
                        <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 10px;">Options (Select correct one)</label>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php for($k=0;$k<4;$k++): ?>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="radio" name="correct_option" value="<?= $k ?>" <?= $k==0 ? 'checked':'' ?> style="width: auto;">
                                    <input type="text" name="options[]" placeholder="Option <?= chr(65+$k) ?>" <?= $k<2 ? 'required' : '' ?>>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div id="shortAnswerGroup" style="display: none;">
                        <div class="input-group">
                            <label>Correct Answer (Exact match)</label>
                            <input type="text" name="correct_answer" placeholder="e.g. 1984">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Points</label>
                        <input type="number" name="points" value="10" min="1">
                    </div>

                    <button type="submit" class="btn">Add Question</button>
                </form>
            </div>
        </aside>
    </main>

    <script>
        function toggleInputs(type) {
            const opts = document.getElementById('optionsGroup');
            const short = document.getElementById('shortAnswerGroup');
            
            if (type === 'multiple_choice' || type === 'true_false') {
                opts.style.display = 'block';
                short.style.display = 'none';
                
                // If T/F, only show 2 options
                const inputs = opts.querySelectorAll('input[type="text"]');
                const radios = opts.querySelectorAll('input[type="radio"]');
                
                if (type === 'true_false') {
                    inputs[0].value = "True";
                    inputs[1].value = "False";
                    inputs[2].style.display = "none";
                    inputs[3].style.display = "none";
                    radios[2].style.display = "none";
                    radios[3].style.display = "none";
                } else {
                    inputs[2].style.display = "block";
                    inputs[3].style.display = "block";
                    radios[2].style.display = "block";
                    radios[3].style.display = "block";
                }
            } else {
                opts.style.display = 'none';
                short.style.display = 'block';
            }
        }
    </script>
</body>
</html>
