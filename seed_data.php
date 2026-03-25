<?php
require_once 'includes/db.php';

echo "Seeding data...\n";

// Ensure Admin
$admin_pwd = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
$stmt->execute([$admin_pwd]);
$admin_id = $pdo->lastInsertId() ?: 1;

// Clear structure for fresh seed (optional in dev)
$pdo->exec("DELETE FROM quizzes");
$pdo->exec("DELETE FROM questions");
$pdo->exec("DELETE FROM options");

$quizzes = [
    [
        'title' => 'Ethical Hacking 101',
        'desc' => 'Basics of penetration testing and common security vulnerabilities.',
        'branch' => 'Cyber Security',
        'course' => 'Security+',
        'time' => 10,
        'questions' => [
            ['text' => 'What does SQL injection primarily target?', 'type' => 'multiple_choice', 'pts' => 20, 'opts' => [['text' => 'Bypassing firewalls', 'correct' => 0], ['text' => 'Exploiting database inputs', 'correct' => 1], ['text' => 'Flooding bandwidth', 'correct' => 0]]],
            ['text' => 'VPN stands for Virtual Private Network.', 'type' => 'true_false', 'pts' => 10, 'opts' => [['text' => 'True', 'correct' => 1], ['text' => 'False', 'correct' => 0]]],
            ['text' => 'What is the default port for SSH?', 'type' => 'short_answer', 'pts' => 20, 'answer' => '22']
        ]
    ],
    [
        'title' => 'Data Structures in C++',
        'desc' => 'Test your knowledge on Stacks, Queues, and Linked Lists.',
        'branch' => 'Software Engineering',
        'course' => 'CS201',
        'time' => 15,
        'questions' => [
            ['text' => 'Which data structure follows LIFO?', 'type' => 'multiple_choice', 'pts' => 20, 'opts' => [['text' => 'Queue', 'correct' => 0], ['text' => 'Stack', 'correct' => 1], ['text' => 'List', 'correct' => 0]]],
            ['text' => 'Binary Search Tree is always balanced by default.', 'type' => 'true_false', 'pts' => 10, 'opts' => [['text' => 'True', 'correct' => 0], ['text' => 'False', 'correct' => 1]]],
            ['text' => 'What is the T(n) complexity of Heapify?', 'type' => 'short_answer', 'pts' => 20, 'answer' => 'O(log n)']
        ]
    ],
    [
        'title' => 'Neural Networks Basics',
        'desc' => 'Overview of activation functions and perceptrons.',
        'branch' => 'Artificial Intelligence',
        'course' => 'Intro to AI',
        'time' => 8,
        'questions' => [
            ['text' => 'Which function is often used to map outputs between 0 and 1?', 'type' => 'multiple_choice', 'pts' => 20, 'opts' => [['text' => 'ReLU', 'correct' => 0], ['text' => 'Sigmoid', 'correct' => 1], ['text' => 'Tanh', 'correct' => 0]]],
            ['text' => 'Backpropagation is used for training weights.', 'type' => 'true_false', 'pts' => 10, 'opts' => [['text' => 'True', 'correct' => 1], ['text' => 'False', 'correct' => 0]]],
            ['text' => 'Who is often called the Father of AI?', 'type' => 'short_answer', 'pts' => 20, 'answer' => 'John McCarthy']
        ]
    ]
];

foreach ($quizzes as $q_data) {
    $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, branch, course, time_limit, author_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$q_data['title'], $q_data['desc'], $q_data['branch'], $q_data['course'], $q_data['time'], $admin_id]);
    $quiz_id = $pdo->lastInsertId();

    foreach ($q_data['questions'] as $q_item) {
        $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type, points, correct_answer) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$quiz_id, $q_item['text'], $q_item['type'], $q_item['pts'], $q_item['answer'] ?? null]);
        $question_id = $pdo->lastInsertId();

        if (in_array($q_item['type'], ['multiple_choice', 'true_false'])) {
            foreach ($q_item['opts'] as $opt) {
                $stmt = $pdo->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, $opt['text'], $opt['correct']]);
            }
        }
    }
}

echo "Seeding complete.\n";
?>
