<?php
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $branch = $_POST['branch'] ?? 'General';
    $course = trim($_POST['course'] ?? 'Computer Science');
    $time_limit = intval($_POST['time_limit'] ?? 0);
    $total_questions = intval($_POST['total_questions'] ?? 10);

    if ($title && $total_questions > 0) {
        $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, branch, course, time_limit, total_questions, author_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $branch, $course, $time_limit, $total_questions, $_SESSION['user_id']]);
        $quiz_id = $pdo->lastInsertId();
        header("Location: editor.php?id=$quiz_id");
        exit();
    } else {
        $error = "Title and Total Questions are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz | QuizMaster</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="teacher-dashboard">
    <header class="nav-bar">
        <h2><i class="fas fa-plus-circle"></i> Create New Assessment</h2>
        <a href="dashboard.php" class="btn btn-secondary" style="width: auto; padding: 10px 24px;">Discard</a>
    </header>

    <main class="content-wrapper" style="max-width: 800px;">
        <div class="glass-card">
            <h1>Quiz Details</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Define the scope and category of the quiz.</p>

            <form method="POST">
                <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

                <div class="input-group">
                    <label>Quiz Title</label>
                    <input type="text" name="title" placeholder="e.g. Introduction to AI" required>
                </div>

                <div class="input-group">
                    <label>Course Name</label>
                    <input type="text" name="course" placeholder="e.g. CS101: Basic Computing" required>
                </div>

                <div class="input-group">
                    <label>Branch / Category</label>
                    <select name="branch">
                        <option value="Artificial Intelligence">Artificial Intelligence</option>
                        <option value="Software Engineering">Software Engineering</option>
                        <option value="Cyber Security">Cyber Security</option>
                        <option value="Networking">Networking</option>
                        <option value="General">General</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Brief overview of what the student will learn."></textarea>
                </div>

                <div style="display: flex; gap: 20px;">
                    <div class="input-group" style="flex: 1;">
                        <label><i class="fas fa-clock"></i> Time Limit (Minutes)</label>
                        <input type="number" name="time_limit" value="15" min="0">
                    </div>
                    <div class="input-group" style="flex: 1;">
                        <label><i class="fas fa-list-ol"></i> Target Question Count</label>
                        <input type="number" name="total_questions" value="10" min="1">
                    </div>
                </div>

                <button type="submit" class="btn">Configure Questions <i class="fas fa-arrow-right"></i></button>
            </form>
        </div>
    </main>
</body>
</html>
