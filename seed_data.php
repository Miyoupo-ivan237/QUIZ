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
    ],
    [
        'title' => 'Requirements Engineering',
        'desc' => 'Explore the process of gathering, documenting, and managing software requirements.',
        'branch' => 'Software Engineering',
        'course' => 'SE101',
        'time' => 12,
        'questions' => [
            ['text' => 'What is the primary goal of requirements elicitation?', 'type' => 'multiple_choice', 'pts' => 20, 'opts' => [['text' => 'Writing code', 'correct' => 0], ['text' => 'Understanding stakeholder needs', 'correct' => 1], ['text' => 'Designing UI', 'correct' => 0]], 'explanation' => 'Elicitation is about gathering needs from stakeholders.'],
            ['text' => 'Functional requirements describe what the system does.', 'type' => 'true_false', 'pts' => 10, 'opts' => [['text' => 'True', 'correct' => 1], ['text' => 'False', 'correct' => 0]], 'explanation' => 'Functional requirements define the specific behavior or functions of the system.'],
            ['text' => 'What is the full form of SRS?', 'type' => 'short_answer', 'pts' => 20, 'answer' => 'Software Requirements Specification', 'explanation' => 'SRS is a document that describes what the software will do and how it will be expected to perform.']
        ]
    ],
    [
        'title' => 'Software Design & Architecture',
        'desc' => 'Core concepts of software architecture patterns and design principles.',
        'branch' => 'Software Engineering',
        'course' => 'SE202',
        'time' => 15,
        'questions' => [
            ['text' => 'Which design pattern is used to restrict a class to only one instance?', 'type' => 'multiple_choice', 'pts' => 20, 'opts' => [['text' => 'Observer', 'correct' => 0], ['text' => 'Singleton', 'correct' => 1], ['text' => 'Factory', 'correct' => 0]], 'explanation' => 'Singleton pattern ensures a class has only one instance.'],
            ['text' => 'Low coupling is generally preferred in software design.', 'type' => 'true_false', 'pts' => 10, 'opts' => [['text' => 'True', 'correct' => 1], ['text' => 'False', 'correct' => 0]], 'explanation' => 'Low coupling reduces dependencies between modules, making them easier to maintain.'],
            ['text' => 'What does MVC stand for?', 'type' => 'short_answer', 'pts' => 20, 'answer' => 'Model View Controller', 'explanation' => 'MVC is an architectural pattern that separates an application into three main logical components.']
        ]
    ],
    [
        'title' => 'Software Testing & QA',
        'desc' => 'Ensuring software quality through various testing methodologies.',
        'branch' => 'Software Engineering',
        'course' => 'SE303',
        'time' => 10,
        'questions' => [
            ['text' => 'Which type of testing focuses on internal logic and code structure?', 'type' => 'multiple_choice', 'pts' => 20, 'opts' => [['text' => 'Black Box', 'correct' => 0], ['text' => 'White Box', 'correct' => 1], ['text' => 'Regression', 'correct' => 0]], 'explanation' => 'White-box testing involves looking at the internal structure of the code.'],
            ['text' => 'Unit testing is performed by the end-users.', 'type' => 'true_false', 'pts' => 10, 'opts' => [['text' => 'True', 'correct' => 0], ['text' => 'False', 'correct' => 1]], 'explanation' => 'Unit testing is typically performed by developers.'],
            ['text' => 'What is the testing that ensures new changes haven\'t broken existing features?', 'type' => 'short_answer', 'pts' => 20, 'answer' => 'Regression Testing', 'explanation' => 'Regression testing verifies that software still performs correctly after changes.']
        ]
    ],
    [
        'title' => 'Software Process Models',
        'desc' => 'Comparing Waterfall, Agile, and other development lifecycles.',
        'branch' => 'Software Engineering',
        'course' => 'SE404',
        'time' => 10,
        'questions' => [
            ['text' => 'Which project management framework is based on sprints?', 'type' => 'multiple_choice', 'pts' => 20, 'opts' => [['text' => 'Waterfall', 'correct' => 0], ['text' => 'Scrum', 'correct' => 1], ['text' => 'V-Model', 'correct' => 0]], 'explanation' => 'Scrum is an Agile framework that uses time-boxed iterations called sprints.'],
            ['text' => 'Agile methodology prioritizes documentation over working software.', 'type' => 'true_false', 'pts' => 10, 'opts' => [['text' => 'True', 'correct' => 0], ['text' => 'False', 'correct' => 1]], 'explanation' => 'The Agile Manifesto values working software over comprehensive documentation.'],
            ['text' => 'What is the oldest software development lifecycle model?', 'type' => 'short_answer', 'pts' => 20, 'answer' => 'Waterfall', 'explanation' => 'Waterfall was the first SDLC model to be introduced.']
        ]
    ]
];

foreach ($quizzes as $q_data) {
    $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, branch, course, time_limit, author_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$q_data['title'], $q_data['desc'], $q_data['branch'], $q_data['course'], $q_data['time'], $admin_id]);
    $quiz_id = $pdo->lastInsertId();

    foreach ($q_data['questions'] as $q_item) {
        $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type, points, correct_answer, explanation) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$quiz_id, $q_item['text'], $q_item['type'], $q_item['pts'], $q_item['answer'] ?? null, $q_item['explanation'] ?? '']);
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

