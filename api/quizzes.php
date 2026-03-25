<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Check simple API Auth (optional)
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized Access']);
//     exit();
// }

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $stmt = $pdo->query("SELECT q.*, u.username as teacher_name FROM quizzes q JOIN users u ON q.author_id = u.id");
    $quizzes = $stmt->fetchAll();
    echo json_encode($quizzes);
} elseif ($action === 'details') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$id]);
    $quiz = $stmt->fetch();
    
    if ($quiz) {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
        $stmt->execute([$id]);
        $questions = $stmt->fetchAll();
        
        foreach ($questions as &$q) {
            $stmt = $pdo->prepare("SELECT id, option_text FROM options WHERE question_id = ?");
            $stmt->execute([$q['id']]);
            $q['options'] = $stmt->fetchAll();
        }
        $quiz['questions'] = $questions;
        echo json_encode($quiz);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Quiz Not Found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Action']);
}
?>
