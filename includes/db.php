<?php
// Database setup and connection
$db_path = __DIR__ . '/../quiz.db';

try {
    $pdo = new PDO("sqlite:$db_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable Foreign Keys for SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");

    // Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'student',
        security_pin TEXT DEFAULT '0000',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Attempt to add columns to existing DB gracefully
    try { $pdo->exec("ALTER TABLE users ADD COLUMN security_pin TEXT DEFAULT '0000'"); } catch(PDOException $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN email TEXT"); } catch(PDOException $e) {}

    // Quizzes Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS quizzes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        branch TEXT DEFAULT 'General', -- CS Branch e.g. 'Software Engineering'
        course TEXT DEFAULT 'Intro', -- Course Name e.g. 'Data Structures'
        total_questions INTEGER DEFAULT 0,
        time_limit INTEGER DEFAULT 0, 
        author_id INTEGER,
        level INTEGER DEFAULT 1, -- Level indicator for progression
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (author_id) REFERENCES users(id)
    )");

    // Gracefully add level column if not exists
    try { $pdo->exec("ALTER TABLE quizzes ADD COLUMN level INTEGER DEFAULT 1"); } catch(PDOException $e) {}

    // Questions Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS questions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        quiz_id INTEGER NOT NULL,
        question_text TEXT NOT NULL,
        question_type TEXT DEFAULT 'multiple_choice', -- 'multiple_choice', 'true_false', 'short_answer'
        correct_answer TEXT, -- For short_answer & evaluation
        source TEXT DEFAULT 'manual', 
        explanation TEXT,
        points INTEGER DEFAULT 10,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    )");

    // Options Table (mainly for MCQ and T/F)
    $pdo->exec("CREATE TABLE IF NOT EXISTS options (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        question_id INTEGER NOT NULL,
        option_text TEXT NOT NULL,
        is_correct INTEGER DEFAULT 0,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    )");

    // Attempts Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        quiz_id INTEGER NOT NULL,
        score INTEGER DEFAULT 0,
        total_points INTEGER DEFAULT 0,
        completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
    )");

    // Leaderboard View (Optional for easier queries)
    $pdo->exec("CREATE VIEW IF NOT EXISTS leaderboard AS
        SELECT u.username, q.title as quiz_title, a.score, a.total_points, a.completed_at
        FROM attempts a
        JOIN users u ON a.user_id = u.id
        JOIN quizzes q ON a.quiz_id = q.id
        ORDER BY a.score DESC");

    // Create a default teacher if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $admin_pwd = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
        $stmt->execute([$admin_pwd]);
    }

} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
